<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\I18n;
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
        $clientes = $this->clientes->all();
        $eventos = $cliente ? $this->eventos->byCliente($cliente) : [];
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
        $this->render('indicadores/charts', [
            'pageTitle' => I18n::t('indicadores.title.charts'),
            'cliente' => $cliente,
            'clientes' => $clientes,
            'series' => $series,
            'i18n' => I18n::class,
        ]);
    }

    public function realizado(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $clientes = $this->clientes->all();
        $items = $cliente ? $this->eventos->byCliente($cliente) : [];
        $this->render('indicadores/realizado', [
            'pageTitle' => I18n::t('indicadores.title.value'),
            'clientes' => $clientes,
            'cliente' => $cliente,
            'items' => $items,
            'i18n' => I18n::class,
        ]);
    }

    public function painel(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $clientes = $this->clientes->all();
        $items = $cliente ? $this->eventos->byCliente($cliente, $ano) : [];
        $stats = ['total' => count($items), 'atingida' => 0, 'parcial' => 0, 'nao_atingida' => 0, 'pendente' => 0];
        foreach ($items as $item) {
            $key = (string)($item['meta_status_key'] ?? 'pendente');
            if (!isset($stats[$key])) {
                $stats[$key] = 0;
            }
            $stats[$key]++;
        }
        $this->render('indicadores/painel', [
            'pageTitle' => I18n::t('indicadores.title.dashboard'),
            'cliente' => $cliente,
            'clientes' => $clientes,
            'ano' => $ano,
            'items' => $items,
            'stats' => $cliente ? $stats : ['total' => 0, 'atingida' => 0, 'parcial' => 0, 'nao_atingida' => 0, 'pendente' => 0],
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
        if ($id <= 0 || !$this->eventos->updateAchievedValue($id, $_POST['valor'] ?? null, $this->currentUserId(), $_POST['observacao'] ?? null)) {
            $_SESSION['flash_error'] = I18n::t('indicadores.validation.invalid_value_update');
            $this->redirect('index.php?route=indicadores/realizado&cliente=' . $cliente);
        }
        $_SESSION['flash_success'] = I18n::t('indicadores.flash.value_updated');
        if (!empty($_POST['redirect_evento'])) {
            $this->redirect('index.php?route=indicadores/evento&id=' . $id);
        }
        $this->redirect('index.php?route=indicadores/realizado&cliente=' . $cliente);
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
