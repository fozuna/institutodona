<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\I18n;
use App\Core\PdfSupport;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\ColaboradorModel;
use App\Models\DepartamentoModel;
use App\Models\IndicadorEventoModel;
use App\Models\IndicadorModel;
use App\Models\SetorModel;
use App\Models\UnidadeMedidaModel;

class IndicadoresController extends BaseController
{
    private IndicadorModel $model;
    private ClienteModel $clientes;
    private DepartamentoModel $departamentos;
    private SetorModel $setores;
    private ColaboradorModel $colaboradores;
    private UnidadeMedidaModel $unidades;
    private IndicadorEventoModel $eventos;

    public function __construct()
    {
        $this->model = new IndicadorModel();
        $this->clientes = new ClienteModel();
        $this->departamentos = new DepartamentoModel();
        $this->setores = new SetorModel();
        $this->colaboradores = new ColaboradorModel();
        $this->unidades = new UnidadeMedidaModel();
        $this->eventos = new IndicadorEventoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $clientes = $this->clientes->all();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        $dateStart = trim((string)($_GET['date_start'] ?? ''));
        $dateEnd = trim((string)($_GET['date_end'] ?? ''));

        if ($dateStart !== '' && $dateEnd !== '' && $dateEnd < $dateStart) {
            if ($this->isAjaxRequest()) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'A data final não pode ser anterior à data inicial.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_error'] = 'A data final não pode ser anterior à data inicial.';
            $dateStart = '';
            $dateEnd = '';
        }

        $items = $this->model->search([
            'cliente_id' => $cliente > 0 ? $cliente : 0,
            'q' => $q,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
        ]);

        if ($this->isAjaxRequest()) {
            header('Content-Type: text/html; charset=utf-8');
            $t = static fn(string $key, array $replace = []): string => I18n::t($key, $replace);
            $formatValue = static function ($value, array $item): string {
                return \App\Core\ValueFormatter::byUnit($value, [
                    'simbolo' => $item['unidade_simbolo'] ?? '',
                    'tipo' => $item['unidade_tipo'] ?? '',
                ]);
            };
            require __DIR__ . '/../views/indicadores/_cards.php';
            return;
        }

