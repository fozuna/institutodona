<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\CronogramaTrafficLight;
use App\Core\DateHelper;
use App\Core\PdfSupport;
use App\Core\ReportBranding;
use App\Core\Security;
use App\Core\AuditLogger;
use App\Models\CronogramaModel;
use App\Models\CronogramaEventoModel;
use App\Models\ClienteModel;
use App\Models\PilarModel;
use DateTimeImmutable;

class CronogramaController extends BaseController
{
    private const PERIODICIDADES = [
        'unico' => 'Unico (sem repeticao)',
        'mensal' => 'Mensal',
        'bimestral' => 'Bimestral',
        'trimestral' => 'Trimestral',
        'semestral' => 'Semestral',
        'anual' => 'Anual',
    ];

    private CronogramaModel $cronogramas;
    private CronogramaEventoModel $eventos;
    private const REUNIAO_ANEXO_MAX_BYTES = 20971520;
    private const REUNIAO_ANEXO_MAX_FILES = 10;

    public function __construct()
    {
        $this->cronogramas = new CronogramaModel();
        $this->eventos = new CronogramaEventoModel();
    }

    public function index(): void
    {
        $this->requireClienteAdminAccess();
        $cid = (int)($_GET['id_cliente'] ?? 0);
        $order = $this->buildCronogramaOrder();
        $items = $cid ? $this->cronogramas->byCliente($cid, $order) : $this->cronogramas->all($order);
        $this->render('cronograma/index', [
            'items' => $items,
            'order' => $order,
            'isClientOrderable' => true,
            'flashSuccess' => $this->takeFlash('flash_success'),
            'flashError' => $this->takeFlash('flash_error'),
        ]);
    }

    public function create(): void
    {
        $this->requireClienteAdminAccess();
        $clientes = (new ClienteModel())->all();
        $pref = (int)($_GET['id_cliente'] ?? 0);
        $this->render('cronograma/create', ['clientes' => $clientes, 'pref' => $pref]);
    }

