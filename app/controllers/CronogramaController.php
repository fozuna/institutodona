<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\CronogramaTrafficLight;
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

    public function __construct()
    {
        $this->cronogramas = new CronogramaModel();
        $this->eventos = new CronogramaEventoModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $pref = (int)($_GET['id_cliente'] ?? 0);
        $this->render('cronograma/create', ['clientes' => $clientes, 'pref' => $pref]);
    }

    public function store(): void
    {
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $this->render('cronograma/select_cliente', ['clientes' => $clientes]);
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_GET['status_filter'] ?? 'todos');
        $filters = $this->buildOcorrenciasFilters();
        $order = $this->buildOcorrenciasOrder();
        $allEvents = $this->eventos->byCronograma($id);
        $annotatedEvents = $this->annotateEvents($allEvents);
        $grid = $this->buildGrid($annotatedEvents);
        $events = $this->filterEventsByTraffic($annotatedEvents, $statusFilter);
        $grid = $this->filterGridByTraffic($grid, $statusFilter);
        $events = $this->filterEventsByCriteria($events, $filters);
        $events = $this->sortEvents($events, $order);
        $grid = $this->sortGridRows($grid, $order);
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
            'occOptions' => $occOptions,
            'totalEvents' => count($allEvents),
            'occFilterError' => $filters['error'] ?? null,
            'flashSuccess' => $this->takeFlash('flash_success'),
            'flashError' => $this->takeFlash('flash_error'),
        ]);
    }

    public function addEvento(): void
    {
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
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

    public function toggleStatus(): void
    {
        $this->requireRole('instituto');
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
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $crono = $this->cronogramas->find($id);
        $pilares = (new PilarModel())->all();
        $this->render('cronograma/add_evento', [
            'crono' => $crono,
            'periodicidades' => self::PERIODICIDADES,
            'pilares' => $pilares,
        ]);
    }

    public function updateEvento(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id_evento'] ?? 0);
        $idCronograma = (int)($_POST['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($_POST['status_filter'] ?? 'todos');
        $data = [
            'data' => $_POST['data'] ?? null,
            'topico' => trim($_POST['topico'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'atividade' => trim($_POST['atividade'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'modelo' => $_POST['modelo'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
            'periodicidade' => $_POST['periodicidade'] ?? 'unico',
        ];
        $scope = ($_POST['escopo'] ?? 'evento') === 'serie' ? 'serie' : 'evento';
        if ($id) {
            try {
                $this->eventos->update($id, $data, $scope);
                $_SESSION['flash_success'] = $scope === 'serie'
                    ? 'Serie atualizada com sucesso.'
                    : 'Ocorrencia atualizada com sucesso.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $_POST);
        header('Location: ' . $url);
    }

    public function deleteEvento(): void
    {
        $this->requireRole('instituto');
        $src = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $src['csrf'] ?? null;
            if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        }
        $id = (int)($src['id'] ?? 0);
        $idCronograma = (int)($src['id_cronograma'] ?? 0);
        $statusFilter = CronogramaTrafficLight::normalizeFilter($src['status_filter'] ?? 'todos');
        $scope = (($src['escopo'] ?? 'evento') === 'serie') ? 'serie' : 'evento';
        if ($id) {
            try {
                $this->eventos->delete($id, $scope);
                $_SESSION['flash_success'] = $scope === 'serie'
                    ? 'Serie excluida com sucesso.'
                    : 'Ocorrencia excluida com sucesso.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        $url = $this->buildOccRedirectUrl($idCronograma, $statusFilter, $src);
        header('Location: ' . $url);
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
            if (!empty($tipos) && !in_array((string)($event['topico'] ?? ''), $tipos, true)) {
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
        usort($events, static function (array $a, array $b) use ($column, $direction, $collator): int {
            $left = $a[$column] ?? '';
            $right = $b[$column] ?? '';
            if ($column === 'data') {
                $result = strcmp((string)$left, (string)$right);
            } elseif ($collator) {
                $result = $collator->compare((string)$left, (string)$right);
            } else {
                $result = strcasecmp((string)$left, (string)$right);
            }
            if ($result === 0) {
                $result = strcmp((string)($a['data'] ?? ''), (string)($b['data'] ?? ''));
            }
            return $result * $direction;
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
            $tipos[] = (string)($event['topico'] ?? '');
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