        $this->render('indicadores/index', [
            'pageTitle' => I18n::t('indicadores.title.index'),
            'clientes' => $clientes,
            'cliente' => $cliente,
            'items' => $items,
            'q' => $q,
            'dateStart' => $dateStart,
            'dateEnd' => $dateEnd,
            'i18n' => I18n::class,
        ]);
    }

    public function autocomplete(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        header('Content-Type: application/json; charset=utf-8');
        if ($cliente <= 0 || $q === '') {
            echo json_encode(['ok' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        try {
            $items = $this->model->autocomplete($cliente, $q, 12);
            echo json_encode(['ok' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Não foi possível carregar sugestões agora.'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $formData = $this->model->defaultPayload();
        if ($cliente > 0) {
            $formData['cliente_id'] = $cliente;
            $formData['cliente_nome'] = (string)($this->clientes->find($cliente)['nome_empresa'] ?? '');
        }
        $this->renderForm('create', $formData, []);
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $formData = $this->model->sanitize($this->requestData());
        $errors = $this->model->validate($formData);
        if ($errors) {
            $this->renderForm('create', $formData, $errors);
            return;
        }
        $id = $this->model->create($formData, $this->currentUserId());
        $_SESSION['flash_success'] = I18n::t('indicadores.flash.created');
        $clienteId = (int)($formData['cliente_id'] ?? 0);
        $this->redirect('index.php?route=indicadores/index' . ($clienteId > 0 ? '&cliente=' . $clienteId : ''));
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        if (!$item) {
            $_SESSION['flash_error'] = I18n::t('indicadores.flash.not_found');
            $this->redirect('index.php?route=indicadores/index');
        }
        $this->renderForm('edit', $item, [], $item);
    }

    public function charts(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $filters = $this->readEventoFilters($cliente);
        $indicadorId = (int)($filters['indicador_id'] ?? 0);
        $clientes = $this->clientes->all();
        $eventos = [];
        if ($cliente > 0) {
            if (!$filters['period_ok']) {
                $_SESSION['flash_error'] = $filters['period_error'] ?: 'Selecione um período de apuração válido.';
            } else {
                $eventos = $this->eventos->searchByCliente($cliente, $filters);
            }
        }
        $series = [];
        foreach ($eventos as $evento) {
            $key = (string)($evento['indicador'] ?? '');
            if ($key === '') {
                continue;
            }
            if (!isset($series[$key])) {
                $series[$key] = [
                    'indicador_id' => (int)$evento['indicador_id'],
                    'trend' => $this->eventos->indicatorHistorySummary((int)$evento['indicador_id']),
                    'unit' => [
                        'simbolo' => $evento['unidade_simbolo'] ?? '',
                        'tipo' => $evento['unidade_tipo'] ?? '',
                    ],
                    'points' => [],
                ];
            }
            $series[$key]['points'][] = [
                'date' => (string)$evento['data_evento'],
                'meta' => (float)$evento['valor_meta'],
                'achieved' => $evento['valor_atingido'] !== null ? (float)$evento['valor_atingido'] : null,
                'percentual' => $evento['percentual_cumprimento'] !== null ? (float)$evento['percentual_cumprimento'] : null,
                'status' => (string)$evento['meta_status_key'],
            ];
        }
        foreach ($series as &$serie) {
            usort($serie['points'], static fn(array $a, array $b): int => strcmp((string)$a['date'], (string)$b['date']));
        }
        unset($serie);
        $indicadores = $cliente ? $this->model->byCliente($cliente) : [];
        $this->render('indicadores/charts', [
            'pageTitle' => I18n::t('indicadores.title.charts'),
            'cliente' => $cliente,
            'clientes' => $clientes,
            'series' => $series,
            'indicadorId' => $indicadorId,
            'periodoInicio' => (string)($filters['periodo_inicio'] ?? ''),
            'periodoFim' => (string)($filters['periodo_fim'] ?? ''),
            'indicadores' => $indicadores,
            'i18n' => I18n::class,
        ]);
    }

    public function realizado(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $filters = $this->readEventoFilters($cliente);
        $indicadorId = (int)($filters['indicador_id'] ?? 0);
        $clientes = $this->clientes->all();
        $items = [];
        if ($cliente > 0) {
            if (!$filters['period_ok']) {
                $items = [];
            } else {
                $items = $this->eventos->searchByCliente($cliente, $filters);
            }
        }
        $indicadores = $cliente ? $this->model->byCliente($cliente) : [];

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            if ($cliente > 0 && !$filters['period_ok']) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => false,
                    'message' => $filters['period_error'] ?: 'Selecione um período de apuração válido.',
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            header('Content-Type: application/json; charset=utf-8');
            $tableHtml = $this->renderRealizadoTable($items, $cliente, $filters);
            echo json_encode([
                'ok' => true,
                'table_html' => $tableHtml,
                'count' => count($items),
            ], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($cliente > 0 && !$filters['period_ok']) {
            $_SESSION['flash_error'] = $filters['period_error'] ?: 'Selecione um período de apuração válido.';
        }
        $this->render('indicadores/realizado', [
            'pageTitle' => I18n::t('indicadores.title.value'),
            'clientes' => $clientes,
            'cliente' => $cliente,
            'items' => $items,
            'indicadorId' => $indicadorId,
            'periodoInicio' => (string)($filters['periodo_inicio'] ?? ''),
            'periodoFim' => (string)($filters['periodo_fim'] ?? ''),
            'indicadores' => $indicadores,
            'i18n' => I18n::class,
        ]);
    }

    public function painel(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $filters = $this->readEventoFilters($cliente);
        $filters['ano'] = $ano;
        $indicadorId = (int)($filters['indicador_id'] ?? 0);
        $clientes = $this->clientes->all();
        $items = [];
        if ($cliente > 0) {
            if (!$filters['period_ok']) {
                $_SESSION['flash_error'] = $filters['period_error'] ?: 'Selecione um período de apuração válido.';
                $items = [];
            } else {
                $items = $this->eventos->searchByCliente($cliente, $filters);
            }
        }
        $stats = ['total' => count($items), 'atingida' => 0, 'parcial' => 0, 'nao_atingida' => 0, 'pendente' => 0];
        foreach ($items as $item) {
            $key = (string)($item['meta_status_key'] ?? 'pendente');
            if (!isset($stats[$key])) {
                $stats[$key] = 0;
            }
            $stats[$key]++;
        }
        $indicadores = $cliente ? $this->model->byCliente($cliente) : [];
        $this->render('indicadores/painel', [
            'pageTitle' => I18n::t('indicadores.title.dashboard'),
            'cliente' => $cliente,
            'clientes' => $clientes,
            'ano' => $ano,
            'items' => $items,
            'stats' => $cliente ? $stats : ['total' => 0, 'atingida' => 0, 'parcial' => 0, 'nao_atingida' => 0, 'pendente' => 0],
            'indicadorId' => $indicadorId,
            'periodoInicio' => (string)($filters['periodo_inicio'] ?? ''),
            'periodoFim' => (string)($filters['periodo_fim'] ?? ''),
            'indicadores' => $indicadores,
            'i18n' => I18n::class,
        ]);
    }

    public function evento(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $evento = $this->eventos->find($id);
        if (!$evento) {
            $_SESSION['flash_error'] = I18n::t('indicadores.flash.event_not_found');
            $this->redirect('index.php?route=indicadores/index');
        }
        $history = $this->eventos->byIndicador((int)$evento['indicador_id']);
        $summary = $this->eventos->indicatorHistorySummary((int)$evento['indicador_id']);
        $this->render('indicadores/evento', [
            'pageTitle' => I18n::t('indicadores.title.event'),
            'evento' => $evento,
            'history' => $history,
            'summary' => $summary,
            'i18n' => I18n::class,
        ]);
    }

    public function historico(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        if (!$item) {
            $_SESSION['flash_error'] = I18n::t('indicadores.flash.not_found');
            $this->redirect('index.php?route=indicadores/index');
        }
        $history = $this->eventos->byIndicador($id);
        $summary = $this->eventos->indicatorHistorySummary($id);
        $this->render('indicadores/historico', [
            'pageTitle' => I18n::t('indicadores.title.history'),
            'item' => $item,
            'history' => $history,
            'summary' => $summary,
            'i18n' => I18n::class,
        ]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $current = $this->model->find($id);
        if (!$current) {
            $_SESSION['flash_error'] = I18n::t('indicadores.flash.not_found');
            $this->redirect('index.php?route=indicadores/index');
        }
        $formData = $this->model->sanitize($this->requestData());
        $errors = $this->model->validate($formData, $id);
        if ($errors) {
            $formData['id'] = $id;
            $this->renderForm('edit', $formData, $errors, $current);
            return;
        }
        $this->model->update($id, $formData, $this->currentUserId());
        $_SESSION['flash_success'] = I18n::t('indicadores.flash.updated');
        $this->redirect('index.php?route=indicadores/edit&id=' . $id);
    }

    public function updateRealizado(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['evento_id'] ?? ($_POST['id'] ?? 0));
        $cliente = (int)($this->resolveScopedClienteId((int)($_POST['cliente'] ?? 0)) ?? 0);
        $indicadorId = (int)($_POST['indicador_id'] ?? 0);
        $periodoInicio = trim((string)($_POST['periodo_inicio'] ?? ''));
        $periodoFim = trim((string)($_POST['periodo_fim'] ?? ''));
        if (($periodoInicio === '') xor ($periodoFim === '')) {
            $periodoInicio = '';
            $periodoFim = '';
        }
        $redirect = 'index.php?route=indicadores/realizado&cliente=' . $cliente
            . ($indicadorId > 0 ? '&indicador_id=' . $indicadorId : '')
            . ($periodoInicio !== '' && $periodoFim !== '' ? '&periodo_inicio=' . urlencode($periodoInicio) . '&periodo_fim=' . urlencode($periodoFim) : '');
        if ($id <= 0 || !$this->eventos->updateAchievedValue($id, $_POST['valor'] ?? null, $this->currentUserId(), $_POST['observacao'] ?? null)) {
            $_SESSION['flash_error'] = I18n::t('indicadores.validation.invalid_value_update');
            $this->redirect($redirect);
        }
        $_SESSION['flash_success'] = I18n::t('indicadores.flash.value_updated');
        if (!empty($_POST['redirect_evento'])) {
            $this->redirect('index.php?route=indicadores/evento&id=' . $id);
        }
        $this->redirect($redirect);
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
        $cliente = (int)($this->resolveScopedClienteId((int)($_POST['cliente'] ?? 0)) ?? 0);
        if ($id <= 0 || !$this->model->softDelete($id, $this->currentUserId())) {
            $_SESSION['flash_error'] = I18n::t('indicadores.validation.invalid_delete');
            $this->redirect('index.php?route=indicadores/index' . ($cliente ? '&cliente=' . $cliente : ''));
        }
        $_SESSION['flash_success'] = I18n::t('indicadores.flash.deleted');
        $this->redirect('index.php?route=indicadores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function updateValorAjax(): void
    {
        $this->requireLogin();
        if (!(Auth::isInstituto() || Auth::isClienteAdmin())) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sem permissão para alterar valores.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Método inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $valor = $_POST['valor'] ?? null;
        if ($id <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Indicador inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $item = $this->model->find($id);
        if (!$item) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => I18n::t('indicadores.flash.not_found')], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!Auth::canAccessCliente((int)($item['cliente_id'] ?? 0))) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sem permissão para este cliente.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->model->updateValor($id, $valor, $this->currentUserId());
        if (!$ok) {
            AuditLogger::log('indicadores_update_valor_failed', 'indicadores', $id, [
                'cliente_id' => (int)($item['cliente_id'] ?? 0),
                'valor' => is_scalar($valor) ? (string)$valor : gettype($valor),
            ]);
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => I18n::t('indicadores.validation.invalid_value_update')], JSON_UNESCAPED_UNICODE);
            return;
        }
        $fresh = $this->model->find($id) ?: $item;
        $formatted = \App\Core\ValueFormatter::byUnit($fresh['valor'], [
            'simbolo' => $fresh['unidade_simbolo'] ?? '',
            'tipo' => $fresh['unidade_tipo'] ?? '',
        ]);
        AuditLogger::log('indicadores_update_valor', 'indicadores', $id, [
            'cliente_id' => (int)($fresh['cliente_id'] ?? 0),
            'valor' => $fresh['valor'],
        ]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'valor' => $fresh['valor'],
            'valor_formatado' => $formatted,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function deleteAjax(): void
    {
        $this->requireLogin();
        if (!(Auth::isInstituto() || Auth::isClienteAdmin())) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sem permissão para excluir.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Método inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Indicador inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $item = $this->model->find($id);
        if (!$item) {
            http_response_code(404);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => I18n::t('indicadores.flash.not_found')], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!Auth::canAccessCliente((int)($item['cliente_id'] ?? 0))) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Sem permissão para este cliente.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $ok = $this->model->softDelete($id, $this->currentUserId());
        if (!$ok) {
            AuditLogger::log('indicadores_delete_failed', 'indicadores', $id, [
                'cliente_id' => (int)($item['cliente_id'] ?? 0),
            ]);
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => I18n::t('indicadores.validation.invalid_delete')], JSON_UNESCAPED_UNICODE);
            return;
        }
        AuditLogger::log('delete', 'indicadores', $id, [
            'cliente_id' => (int)($item['cliente_id'] ?? 0),
            'via' => 'ajax',
        ]);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    public function chartsPdf(): void
    {
        $this->requireLogin();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Método inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!PdfSupport::isDompdfAvailable()) {
            $errorId = PdfSupport::newErrorId();
            AuditLogger::log('pdf_unavailable', 'indicadores', null, [
                'error_id' => $errorId,
                'route' => 'indicadores/chartsPdf',
                'reason' => 'dompdf_missing',
                'diagnostics' => PdfSupport::dompdfDiagnostics(),
            ]);
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => PdfSupport::missingDompdfMessage(), 'code' => $errorId], JSON_UNESCAPED_UNICODE);
            return;
        }

        $clienteId = (int)($this->resolveScopedClienteId((int)($_POST['cliente_id'] ?? 0)) ?? 0);
        $clienteNome = trim((string)($_POST['cliente_nome'] ?? ''));
        $payload = json_decode((string)($_POST['charts'] ?? '[]'), true);
        if (!is_array($payload) || empty($payload)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Nenhum gráfico selecionado para exportação.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (count($payload) > 12) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Selecione no máximo 12 gráficos para exportação.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $totalBytes = 0;
        foreach ($payload as $i => $chart) {
            $img = (string)($chart['image'] ?? '');
            if ($img === '' || !str_starts_with($img, 'data:image/png;base64,')) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Imagem inválida do gráfico.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $base64 = substr($img, strlen('data:image/png;base64,'));
            $decoded = base64_decode($base64, true);
            if ($decoded === false) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Imagem inválida do gráfico.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            $totalBytes += strlen($decoded);
            if ($totalBytes > (8 * 1024 * 1024)) {
                http_response_code(422);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Exportação muito grande. Reduza a quantidade de gráficos.'], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $safeCliente = $clienteNome !== '' ? htmlspecialchars($clienteNome) : '';
        $generatedAt = date('d/m/Y H:i');

        $html = '<!doctype html><html><head><meta charset="utf-8" />'
            . '<style>
              body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
              .header { margin-bottom: 14px; }
              .title { font-size: 18px; font-weight: 700; margin: 0 0 4px 0; }
              .subtitle { font-size: 12px; color: #6b7280; margin: 0; }
              .grid { width: 100%; }
              .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin: 0 0 12px 0; }
              .card h2 { font-size: 14px; margin: 0 0 6px 0; }
              .meta { color: #374151; font-size: 11px; margin: 0 0 8px 0; }
              .kv { font-size: 11px; color: #374151; margin: 0 0 6px 0; }
              .kv strong { color: #111827; }
              img { width: 100%; height: auto; }
            </style></head><body>';

        $html .= '<div class="header">'
            . '<div class="title">Gráficos de Indicadores</div>'
            . '<p class="subtitle">Cliente: ' . ($safeCliente !== '' ? $safeCliente : '—') . ' · Gerado em: ' . htmlspecialchars($generatedAt) . '</p>'
            . '</div>';

        foreach ($payload as $chart) {
            $titulo = htmlspecialchars((string)($chart['title'] ?? 'Indicador'));
            $trend = htmlspecialchars((string)($chart['trend'] ?? ''));
            $meta = htmlspecialchars((string)($chart['meta'] ?? '—'));
            $ach = htmlspecialchars((string)($chart['achieved'] ?? '—'));
            $img = (string)($chart['image'] ?? '');

            $html .= '<div class="card">'
                . '<h2>' . $titulo . '</h2>'
                . ($trend !== '' ? '<div class="meta">' . $trend . '</div>' : '')
                . '<div class="kv"><strong>Meta:</strong> ' . $meta . ' &nbsp;&nbsp; <strong>Atingido:</strong> ' . $ach . '</div>'
                . '<img src="' . $img . '" alt="" />'
                . '</div>';
        }
        $html .= '</body></html>';

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        AuditLogger::log('pdf_export', 'indicadores', null, [
            'via' => 'charts',
            'cliente_id' => $clienteId,
            'items' => count($payload),
        ]);

        $filename = 'graficos_indicadores_' . date('Ymd_His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dompdf->output();
    }

    public function apiClientes(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->clientes->searchActiveByName($q, 12)], JSON_UNESCAPED_UNICODE);
    }

    public function apiDepartamentos(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        if ($cliente <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->departamentos->activeByCliente($cliente)], JSON_UNESCAPED_UNICODE);
    }

    public function apiSetores(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $departamento = (int)($_GET['departamento_id'] ?? 0);
        if ($departamento <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->setores->activeByDepartamento($departamento)], JSON_UNESCAPED_UNICODE);
    }

    public function apiResponsaveis(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $cliente = (int)($this->resolveScopedClienteId((int)($_GET['cliente_id'] ?? 0)) ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        if ($cliente <= 0) {
            echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(['success' => true, 'items' => $this->colaboradores->searchActiveByCliente($cliente, $q, 15)], JSON_UNESCAPED_UNICODE);
    }

    private function readEventoFilters(int $clienteId): array
    {
        $default = [
            'indicador_id' => 0,
            'periodo_inicio' => '',
            'periodo_fim' => '',
            'has_explicit' => false,
            'period_ok' => false,
            'period_error' => '',
        ];
        if ($clienteId <= 0) {
            return $default;
        }
        $key = '__indicadores_event_filters';
        $stored = $_SESSION[$key][$clienteId] ?? $default;

        $hasExplicit = array_key_exists('indicador_id', $_GET)
            || array_key_exists('periodo_inicio', $_GET)
            || array_key_exists('periodo_fim', $_GET)
            || array_key_exists('clear_filters', $_GET);
        if (!$hasExplicit) {
            $out = is_array($stored) ? array_merge($default, $stored) : $default;
            $out['has_explicit'] = false;
            $period = $this->validatePeriodoApuracao((string)($out['periodo_inicio'] ?? ''), (string)($out['periodo_fim'] ?? ''));
            $out['period_ok'] = $period['ok'];
            $out['period_error'] = $period['error'];
            return $out;
        }

        if ((string)($_GET['clear_filters'] ?? '') === '1') {
            $storedReset = $default;
            unset($storedReset['has_explicit'], $storedReset['period_ok'], $storedReset['period_error']);
            $_SESSION[$key][$clienteId] = $storedReset;
            $out = $default;
            $out['has_explicit'] = true;
            return $out;
        }

        $indicadorId = isset($_GET['indicador_id']) ? (int)$_GET['indicador_id'] : 0;
        $periodoInicio = trim((string)($_GET['periodo_inicio'] ?? ''));
        $periodoFim = trim((string)($_GET['periodo_fim'] ?? ''));
        $period = $this->validatePeriodoApuracao($periodoInicio, $periodoFim);

        $filters = [
            'indicador_id' => $indicadorId > 0 ? $indicadorId : 0,
            'periodo_inicio' => $period['inicio'],
            'periodo_fim' => $period['fim'],
            'has_explicit' => true,
            'period_ok' => $period['ok'],
            'period_error' => $period['error'],
        ];
        $_SESSION[$key][$clienteId] = [
            'indicador_id' => $filters['indicador_id'],
            'periodo_inicio' => $filters['periodo_inicio'],
            'periodo_fim' => $filters['periodo_fim'],
        ];
        return $filters;
    }

    private function validatePeriodoApuracao(string $inicio, string $fim): array
    {
        $inicio = trim($inicio);
        $fim = trim($fim);
        if ($inicio === '' || $fim === '') {
            return [
                'ok' => false,
                'inicio' => '',
                'fim' => '',
                'error' => 'Período de apuração é obrigatório. Selecione data de início e fim.',
            ];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fim)) {
            return [
                'ok' => false,
                'inicio' => '',
                'fim' => '',
                'error' => 'Período de apuração inválido. Selecione datas válidas.',
            ];
        }
        $ini = \DateTimeImmutable::createFromFormat('Y-m-d', $inicio) ?: null;
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $fim) ?: null;
        if (!$ini || !$end) {
            return [
                'ok' => false,
                'inicio' => '',
                'fim' => '',
                'error' => 'Período de apuração inválido. Selecione datas válidas.',
            ];
        }
        if ($end < $ini) {
            return [
                'ok' => false,
                'inicio' => $inicio,
                'fim' => $fim,
                'error' => 'A data final do período não pode ser anterior à data inicial.',
            ];
        }
        return [
            'ok' => true,
            'inicio' => $inicio,
            'fim' => $fim,
            'error' => '',
        ];
    }

    private function renderPeriodoOptions(array $periodos, string $selectedInicio, string $selectedFim): string
    {
        $html = '<option value="">' . htmlspecialchars(I18n::t('indicadores.option.none')) . '</option>';
        foreach ($periodos as $p) {
            $inicio = (string)($p['periodo_inicio'] ?? '');
            $fim = (string)($p['periodo_fim'] ?? '');
            if ($inicio === '' || $fim === '') {
                continue;
            }
            $value = $inicio . '|' . $fim;
            $selected = ($inicio === $selectedInicio && $fim === $selectedFim) ? ' selected' : '';
            $label = htmlspecialchars($inicio . ' até ' . $fim);
            $html .= '<option value="' . htmlspecialchars($value) . '"' . $selected . '>' . $label . '</option>';
        }
        return $html;
    }

    private function renderRealizadoTable(array $items, int $clienteId, array $filters): string
    {
        $cliente = $clienteId;
        $indicadorId = (int)($filters['indicador_id'] ?? 0);
        $periodoInicio = (string)($filters['periodo_inicio'] ?? '');
        $periodoFim = (string)($filters['periodo_fim'] ?? '');
        $renderOnlyTable = true;
        ob_start();
        require __DIR__ . '/../views/indicadores/realizado.php';
        return (string)ob_get_clean();
    }

    private function renderForm(string $view, array $formData, array $errors, ?array $item = null): void
    {
        $formData = $this->model->sanitize($formData);
        $clienteId = (int)($formData['cliente_id'] ?? 0);
        $departamentoId = (int)($formData['departamento_id'] ?? 0);
        $responsavelIds = (array)($formData['responsavel_ids'] ?? []);
        if ($clienteId > 0 && ($formData['cliente_nome'] ?? '') === '') {
            $formData['cliente_nome'] = (string)($this->clientes->find($clienteId)['nome_empresa'] ?? '');
        }
        $selectedResponsaveis = $clienteId > 0 ? $this->colaboradores->activeByIdsCliente($responsavelIds, $clienteId) : [];
        $this->render('indicadores/' . $view, [
            'pageTitle' => $view === 'create' ? I18n::t('indicadores.title.create') : I18n::t('indicadores.title.edit'),
            'formData' => $formData,
            'errors' => $errors,
            'item' => $item,
            'departamentos' => $clienteId > 0 ? $this->departamentos->activeByCliente($clienteId) : [],
            'setores' => $departamentoId > 0 ? $this->setores->activeByDepartamento($departamentoId) : [],
            'unidades' => $this->unidades->activeAll(),
            'responsaveisSelecionados' => $selectedResponsaveis,
            'periodicidades' => $this->model->periodicidades(),
            'i18n' => I18n::class,
        ]);
    }

    private function requestData(): array
    {
        return [
            'cliente_id' => $_POST['cliente_id'] ?? 0,
            'cliente_nome' => $_POST['cliente_nome'] ?? '',
            'indicador' => $_POST['indicador'] ?? '',
            'departamento_id' => $_POST['departamento_id'] ?? 0,
            'setor_id' => $_POST['setor_id'] ?? 0,
            'responsavel_ids' => $_POST['responsavel_ids'] ?? [],
            'periodicidade_tipo' => $_POST['periodicidade_tipo'] ?? 'mensal',
            'data_inicial' => $_POST['data_inicial'] ?? '',
            'data_final' => $_POST['data_final'] ?? '',
            'valor' => $_POST['valor'] ?? '',
            'unidade_medida_id' => $_POST['unidade_medida_id'] ?? 0,
            'valor_minimo' => $_POST['valor_minimo'] ?? '',
            'valor_maximo' => $_POST['valor_maximo'] ?? '',
        ];
    }

    private function currentUserId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    private function isAjaxRequest(): bool
    {
        if ((string)($_GET['ajax'] ?? '') === '1') {
            return true;
        }
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