    public function store(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'ano' => (int)($_POST['ano'] ?? date('Y')),
            'ativo' => (int)($_POST['ativo'] ?? 1) === 1 ? 1 : 0,
        ];
        if ($data['id_cliente'] && $data['ano']) {
            $id = $this->cronogramas->create($data);
            header('Location: index.php?route=cronograma/show&id=' . $id);
            return;
        }
        header('Location: index.php?route=cronograma/index');
    }

    public function edit(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        if (!$crono) {
            $_SESSION['flash_error'] = 'Cronograma não encontrado.';
            header('Location: index.php?route=cronograma/index');
            return;
        }
        $clientes = (new ClienteModel())->all();
        $this->render('cronograma/create', [
            'clientes' => $clientes,
            'pref' => (int)($crono['id_cliente'] ?? 0),
            'crono' => $crono,
        ]);
    }

    public function update(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'ano' => (int)($_POST['ano'] ?? date('Y')),
            'ativo' => (int)($_POST['ativo'] ?? 1) === 1 ? 1 : 0,
        ];
        $ok = $this->cronogramas->update($id, $data);
        $_SESSION[$ok ? 'flash_success' : 'flash_error'] = $ok ? 'Cronograma atualizado com sucesso.' : 'Não foi possível atualizar o cronograma.';
        header('Location: index.php?route=cronograma/index');
    }

    public function delete(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $crono = $this->cronogramas->find($id);
        if (!$crono) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Cronograma não encontrado.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = 'Cronograma não encontrado.';
            header('Location: index.php?route=cronograma/index');
            return;
        }
        try {
            $deletedEvents = $this->eventos->deleteByCronograma($id);
            $ok = $this->cronogramas->delete($id);
            if (!$ok) {
                throw new \RuntimeException('Não foi possível excluir o cronograma.');
            }
            AuditLogger::log('cronograma_delete', 'cronograma', $id, ['deleted_events' => $deletedEvents]);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'message' => 'Cronograma excluído com sucesso.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_success'] = 'Cronograma excluído com sucesso.';
            header('Location: index.php?route=cronograma/index');
        } catch (\Throwable $e) {
            AuditLogger::log('cronograma_delete_failed', 'cronograma', $id, ['error' => $e->getMessage()]);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: index.php?route=cronograma/index');
        }
    }

    public function duplicate(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $crono = $this->cronogramas->find($id);
        if (!$crono) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Cronograma não encontrado.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = 'Cronograma não encontrado.';
            header('Location: index.php?route=cronograma/index');
            return;
        }
        try {
            $newId = $this->cronogramas->duplicate($id);
            if ($newId <= 0) {
                throw new \RuntimeException('Não foi possível duplicar o cronograma.');
            }
            $dup = $this->eventos->duplicateForCronograma($id, $newId);
            if (empty($dup['ok'])) {
                throw new \RuntimeException((string)($dup['error'] ?? 'Falha ao duplicar eventos do cronograma.'));
            }
            AuditLogger::log('cronograma_duplicate', 'cronograma', $newId, ['source_id' => $id, 'events_created' => (int)($dup['created'] ?? 0)]);
            $message = 'Cronograma duplicado com sucesso.';
            $redirectUrl = 'index.php?route=cronograma/show&id=' . $newId;
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'message' => $message, 'redirect_url' => $redirectUrl], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_success'] = $message;
            header('Location: ' . $redirectUrl);
        } catch (\Throwable $e) {
            AuditLogger::log('cronograma_duplicate_failed', 'cronograma', $id, ['error' => $e->getMessage()]);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            header('Location: index.php?route=cronograma/index');
        }
    }

    public function toggleAtivo(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $next = $this->cronogramas->toggleAtivo($id);
        if ($next === null) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Não foi possível atualizar o status do cronograma.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = 'Não foi possível atualizar o status do cronograma.';
            header('Location: index.php?route=cronograma/index');
            return;
        }
        $msg = $next === 1 ? 'Cronograma ativado.' : 'Cronograma desativado.';
        AuditLogger::log('cronograma_toggle_ativo', 'cronograma', $id, ['ativo' => $next]);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'message' => $msg, 'ativo' => $next], JSON_UNESCAPED_UNICODE);
            return;
        }
        $_SESSION['flash_success'] = $msg;
        header('Location: index.php?route=cronograma/index');
    }

    public function selectCliente(): void
    {
        $this->requireClienteAdminAccess();
        $clientes = (new ClienteModel())->all();
        $this->render('cronograma/select_cliente', ['clientes' => $clientes]);
    }

    public function show(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_GET['status_filter'] ?? 'todos');
        $filters = $this->buildOcorrenciasFilters();
        $order = $this->buildOcorrenciasOrder();
        $gridOrder = $this->buildGridOrder();
        $allEvents = $this->eventos->byCronograma($id);
        $annotatedEvents = $this->annotateEvents($allEvents);
        $grid = $this->buildGrid($annotatedEvents);
        $events = $this->filterEventsByTraffic($annotatedEvents, $statusFilter);
        $grid = $this->filterGridByTraffic($grid, $statusFilter);
        $events = $this->filterEventsByCriteria($events, $filters);
        $events = $this->sortEvents($events, $order);
        $grid = $this->sortGridRows($grid, $gridOrder);
        $eventIds = [];
        foreach ($events as $ev) {
            if (($ev['tipo_evento'] ?? '') === 'Reunião') {
                $eventIds[] = (int)($ev['id'] ?? 0);
            }
        }
        $eventIds = array_values(array_unique(array_filter($eventIds, static fn(int $v): bool => $v > 0)));
        $anexosMap = !empty($eventIds) ? $this->eventos->anexosByEventIds($eventIds) : [];
        foreach ($events as $i => $ev) {
            if (($ev['tipo_evento'] ?? '') !== 'Reunião') {
                continue;
            }
            $eid = (int)($ev['id'] ?? 0);
            $events[$i]['anexos'] = $eid > 0 ? ($anexosMap[$eid] ?? []) : [];
        }
        $pilares = (new PilarModel())->all();
        $occOptions = $this->buildOcorrenciasOptions($annotatedEvents);

        AuditLogger::log('cronograma_show', 'cronograma', $id, [
            'cronograma_found' => (bool)$crono,
            'events_count' => count($allEvents),
            'status_filter' => $statusFilter,
            'filters' => $filters,
            'order' => $order,
        ]);

        $this->render('cronograma/show', [
            'crono' => $crono,
            'events' => $events,
            'grid' => $grid,
            'periodicidades' => self::PERIODICIDADES,
            'statusFilter' => $statusFilter,
            'pilares' => $pilares,
            'occFilters' => $filters,
            'occOrder' => $order,
            'gridOrder' => $gridOrder,
            'occOptions' => $occOptions,
            'totalEvents' => count($allEvents),
            'occFilterError' => $filters['error'] ?? null,
            'flashSuccess' => $this->takeFlash('flash_success'),
            'flashError' => $this->takeFlash('flash_error'),
        ]);
    }

    public function gridPdf(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        if (!$crono) {
            http_response_code(404);
            echo 'Cronograma não encontrado.';
            return;
        }
        if (!PdfSupport::isDompdfAvailable()) {
            $errorId = PdfSupport::newErrorId();
            AuditLogger::log('pdf_unavailable', 'cronograma', $id, [
                'error_id' => $errorId,
                'route' => 'cronograma/gridPdf',
                'reason' => 'dompdf_missing',
                'diagnostics' => PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            echo PdfSupport::missingDompdfMessage() . ' Código: ' . $errorId;
            return;
        }

        $statusFilter = CronogramaTrafficLight::normalizeFilter($_GET['status_filter'] ?? 'todos');
        $gridOrder = $this->buildGridOrder();
        $allEvents = $this->eventos->byCronograma($id);
        $annotatedEvents = $this->annotateEvents($allEvents);
        $grid = $this->buildGrid($annotatedEvents);
        $grid = $this->filterGridByTraffic($grid, $statusFilter);
        $grid = $this->sortGridRows($grid, $gridOrder);

        $subtitleParts = array_values(array_filter([
            trim((string)($crono['cliente'] ?? '')),
            !empty($crono['ano']) ? ('Ano ' . (int)$crono['ano']) : null,
            !empty($crono['nome']) ? (string)$crono['nome'] : null,
        ]));
        $branding = ReportBranding::aplicarBrandingRelatorio('pdf', [
            'report_title' => 'Cronograma Anual',
            'header_title' => 'Cronograma Anual',
            'header_subtitle' => !empty($subtitleParts) ? implode(' · ', $subtitleParts) : 'Grade anual de atividades',
            'logo_position' => 'left',
            'logo_width' => 100,
            'margins' => ['top' => 12, 'right' => 10, 'bottom' => 14, 'left' => 10],
            'footer_text' => 'Relatório do sistema',
            'generated_at' => DateHelper::now(),
        ]);

        ob_start();
        require __DIR__ . '/../views/cronograma/grid_pdf.php';
        $html = (string)ob_get_clean();

        if (!empty($_GET['preview'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
            return;
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('dpi', 120);
        $options->setChroot(dirname(__DIR__, 2));

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $pdf = (string)$dompdf->output();

        AuditLogger::log('pdf_export', 'cronograma', $id, [
            'via' => 'grid_pdf',
            'status_filter' => $statusFilter,
            'grid_order' => $gridOrder,
            'rows' => count($grid),
        ]);

        $filename = 'cronograma_' . $id . '_' . date('Ymd_His') . '.pdf';
        if (PHP_SAPI !== 'cli') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pdf));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
    }

    public function addEvento(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $tipoEvento = CronogramaEventoModel::normalizeEventType($_POST['tipo_evento'] ?? null);
        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
            'periodicidade' => $_POST['periodicidade'] ?? 'unico',
            'tipo_evento' => $tipoEvento,
        ];

        $isValid = $idCronograma && $data['data'] && $data['topico'] && $data['atividade'];
        AuditLogger::log('cronograma_add_evento_attempt', 'cronograma_evento', null, [
            'id_cronograma' => $idCronograma,
            'is_valid' => (bool)$isValid,
        ]);
        if ($isValid) {
            try {
                $newId = $this->eventos->create($idCronograma, $data);
                $_SESSION['flash_success'] = 'Evento salvo com recorrencia processada com sucesso.';
                AuditLogger::log('cronograma_add_evento_success', 'cronograma_evento', $newId, [
                    'id_cronograma' => $idCronograma,
                    'periodicidade' => $data['periodicidade'],
                    'tipo_evento' => $tipoEvento,
                ]);
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
                AuditLogger::log('cronograma_add_evento_error', 'cronograma_evento', null, [
                    'id_cronograma' => $idCronograma,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $_SESSION['flash_error'] = 'Preencha os campos obrigatorios do evento.';
            AuditLogger::log('cronograma_add_evento_invalid', 'cronograma_evento', null, [
                'id_cronograma' => $idCronograma,
            ]);
        }
        $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
        header('Location: ' . $url);
    }

    public function ataDownload(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id_evento'] ?? 0);
        $ev = $this->eventos->find($id);
        if (!$ev) {
            http_response_code(404);
            echo 'Evento não encontrado.';
            return;
        }
        $tipo = (string)($ev['tipo_evento'] ?? '');
        $path = (string)($ev['ata_path'] ?? '');
        if ($tipo !== 'Reunião' || $path === '' || !is_file($path)) {
            http_response_code(404);
            echo 'Ata não disponível.';
            return;
        }
        $mime = (string)($ev['ata_mime'] ?? 'application/octet-stream');
        $name = (string)($ev['ata_original_name'] ?? ('ata_' . $id));
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: ('ata_' . $id);
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    public function ataUpload(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }

        $idEvento = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        $ev = $this->eventos->find($idEvento);
        if (!$ev) {
            http_response_code(404);
            $payload = ['ok' => false, 'message' => 'Evento não encontrado.'];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
        $tipoEvento = CronogramaEventoModel::normalizeEventType($ev['tipo_evento'] ?? null);
        if ($tipoEvento !== 'Reunião') {
            http_response_code(400);
            $payload = ['ok' => false, 'message' => 'O anexo de ata é permitido apenas para eventos do tipo Reunião.'];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }

        $storedAtaPath = null;
        try {
            $ata = $this->handleAtaUploadIfAny($idCronograma > 0 ? $idCronograma : (int)($ev['id_cronograma'] ?? 0), $tipoEvento, true);
            $storedAtaPath = (string)($ata['ata_path'] ?? '');
            $ok = $this->eventos->setAta($idEvento, $ata);
            if (!$ok) {
                throw new \RuntimeException('Não foi possível salvar o anexo da ata no evento.');
            }
            AuditLogger::log('cronograma_ata_uploaded', 'cronograma_evento', $idEvento, [
                'id_cronograma' => (int)($ev['id_cronograma'] ?? 0),
                'ata_size' => (int)($ata['ata_size'] ?? 0),
                'ata_mime' => (string)($ata['ata_mime'] ?? ''),
            ]);
            $payload = [
                'ok' => true,
                'message' => 'Ata anexada com sucesso.',
                'event_id' => $idEvento,
                'download_url' => 'index.php?route=cronograma/ataDownload&id_evento=' . $idEvento,
            ];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_success'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        } catch (\Throwable $e) {
            if (is_string($storedAtaPath) && $storedAtaPath !== '' && is_file($storedAtaPath)) {
                @unlink($storedAtaPath);
            }
            $payload = ['ok' => false, 'message' => $e->getMessage()];
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
    }

    private function handleAtaUploadIfAny(int $idCronograma, string $tipoEvento, bool $required = false): array
    {
        if ($idCronograma <= 0) {
            return [];
        }
        $file = $_FILES['ata'] ?? null;
        if ($tipoEvento !== 'Reunião') {
            if (is_array($file) && !empty($file['name'])) {
                throw new \RuntimeException('O anexo de ata é permitido apenas para eventos do tipo Reunião.');
            }
            return [];
        }
        if (!is_array($file) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($required) {
                throw new \RuntimeException('Selecione o arquivo da ata para anexar.');
            }
            return [];
        }
        if ((int)($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha no upload da ata.');
        }
        $tmp = (string)($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Arquivo temporário inválido.');
        }
        $sizeBytes = (int)($file['size'] ?? 0);
        $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : null;
        $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : (string)($file['type'] ?? '');
        if ($finfo) {
            @finfo_close($finfo);
        }
        $validated = CronogramaEventoModel::validateAtaUpload((string)($file['name'] ?? ''), $sizeBytes, $mime, 50 * 1024 * 1024);
        if (empty($validated['ok'])) {
            throw new \RuntimeException((string)($validated['message'] ?? 'Arquivo inválido.'));
        }
        $token = bin2hex(random_bytes(8));
        $baseDir = dirname(__DIR__, 2) . '/storage/cronograma/atas/' . $idCronograma . '/' . $token;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new \RuntimeException('Não foi possível criar o diretório da ata.');
        }
        if (!is_writable($baseDir)) {
            throw new \RuntimeException('Diretório da ata sem permissão de escrita.');
        }
        $clientName = basename((string)($file['name'] ?? 'ata'));
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $clientName) ?: 'ata';
        $dest = $baseDir . '/' . date('Ymd_His') . '_' . $safe;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo da ata.');
        }
        $sha256 = hash_file('sha256', $dest) ?: null;
        return [
            'ata_path' => $dest,
            'ata_original_name' => $clientName,
            'ata_mime' => (string)($validated['mime'] ?? $mime),
            'ata_size' => $sizeBytes,
            'ata_sha256' => $sha256,
        ];
    }

    public function anexosUpload(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }

        $idEvento = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $encerramento = (int)($_POST['encerramento'] ?? 0) === 1;
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        $ev = $this->eventos->find($idEvento);
        if (!$ev) {
            http_response_code(404);
            $payload = ['ok' => false, 'message' => 'Evento não encontrado.'];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
        $tipoEvento = CronogramaEventoModel::normalizeEventType($ev['tipo_evento'] ?? null);
        if ($tipoEvento !== 'Reunião') {
            http_response_code(400);
            $payload = ['ok' => false, 'message' => 'Anexos são permitidos apenas para eventos do tipo Reunião.'];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
        if (!$encerramento) {
            http_response_code(400);
            $payload = ['ok' => false, 'message' => 'Documentos de reuniões só podem ser anexados na etapa de encerramento.'];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
        if ((string)($ev['status'] ?? '') === 'Finalizado') {
            http_response_code(400);
            $payload = ['ok' => false, 'message' => 'Esta reunião já está encerrada.'];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
        $cronogramaId = $idCronograma > 0 ? $idCronograma : (int)($ev['id_cronograma'] ?? 0);

        try {
            $this->storeReuniaoAnexosFromRequest($idEvento, $cronogramaId, true);
            $anexos = $this->eventos->anexosList($idEvento);
            $payload = [
                'ok' => true,
                'message' => 'Documentos anexados com sucesso.',
                'event_id' => $idEvento,
                'anexos' => array_map(static fn(array $a): array => [
                    'id' => (int)($a['id'] ?? 0),
                    'name' => (string)($a['original_name'] ?? ''),
                    'size' => (int)($a['size'] ?? 0),
                    'download_url' => 'index.php?route=cronograma/anexoDownload&id_anexo=' . (int)($a['id'] ?? 0),
                ], $anexos),
            ];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_success'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($cronogramaId, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        } catch (\Throwable $e) {
            $payload = ['ok' => false, 'message' => $e->getMessage()];
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($cronogramaId, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
    }

    public function anexoDownload(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id_anexo'] ?? 0);
        $anexo = $this->eventos->anexoFind($id);
        if (!$anexo || !empty($anexo['deleted_at'])) {
            http_response_code(404);
            echo 'Anexo não encontrado.';
            return;
        }
        $tipo = CronogramaEventoModel::normalizeEventType($anexo['tipo_evento'] ?? null);
        if ($tipo !== 'Reunião') {
            http_response_code(404);
            echo 'Anexo não disponível.';
            return;
        }
        $path = (string)($anexo['path'] ?? '');
        if ($path === '' || !is_file($path)) {
            http_response_code(404);
            echo 'Arquivo não disponível.';
            return;
        }
        $mime = (string)($anexo['mime'] ?? 'application/octet-stream');
        $name = (string)($anexo['original_name'] ?? ('anexo_' . $id));
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?: ('anexo_' . $id);
        header('Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $safe . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    public function anexoDelete(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }

        $idAnexo = (int)($_POST['id_anexo'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $encerramento = (int)($_POST['encerramento'] ?? 0) === 1;
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        try {
            $anexo = $this->eventos->anexoFind($idAnexo);
            if (!$anexo || !empty($anexo['deleted_at'])) {
                throw new \RuntimeException('Anexo não encontrado.');
            }
            $tipo = CronogramaEventoModel::normalizeEventType($anexo['tipo_evento'] ?? null);
            if ($tipo !== 'Reunião') {
                throw new \RuntimeException('Anexos são permitidos apenas para eventos do tipo Reunião.');
            }
            if (!$encerramento) {
                throw new \RuntimeException('Documentos de reuniões só podem ser gerenciados na etapa de encerramento.');
            }
            if ((string)($anexo['status'] ?? '') === 'Finalizado') {
                throw new \RuntimeException('Esta reunião já está encerrada.');
            }
            $path = (string)($anexo['path'] ?? '');
            $ok = $this->eventos->anexoSoftDelete($idAnexo);
            if (!$ok) {
                throw new \RuntimeException('Não foi possível remover o anexo.');
            }
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
            $eventId = (int)($anexo['evento_id'] ?? 0);
            $anexos = $this->eventos->anexosList($eventId);
            $payload = [
                'ok' => true,
                'message' => 'Anexo removido com sucesso.',
                'event_id' => $eventId,
                'anexos' => array_map(static fn(array $a): array => [
                    'id' => (int)($a['id'] ?? 0),
                    'name' => (string)($a['original_name'] ?? ''),
                    'size' => (int)($a['size'] ?? 0),
                    'download_url' => 'index.php?route=cronograma/anexoDownload&id_anexo=' . (int)($a['id'] ?? 0),
                ], $anexos),
            ];
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_success'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        } catch (\Throwable $e) {
            $payload = ['ok' => false, 'message' => $e->getMessage()];
            if ($isAjax) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($payload, JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = $payload['message'];
            $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
            header('Location: ' . $url);
            return;
        }
    }

    private function countUploadedFiles(string $inputName): int
    {
        $files = $_FILES[$inputName] ?? null;
        if (!is_array($files) || !isset($files['name'])) {
            return 0;
        }
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $errors = is_array($files['error'] ?? null) ? $files['error'] : [($files['error'] ?? UPLOAD_ERR_NO_FILE)];
        $count = 0;
        foreach ($names as $i => $name) {
            $err = (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_OK && trim((string)$name) !== '') {
                $count++;
            }
        }
        return $count;
    }

    private function storeReuniaoAnexosFromRequest(int $serieId, int $idCronograma, bool $required): void
    {
        $eventId = (int)$serieId;
        if ($eventId <= 0 || $idCronograma <= 0) {
            throw new \RuntimeException('Evento inválido para anexos.');
        }
        $files = $_FILES['anexos'] ?? null;
        if (!is_array($files) || !isset($files['name'])) {
            if ($required) {
                throw new \RuntimeException('Para eventos do tipo Reunião, é obrigatório anexar pelo menos um documento.');
            }
            return;
        }
        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $tmpNames = is_array($files['tmp_name'] ?? null) ? $files['tmp_name'] : [($files['tmp_name'] ?? '')];
        $errors = is_array($files['error'] ?? null) ? $files['error'] : [($files['error'] ?? UPLOAD_ERR_NO_FILE)];
        $sizes = is_array($files['size'] ?? null) ? $files['size'] : [($files['size'] ?? 0)];
        $types = is_array($files['type'] ?? null) ? $files['type'] : [($files['type'] ?? '')];

        $items = [];
        $storedPaths = [];
        $total = 0;
        for ($i = 0; $i < count($names); $i++) {
            $name = trim((string)($names[$i] ?? ''));
            $err = (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE && $name === '') {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                if (in_array($err, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                    throw new \RuntimeException('Arquivo excede o limite permitido.');
                }
                throw new \RuntimeException('Falha no upload do arquivo.');
            }
            $total++;
            if ($total > self::REUNIAO_ANEXO_MAX_FILES) {
                throw new \RuntimeException('Limite de arquivos excedido (máximo de 10 por envio).');
            }
            $tmp = (string)($tmpNames[$i] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                throw new \RuntimeException('Arquivo temporário inválido.');
            }
            $sizeBytes = (int)($sizes[$i] ?? 0);
            $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : null;
            $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : (string)($types[$i] ?? '');
            if ($finfo) {
                @finfo_close($finfo);
            }
            $validated = CronogramaEventoModel::validateReuniaoDocumentoUpload($name, $sizeBytes, $mime, self::REUNIAO_ANEXO_MAX_BYTES);
            if (empty($validated['ok'])) {
                throw new \RuntimeException((string)($validated['message'] ?? 'Arquivo inválido.'));
            }
            $token = bin2hex(random_bytes(8));
            $baseDir = dirname(__DIR__, 2) . '/storage/cronograma/reunioes/' . $idCronograma . '/' . $eventId . '/' . $token;
            if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
                throw new \RuntimeException('Não foi possível criar o diretório do anexo.');
            }
            if (!is_writable($baseDir)) {
                throw new \RuntimeException('Diretório do anexo sem permissão de escrita.');
            }
            $clientName = basename((string)$name);
            $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', $clientName) ?: 'anexo';
            $dest = $baseDir . '/' . date('Ymd_His') . '_' . $safe;
            if (!move_uploaded_file($tmp, $dest)) {
                throw new \RuntimeException('Não foi possível salvar o arquivo.');
            }
            $storedPaths[] = $dest;
            $sha256 = hash_file('sha256', $dest) ?: null;
            $items[] = [
                'path' => $dest,
                'original_name' => $clientName,
                'mime' => (string)($validated['mime'] ?? $mime),
                'size' => $sizeBytes,
                'sha256' => $sha256,
            ];
        }
        if (empty($items)) {
            if ($required) {
                throw new \RuntimeException('Para eventos do tipo Reunião, é obrigatório anexar pelo menos um documento.');
            }
            return;
        }
        try {
            $this->eventos->anexosCreateMany($eventId, $items);
        } catch (\Throwable $e) {
            foreach ($storedPaths as $p) {
                if (is_string($p) && $p !== '' && is_file($p)) {
                    @unlink($p);
                }
            }
            throw $e;
        }
    }

    public function toggleStatus(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $id = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $finalizado = (int)($_POST['finalizado'] ?? $_POST['realizado'] ?? 0) === 1;
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $targetStatus = $finalizado ? 'Finalizado' : 'Pendente';

        try {
            $ok = $id > 0 ? $this->eventos->setStatus($id, $targetStatus) : false;
            if (!$ok) {
                http_response_code(400);
                $this->respondToggleStatus(['ok' => false, 'message' => 'Nao foi possivel atualizar o status do evento.'], $idCronograma, $statusFilter, $_POST);
                return;
            }
        } catch (\Throwable $e) {
            http_response_code(422);
            $this->respondToggleStatus(['ok' => false, 'message' => $e->getMessage()], $idCronograma, $statusFilter, $_POST);
            return;
        }

        $event = $this->annotateEvent($this->eventos->find($id));
        if (!$event) {
            http_response_code(404);
            $this->respondToggleStatus(['ok' => false, 'message' => 'Evento nao encontrado apos a atualizacao.'], $idCronograma, $statusFilter, $_POST);
            return;
        }
        $series = $this->annotateEvents($this->eventos->seriesMembers((int)$event['serie_id']));
        $grid = $this->buildGrid($series);
        $row = $grid[(int)$event['serie_id']] ?? null;

        $payload = [
            'ok' => true,
            'occurrence' => [
                'id' => (int)$event['id'],
                'status' => (string)$event['status'],
                'traffic' => $event['traffic'],
            ],
            'series' => [
                'serie_id' => (int)$event['serie_id'],
                'traffic' => $row['traffic'] ?? CronogramaTrafficLight::series([]),
                'months' => $row['meses'] ?? [],
            ],
        ];
        $this->respondToggleStatus($payload, $idCronograma, $statusFilter, $_POST);
    }

    public function addEventoForm(): void
    {
        $this->requireClienteAdminAccess();
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $pilares = (new PilarModel())->all();
        $eventTypes = CronogramaEventoModel::eventTypeOptions();
        $this->render('cronograma/add_evento', [
            'crono' => $crono,
            'periodicidades' => self::PERIODICIDADES,
            'pilares' => $pilares,
            'eventTypes' => $eventTypes,
        ]);
    }

    public function eventoDrawer(): void
    {
        $this->requireClienteAdminAccess();
        $idEvento = (int)($_GET['id_evento'] ?? 0);
        $idCronograma = (int)($_GET['id_cronograma'] ?? 0);
        $scope = (string)($_GET['escopo'] ?? 'evento');
        $mode = (string)($_GET['mode'] ?? 'edit');
        $event = $idEvento > 0 ? $this->eventos->find($idEvento) : null;
        if (!$event) {
            http_response_code(404);
            echo 'Evento não encontrado.';
            return;
        }
        $event = $this->annotateEvent($event);
        $pilares = (new PilarModel())->all();
        $eventTypes = CronogramaEventoModel::eventTypeOptions();
        $anexos = [];
        $hasLegacyAta = !empty($event['ata_path']);
        if (($event['tipo_evento'] ?? '') === 'Reunião') {
            $anexos = $this->eventos->anexosList((int)$event['id']);
        }
        header('Content-Type: text/html; charset=utf-8');
        $this->renderPartial('cronograma/evento_drawer', [
            'event' => $event,
            'cronoId' => $idCronograma > 0 ? $idCronograma : (int)($event['id_cronograma'] ?? 0),
            'periodicidades' => self::PERIODICIDADES,
            'pilares' => $pilares,
            'eventTypes' => $eventTypes,
            'anexos' => $anexos,
            'hasLegacyAta' => $hasLegacyAta,
            'scope' => $scope === 'serie' ? 'serie' : 'evento',
            'mode' => $mode,
        ]);
    }

    public function updateEvento(): void
    {
        $this->requireClienteAdminAccess();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        $responsavelPrincipal = trim((string)($_POST['responsavel_principal'] ?? ''));
        $responsaveisSecundarios = trim((string)($_POST['responsaveis_secundarios'] ?? ''));
        $responsavel = trim((string)($_POST['responsavel'] ?? ''));
        if ($responsavel === '') {
            $parts = [];
            if ($responsavelPrincipal !== '') {
                $parts[] = $responsavelPrincipal;
            }
            if ($responsaveisSecundarios !== '') {
                $parts[] = $responsaveisSecundarios;
            }
            $responsavel = trim(implode(', ', $parts));
        }

        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => $responsavel,
            'responsavel_principal' => $responsavelPrincipal,
            'responsaveis_secundarios' => $responsaveisSecundarios,
            'comentarios' => trim((string)($_POST['comentarios'] ?? '')),
            'notas_internas' => trim((string)($_POST['notas_internas'] ?? '')),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
            'periodicidade' => $_POST['periodicidade'] ?? 'unico',
            'tipo_evento' => trim((string)($_POST['tipo_evento'] ?? '')),
        ];
        $scope = ($_POST['escopo'] ?? 'evento') === 'serie' ? 'serie' : 'evento';
        if ($id) {
            try {
                $event = $this->eventos->find($id);
                if ($event) {
                    $tipo = CronogramaEventoModel::normalizeEventType($data['tipo_evento'] ?? ($event['tipo_evento'] ?? null));
                    $data['tipo_evento'] = $tipo;
                    $currentStatus = (string)($event['status'] ?? 'Planejado');
                    $targetStatus = (string)($data['status'] ?? 'Planejado');
                    if ($tipo === 'Reunião' && $targetStatus === 'Finalizado') {
                        if ($scope === 'serie') {
                            throw new \RuntimeException('O encerramento de reuniões deve ser feito por ocorrência (não pela série).');
                        }
                        if ($currentStatus === 'Finalizado') {
                            throw new \RuntimeException('Esta reunião já está encerrada.');
                        }
                        $effectiveCronogramaId = $idCronograma > 0 ? $idCronograma : (int)($event['id_cronograma'] ?? 0);
                        $legacy = !empty($event['ata_path']) ? 1 : 0;
                        $existing = count($this->eventos->anexosList($id)) + $legacy;
                        $hasNewFiles = $this->countUploadedFiles('anexos') > 0;
                        if ($hasNewFiles) {
                            $this->storeReuniaoAnexosFromRequest($id, $effectiveCronogramaId, true);
                            $existing = count($this->eventos->anexosList($id)) + $legacy;
                        }
                        if ($existing <= 0) {
                            throw new \RuntimeException('Para encerrar uma reunião, anexe pelo menos um documento.');
                        }
                    }
                }
                $this->eventos->update($id, $data, $scope);
                if ($isAjax) {
                    $updated = $this->annotateEvent($this->eventos->find($id));
                    if (!$updated) {
                        throw new \RuntimeException('Evento não encontrado após a atualização.');
                    }
                    $serieId = (int)($updated['serie_id'] ?? $updated['id']);
                    $seriesMembers = $this->annotateEvents($this->eventos->seriesMembers($serieId));
                    $grid = $this->buildGrid($seriesMembers);
                    $row = $grid[$serieId] ?? null;
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => true,
                        'message' => $scope === 'serie' ? 'Série atualizada com sucesso.' : 'Evento atualizado com sucesso.',
                        'occurrence' => [
                            'id' => (int)$updated['id'],
                            'data' => (string)$updated['data'],
                            'topico' => (string)$updated['topico'],
                            'unidade' => (string)($updated['unidade'] ?? ''),
                            'atividade' => (string)$updated['atividade'],
                            'responsavel' => (string)($updated['responsavel'] ?? ''),
                            'tipo_evento' => (string)($updated['tipo_evento'] ?? ''),
                            'periodicidade' => (string)($updated['periodicidade'] ?? 'unico'),
                            'modelo' => (string)($updated['modelo'] ?? ''),
                            'status' => (string)($updated['status'] ?? ''),
                            'traffic' => $updated['traffic'] ?? null,
                        ],
                        'series' => [
                            'serie_id' => $serieId,
                            'traffic' => $row['traffic'] ?? CronogramaTrafficLight::series([]),
                            'months' => $row['meses'] ?? [],
                        ],
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $_SESSION['flash_success'] = $scope === 'serie'
                    ? 'Série atualizada com sucesso.'
                    : 'Evento atualizado com sucesso.';
            } catch (\Throwable $e) {
                if ($isAjax) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
        header('Location: ' . $url);
    }

    public function deleteEvento(): void
    {
        $this->requireClienteAdminAccess();
        $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $src['csrf'] ?? null;
            if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        }
        $id = (int)($src['id'] ?? 0);
        $idCronograma = (int)($src['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($src['status_filter'] ?? 'todos');
        $scope = (($src['escopo'] ?? 'evento') === 'serie') ? 'serie' : 'evento';
        $redirectUrl = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $src);
        if ($id) {
            try {
                $event = $this->eventos->find($id);
                if (!$event) {
                    throw new \RuntimeException('Evento não encontrado.');
                }
                $this->eventos->delete($id, $scope);
                $message = $scope === 'serie'
                    ? 'Serie excluida com sucesso.'
                    : 'Ocorrencia excluida com sucesso.';
                if ($isAjax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => true,
                        'message' => $message,
                        'deleted_id' => $id,
                        'scope' => $scope,
                        'redirect_url' => $redirectUrl,
                        'serie_id' => (int)($event['serie_id'] ?? $id),
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $_SESSION['flash_success'] = $message;
            } catch (\Throwable $e) {
                if ($isAjax) {
                    http_response_code(422);
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'ok' => false,
                        'message' => $e->getMessage(),
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        header('Location: ' . $redirectUrl);
    }

    private function buildGrid(array $events): array
    {
        $grid = [];
        foreach ($events as $ev) {
            $serieId = (int)($ev['serie_id'] ?? $ev['id']);
            if (!isset($grid[$serieId])) {
                $grid[$serieId] = [
                    'serie_id' => $serieId,
                    'topico' => $ev['topico'],
                    'unidade' => $ev['unidade'] ?? '',
                    'atividade' => $ev['atividade'],
                    'responsavel' => $ev['responsavel'] ?? '',
                    'periodicidade' => $ev['periodicidade'] ?? 'unico',
                    'meses' => array_fill(1, 12, ['marked' => false, 'count' => 0, 'events' => []]),
                ];
            }
            $month = (int)date('n', strtotime((string)$ev['data']));
            $grid[$serieId]['meses'][$month]['marked'] = true;
            $grid[$serieId]['meses'][$month]['count']++;
            $grid[$serieId]['meses'][$month]['events'][] = $ev;
        }
        foreach ($grid as &$row) {
            $row['traffic'] = CronogramaTrafficLight::series($row['meses'], new DateTimeImmutable('today'));
            $row['status'] = $row['traffic']['label'];
            for ($month = 1; $month <= 12; $month++) {
                $row['meses'][$month]['traffic'] = CronogramaTrafficLight::monthCell($row['meses'][$month]['events'] ?? [], new DateTimeImmutable('today'));
            }
        }
        unset($row);
        return $grid;
    }

    private function annotateEvents(array $events): array
    {
        $annotated = [];
        foreach ($events as $event) {
            $annotatedEvent = $this->annotateEvent($event);
            if ($annotatedEvent !== null) {
                $annotated[] = $annotatedEvent;
            }
        }
        return $annotated;
    }

    private function annotateEvent(?array $event): ?array
    {
        if (!$event) {
            return null;
        }
        $event['traffic'] = CronogramaTrafficLight::occurrence($event, new DateTimeImmutable('today'));
        return $event;
    }

    private function filterEventsByTraffic(array $events, string $statusFilter): array
    {
        if ($statusFilter === 'todos') {
            return $events;
        }
        return array_values(array_filter($events, static function (array $event) use ($statusFilter): bool {
            return (string)($event['traffic']['filter_key'] ?? 'pendente') === $statusFilter;
        }));
    }

    private function filterGridByTraffic(array $grid, string $statusFilter): array
    {
        if ($statusFilter === 'todos') {
            return $grid;
        }
        return array_values(array_filter($grid, static function (array $row) use ($statusFilter): bool {
            return (string)($row['traffic']['filter_key'] ?? 'pendente') === $statusFilter;
        }));
    }

    private function respondToggleStatus(array $payload, int $idCronograma, string $statusFilter, array $occParams = []): void
    {
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($payload, JSON_UNESCAPED_UNICODE);
            return;
        }
        $_SESSION[$payload['ok'] ? 'flash_success' : 'flash_error'] = $payload['message'] ?? ($payload['ok'] ? 'Status atualizado.' : 'Falha ao atualizar status.');
        $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $occParams);
        header('Location: ' . $url);
    }

    private function takeFlash(string $key): ?string
    {
        $value = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function buildCronogramaOrder(): array
    {
        $allowed = ['cliente', 'nome', 'ano'];
        $column = strtolower((string)($_GET['sort'] ?? ''));
        $direction = strtolower((string)($_GET['dir'] ?? ''));
        if (!in_array($column, $allowed, true)) {
            return ['column' => 'cliente', 'direction' => 'asc'];
        }
        return [
            'column' => $column,
            'direction' => $direction === 'desc' ? 'desc' : 'asc',
        ];
    }

    private function buildOcorrenciasFilters(): array
    {
        $filters = [
            'error' => null,
            'date_start' => trim((string)($_GET['occ_date_start'] ?? '')),
            'date_end' => trim((string)($_GET['occ_date_end'] ?? '')),
            'tipo' => array_values(array_unique(array_filter(array_map('trim', (array)($_GET['occ_tipo'] ?? []))))),
            'status' => array_values(array_unique(array_filter(array_map('trim', (array)($_GET['occ_status'] ?? []))))),
            'responsavel' => trim((string)($_GET['occ_responsavel'] ?? '')),
            'local' => trim((string)($_GET['occ_local'] ?? '')),
        ];
        if ($filters['date_start'] !== '' && $filters['date_end'] !== '' && $filters['date_start'] > $filters['date_end']) {
            $filters['error'] = 'O período inicial não pode ser maior que o período final.';
            $filters['date_start'] = '';
            $filters['date_end'] = '';
        }
        return $filters;
    }

    private function buildOcorrenciasOrder(): array
    {
        $allowed = ['data', 'topico', 'atividade', 'responsavel', 'periodicidade', 'status', 'unidade'];
        $column = strtolower((string)($_GET['occ_sort'] ?? ''));
        $direction = strtolower((string)($_GET['occ_dir'] ?? ''));
        if (!in_array($column, $allowed, true)) {
            return ['column' => 'data', 'direction' => 'asc'];
        }
        return [
            'column' => $column,
            'direction' => $direction === 'desc' ? 'desc' : 'asc',
        ];
    }

    private function buildGridOrder(): array
    {
        $allowed = ['topico', 'unidade', 'atividade', 'responsavel', 'status'];
        $column = strtolower((string)($_GET['grid_sort'] ?? ''));
        $direction = strtolower((string)($_GET['grid_dir'] ?? ''));
        if (!in_array($column, $allowed, true)) {
            return ['column' => 'topico', 'direction' => 'asc'];
        }
        return [
            'column' => $column,
            'direction' => $direction === 'desc' ? 'desc' : 'asc',
        ];
    }

    private function filterEventsByCriteria(array $events, array $filters): array
    {
        $start = $filters['date_start'] ?: null;
        $end = $filters['date_end'] ?: null;
        $tipos = $filters['tipo'] ?? [];
        $status = $filters['status'] ?? [];
        $responsavel = $filters['responsavel'] ?? '';
        $local = $filters['local'] ?? '';
        return array_values(array_filter($events, static function (array $event) use ($start, $end, $tipos, $status, $responsavel, $local): bool {
            $data = (string)($event['data'] ?? '');
            if ($start && $data < $start) {
                return false;
            }
            if ($end && $data > $end) {
                return false;
            }
            if (!empty($tipos) && !in_array((string)($event['tipo_evento'] ?? ''), $tipos, true)) {
                return false;
            }
            if (!empty($status)) {
                $label = (string)($event['traffic']['label'] ?? $event['status'] ?? '');
                if (!in_array($label, $status, true)) {
                    return false;
                }
            }
            if ($responsavel !== '' && stripos((string)($event['responsavel'] ?? ''), $responsavel) === false) {
                return false;
            }
            if ($local !== '' && stripos((string)($event['unidade'] ?? ''), $local) === false) {
                return false;
            }
            return true;
        }));
    }

    private function sortEvents(array $events, array $order): array
    {
        $column = $order['column'] ?? 'data';
        $direction = ($order['direction'] ?? 'asc') === 'desc' ? -1 : 1;
        $collator = class_exists('Collator') ? new \Collator('pt_BR') : null;
        $compareText = static function ($left, $right) use ($collator): int {
            return $collator ? $collator->compare((string)$left, (string)$right) : strcasecmp((string)$left, (string)$right);
        };
        usort($events, static function (array $a, array $b) use ($column, $direction, $compareText): int {
            if ($column === 'data') {
                $dataA = (string)($a['data'] ?? '');
                $dataB = (string)($b['data'] ?? '');
                $emptyA = $dataA === '';
                $emptyB = $dataB === '';
                if ($emptyA !== $emptyB) {
                    // Eventos sem data ficam sempre no final, independente da direção escolhida.
                    return $emptyA ? 1 : -1;
                }
                $result = strcmp($dataA, $dataB) * $direction;
            } else {
                $result = $compareText($a[$column] ?? '', $b[$column] ?? '') * $direction;
            }
            if ($result === 0) {
                $result = $compareText($a['atividade'] ?? '', $b['atividade'] ?? '');
            }
            if ($result === 0) {
                $result = ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
            }
            return $result;
        });
        return $events;
    }

    private function sortGridRows(array $grid, array $order): array
    {
        $column = $order['column'] ?? 'topico';
        $allowed = ['topico', 'unidade', 'atividade', 'responsavel', 'status'];
        if (!in_array($column, $allowed, true)) {
            $column = 'topico';
        }
        $direction = ($order['direction'] ?? 'asc') === 'desc' ? -1 : 1;
        $collator = class_exists('Collator') ? new \Collator('pt_BR') : null;
        usort($grid, static function (array $a, array $b) use ($column, $direction, $collator): int {
            $left = $a[$column] ?? '';
            $right = $b[$column] ?? '';
            $result = $collator ? $collator->compare((string)$left, (string)$right) : strcasecmp((string)$left, (string)$right);
            return $result * $direction;
        });
        return $grid;
    }

    private function buildOcorrenciasOptions(array $events): array
    {
        $tipos = [];
        $responsaveis = [];
        $locais = [];
        $status = [];
        foreach ($events as $event) {
            $tipos[] = (string)($event['tipo_evento'] ?? '');
            $responsaveis[] = (string)($event['responsavel'] ?? '');
            $locais[] = (string)($event['unidade'] ?? '');
            $statusLabel = (string)($event['traffic']['label'] ?? $event['status'] ?? '');
            $status[] = $statusLabel;
        }
        $tipos = array_values(array_filter(array_unique($tipos)));
        $responsaveis = array_values(array_filter(array_unique($responsaveis)));
        $locais = array_values(array_filter(array_unique($locais)));
        $status = array_values(array_filter(array_unique($status)));
        $this->sortOptions($tipos);
        $this->sortOptions($responsaveis);
        $this->sortOptions($locais);
        $this->sortOptions($status);
        return [
            'tipos' => $tipos,
            'responsaveis' => $responsaveis,
            'locais' => $locais,
            'status' => $status,
        ];
    }

    private function sortOptions(array &$items): void
    {
        if (class_exists('Collator')) {
            $collator = new \Collator('pt_BR');
            usort($items, static fn(string $a, string $b): int => $collator->compare($a, $b));
            return;
        }
        natcasesort($items);
        $items = array_values($items);
    }

    private function buildOccRedirectUrl(int $idCronograma, string $statusFilter, array $source): string
    {
        $params = $this->buildOccQueryParams($source);
        $base = 'index.php?route=cronograma/show&id=' . $idCronograma . '&status_filter=' . urlencode($statusFilter);
        if (!empty($params)) {
            $base .= '&' . http_build_query($params);
        }
        return $base;
    }

    private function buildOccQueryParams(array $source): array
    {
        $params = [];
        $dateStart = trim((string)($source['occ_date_start'] ?? ''));
        $dateEnd = trim((string)($source['occ_date_end'] ?? ''));
        $tipos = array_values(array_unique(array_filter(array_map('trim', (array)($source['occ_tipo'] ?? [])))));
        $status = array_values(array_unique(array_filter(array_map('trim', (array)($source['occ_status'] ?? [])))));
        $responsavel = trim((string)($source['occ_responsavel'] ?? ''));
        $local = trim((string)($source['occ_local'] ?? ''));
        $sort = trim((string)($source['occ_sort'] ?? ''));
        $dir = trim((string)($source['occ_dir'] ?? ''));
        if ($dateStart !== '') $params['occ_date_start'] = $dateStart;
        if ($dateEnd !== '') $params['occ_date_end'] = $dateEnd;
        if (!empty($tipos)) $params['occ_tipo'] = $tipos;
        if (!empty($status)) $params['occ_status'] = $status;
        if ($responsavel !== '') $params['occ_responsavel'] = $responsavel;
        if ($local !== '') $params['occ_local'] = $local;
        if ($sort !== '') $params['occ_sort'] = $sort;
        if ($dir !== '') $params['occ_dir'] = $dir;
        return $params;
    }
}
