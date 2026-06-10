<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Security;
use App\Core\XlsxExport;
use App\Models\ColaboradorModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;
use App\Services\ColaboradorImportService;

class ColaboradoresController extends BaseController
{
    private ColaboradorModel $colabs;
    private FuncaoModel $funcoes;
    private SetorModel $setores;
    private DepartamentoModel $deps;

    public function __construct()
    {
        $this->colabs = new ColaboradorModel();
        $this->funcoes = new FuncaoModel();
        $this->setores = new SetorModel();
        $this->deps = new DepartamentoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0) {
            $cliente = (int)($this->resolveScopedClienteId($cliente) ?? 0);
        }
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per']) ? max(5, min(100, (int)$_GET['per'])) : 20;
        $lider = isset($_GET['lider']) && in_array($_GET['lider'], ['sim','não'], true) ? $_GET['lider'] : '';
        $departamentoId = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
        $setorId = isset($_GET['setor']) ? (int)$_GET['setor'] : 0;
        $funcaoId = isset($_GET['funcao']) ? (int)$_GET['funcao'] : 0;
        $unidadeId = isset($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : 0;
        $status = trim((string)($_GET['status'] ?? ''));
        $allFuncionarios = !empty($_GET['all_funcionarios']) && (string)$_GET['all_funcionarios'] !== '0';
        $filters = [
            'lider' => $lider,
            'departamento_id' => $departamentoId ?: null,
            'setor_id' => $setorId ?: null,
            'funcao_id' => $funcaoId ?: null,
            'status' => $status !== '' ? $status : null,
        ];

        $clienteModel = new ClienteModel();
        $selectedCliente = $cliente > 0 ? $clienteModel->find($cliente) : null;
        if ($cliente > 0 && !$selectedCliente) {
            $cliente = 0;
        }

        $scopeClienteIds = [];
        $canAllFuncionarios = false;
        $catalogClienteId = 0;
        if ($cliente > 0 && $selectedCliente) {
            $groupRootId = $clienteModel->catalogRootIdFor($cliente);
            $catalogClienteId = $groupRootId;
            $filiais = $groupRootId > 0 ? $clienteModel->filiaisByMatriz($groupRootId) : [];
            $scopeClienteIds = array_values(array_unique(array_merge(
                [$groupRootId],
                array_map(static fn(array $row): int => (int)$row['id'], $filiais)
            )));
            $canAllFuncionarios = count($scopeClienteIds) > 1;
            if (!$allFuncionarios || !$canAllFuncionarios) {
                $scopeClienteIds = [$cliente];
                $allFuncionarios = false;
            }
        } else {
            $allFuncionarios = false;
        }

        if (!empty($scopeClienteIds) && $unidadeId > 0 && in_array($unidadeId, $scopeClienteIds, true)) {
            $scopeClienteIds = [$unidadeId];
        } else {
            $unidadeId = 0;
        }

        $items = !empty($scopeClienteIds) ? $this->colabs->paginatedByClientesWithFilters($scopeClienteIds, $page, $perPage, $filters) : [];
        $total = !empty($scopeClienteIds) ? $this->colabs->countByClientesWithFilters($scopeClienteIds, $filters) : 0;
        $totalPages = !empty($scopeClienteIds) ? max(1, (int)ceil($total / $perPage)) : 1;

        if (!empty($scopeClienteIds)) {
            $effectiveCatalogId = $catalogClienteId > 0 ? $catalogClienteId : (int)($scopeClienteIds[0] ?? 0);
            $departamentos = $effectiveCatalogId > 0 ? $this->deps->activeByCliente($effectiveCatalogId) : [];
            $setores = $departamentoId > 0 ? $this->setores->activeByDepartamento($departamentoId) : ($effectiveCatalogId > 0 ? $this->setores->activeByCliente($effectiveCatalogId) : []);
            $funcoes = $setorId > 0 ? $this->funcoes->activeBySetor($setorId, [$effectiveCatalogId]) : ($effectiveCatalogId > 0 ? $this->funcoes->activeByCliente($effectiveCatalogId) : []);
        } else {
            $departamentos = $this->deps->all();
            $setores = $this->setores->all();
            $funcoes = [];
        }

        $unidades = !empty($scopeClienteIds) ? $clienteModel->byIds($scopeClienteIds) : [];
        $statusOptions = !empty($scopeClienteIds) ? $this->colabs->statusOptionsByClientes($scopeClienteIds) : [];

        $clientes = (new ClienteModel())->all();
        $this->render('colaboradores/index', [
            'items' => $items,
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
            'cliente' => $cliente,
            'clientes' => $clientes,
            'unidades' => $unidades,
            'status_options' => $statusOptions,
            'page' => $page,
            'per' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'filter_lider' => $lider,
            'filter_departamento' => $departamentoId,
            'filter_setor' => $setorId,
            'filter_funcao' => $funcaoId,
            'filter_unidade' => $unidadeId,
            'filter_status' => $status,
            'filter_all_funcionarios' => $allFuncionarios,
            'can_all_funcionarios' => $canAllFuncionarios
        ]);
    }

    public function filterAjax(): void
    {
        $this->requireLogin();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0) {
            $cliente = (int)($this->resolveScopedClienteId($cliente) ?? 0);
        }
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per']) ? max(5, min(100, (int)$_GET['per'])) : 20;
        $lider = isset($_GET['lider']) && in_array($_GET['lider'], ['sim','não'], true) ? $_GET['lider'] : '';
        $departamentoId = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
        $setorId = isset($_GET['setor']) ? (int)$_GET['setor'] : 0;
        $funcaoId = isset($_GET['funcao']) ? (int)$_GET['funcao'] : 0;
        $unidadeId = isset($_GET['unidade_id']) ? (int)$_GET['unidade_id'] : 0;
        $status = trim((string)($_GET['status'] ?? ''));
        $allFuncionarios = !empty($_GET['all_funcionarios']) && (string)$_GET['all_funcionarios'] !== '0';

        $clienteModel = new ClienteModel();
        $selectedCliente = $cliente > 0 ? $clienteModel->find($cliente) : null;
        if ($cliente > 0 && !$selectedCliente) {
            echo json_encode(['ok' => true, 'options' => [], 'items' => [], 'rows_html' => $this->renderColaboradoresRows([] ,0), 'page' => 1, 'total' => 0, 'total_pages' => 1], JSON_UNESCAPED_UNICODE);
            return;
        }

        $scopeClienteIds = [];
        $canAllFuncionarios = false;
        $catalogClienteId = 0;
        if ($cliente > 0 && $selectedCliente) {
            $groupRootId = $clienteModel->catalogRootIdFor($cliente);
            $catalogClienteId = $groupRootId;
            $filiais = $groupRootId > 0 ? $clienteModel->filiaisByMatriz($groupRootId) : [];
            $scopeClienteIds = array_values(array_unique(array_merge(
                [$groupRootId],
                array_map(static fn(array $row): int => (int)$row['id'], $filiais)
            )));
            $canAllFuncionarios = count($scopeClienteIds) > 1;
            if (!$allFuncionarios || !$canAllFuncionarios) {
                $scopeClienteIds = [$cliente];
                $allFuncionarios = false;
            }
        } else {
            $allFuncionarios = false;
        }

        if (empty($scopeClienteIds)) {
            echo json_encode(['ok' => true, 'options' => [], 'items' => [], 'rows_html' => $this->renderColaboradoresRows([] ,0), 'page' => 1, 'total' => 0, 'total_pages' => 1], JSON_UNESCAPED_UNICODE);
            return;
        }

        $unidades = $clienteModel->byIds($scopeClienteIds);
        if ($unidadeId > 0 && in_array($unidadeId, $scopeClienteIds, true)) {
            $scopeClienteIds = [$unidadeId];
        } else {
            $unidadeId = 0;
        }

        $effectiveCatalogId = $catalogClienteId > 0 ? $catalogClienteId : (int)($scopeClienteIds[0] ?? 0);

        if ($departamentoId > 0) {
            $dep = $this->deps->find($departamentoId);
            if (!$dep || (int)($dep['cliente_id'] ?? 0) !== $effectiveCatalogId) {
                $departamentoId = 0;
                $setorId = 0;
                $funcaoId = 0;
            }
        }
        if ($setorId > 0) {
            $set = $this->setores->find($setorId);
            if (!$set) {
                $setorId = 0;
                $funcaoId = 0;
            } else {
                $dep = $this->deps->find((int)($set['departamento_id'] ?? 0));
                $setCliente = $dep ? (int)($dep['cliente_id'] ?? 0) : 0;
                if ($setCliente <= 0 || $setCliente !== $effectiveCatalogId || ($departamentoId > 0 && (int)($set['departamento_id'] ?? 0) !== $departamentoId)) {
                    $setorId = 0;
                    $funcaoId = 0;
                }
            }
        }
        if ($funcaoId > 0) {
            $f = $this->funcoes->find($funcaoId);
            if (!$f || ($setorId > 0 && (int)($f['setor_id'] ?? 0) !== $setorId)) {
                $funcaoId = 0;
            } elseif ($setorId === 0) {
                $set = $this->setores->find((int)($f['setor_id'] ?? 0));
                if (!$set) {
                    $funcaoId = 0;
                } else {
                    $dep = $this->deps->find((int)($set['departamento_id'] ?? 0));
                    if (!$dep || (int)($dep['cliente_id'] ?? 0) !== $effectiveCatalogId) {
                        $funcaoId = 0;
                    }
                }
            } else {
                $set = $this->setores->find((int)($f['setor_id'] ?? 0));
                $dep = $set ? $this->deps->find((int)($set['departamento_id'] ?? 0)) : null;
                if (!$dep || (int)($dep['cliente_id'] ?? 0) !== $effectiveCatalogId) {
                    $funcaoId = 0;
                }
            }
        }

        $filters = [
            'lider' => $lider,
            'departamento_id' => $departamentoId ?: null,
            'setor_id' => $setorId ?: null,
            'funcao_id' => $funcaoId ?: null,
            'status' => $status !== '' ? $status : null,
        ];

        $items = $this->colabs->paginatedByClientesWithFilters($scopeClienteIds, $page, $perPage, $filters);
        $total = $this->colabs->countByClientesWithFilters($scopeClienteIds, $filters);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        if ($page !== (int)($_GET['page'] ?? $page)) {
            $items = $this->colabs->paginatedByClientesWithFilters($scopeClienteIds, $page, $perPage, $filters);
        }

        $departamentos = $effectiveCatalogId > 0 ? $this->deps->activeByCliente($effectiveCatalogId) : [];
        $setores = $departamentoId > 0 ? $this->setores->activeByDepartamento($departamentoId) : ($effectiveCatalogId > 0 ? $this->setores->activeByCliente($effectiveCatalogId) : []);
        $funcoes = $setorId > 0 ? $this->funcoes->activeBySetor($setorId, [$effectiveCatalogId]) : ($effectiveCatalogId > 0 ? $this->funcoes->activeByCliente($effectiveCatalogId) : []);

        $statusOptions = $this->colabs->statusOptionsByClientes($scopeClienteIds);

        echo json_encode([
            'ok' => true,
            'page' => $page,
            'per' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'filters' => [
                'cliente' => $cliente,
                'all_funcionarios' => $allFuncionarios ? 1 : 0,
                'can_all_funcionarios' => $canAllFuncionarios ? 1 : 0,
                'unidade_id' => $unidadeId,
                'lider' => $lider,
                'departamento' => $departamentoId,
                'setor' => $setorId,
                'funcao' => $funcaoId,
                'status' => $status,
            ],
            'options' => [
                'unidades' => $unidades,
                'departamentos' => $departamentos,
                'setores' => $setores,
                'funcoes' => $funcoes,
                'status' => $statusOptions,
            ],
            'rows_html' => $this->renderColaboradoresRows($items, $cliente),
            'summary' => 'Total: ' . (int)$total . ' • Página ' . (int)$page . ' de ' . (int)$totalPages,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function renderColaboradoresRows(array $items, int $cliente): string
    {
        $e = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        if (empty($items)) {
            return '<tr data-empty-row><td class="p-3 text-sm text-gray-600" colspan="7">Nenhum colaborador encontrado para os filtros selecionados.</td></tr>';
        }
        $html = '';
        foreach ($items as $c) {
            $id = (int)($c['id'] ?? 0);
            $html .= '<tr class="border-b">';
            $html .= '<td class="p-3">' . $e((string)($c['nome'] ?? '')) . '</td>';
            $html .= '<td class="p-3">' . $e((string)($c['email'] ?? '')) . '</td>';
            $html .= '<td class="p-3">' . $e((string)($c['unidade'] ?? '')) . '</td>';
            $html .= '<td class="p-3">' . $e((string)($c['funcao'] ?? '')) . '</td>';
            $html .= '<td class="p-3">' . $e((string)($c['setor'] ?? '')) . '</td>';
            $html .= '<td class="p-3">' . $e((string)($c['departamento'] ?? '')) . '</td>';
            $html .= '<td class="p-3 whitespace-nowrap">';
            $html .= '<a class="text-brand-pink icon-action" href="index.php?route=colaboradores/edit&id=' . $id . ($cliente ? '&cliente=' . (int)$cliente : '') . '" title="Editar" aria-label="Editar"><span data-feather="edit"></span></a>';
            $html .= '<a class="text-brand-brown icon-action ml-2" href="index.php?route=colaboradores/delete&id=' . $id . ($cliente ? '&cliente=' . (int)$cliente : '') . '" title="Excluir" aria-label="Excluir"><span data-feather="trash-2"></span></a>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        return $html;
    }

    public function search(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $q = trim($_GET['q'] ?? '');
        $items = [];
        if ($cliente) {
            if ($q !== '') {
                $items = (new \App\Models\ColaboradorModel())->searchByClienteName($cliente, $q, 200);
            } else {
                $items = (new \App\Models\ColaboradorModel())->allByCliente($cliente);
            }
        }
        echo json_encode($items);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0) {
            $cliente = (int)($this->resolveScopedClienteId($cliente) ?? 0);
        }
        $catalogClienteId = $cliente > 0 ? $this->catalogClienteIdForCliente($cliente) : 0;
        $departamentos = $catalogClienteId > 0 ? $this->deps->activeByCliente($catalogClienteId) : [];
        $setores = $catalogClienteId > 0 ? $this->setores->activeByCliente($catalogClienteId) : [];
        $funcoes = $catalogClienteId > 0 ? $this->funcoes->activeByCliente($catalogClienteId) : [];
        $this->render('colaboradores/create', [
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
            'cliente' => $cliente,
            'selected_departamento_id' => 0,
            'selected_setor_id' => 0,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $funcaoId = (int)($_POST['funcao_id'] ?? 0);
        $lider = ($_POST['lider'] ?? 'não') === 'sim' ? 'sim' : 'não';
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($cliente > 0) {
            $cliente = (int)($this->resolveScopedClienteId($cliente) ?? 0);
        }
        $catalogClienteId = $cliente > 0 ? $this->catalogClienteIdForCliente($cliente) : 0;
        if (!$nome || $cliente <= 0 || $funcaoId <= 0) {
            $_SESSION['flash_error'] = 'Preencha os campos obrigatórios.';
            if (!headers_sent()) {
                header('Location: index.php?route=colaboradores/create' . ($cliente ? '&cliente=' . $cliente : ''));
            }
            return;
        }
        if ($catalogClienteId <= 0 || !$this->funcaoBelongsToCatalog($funcaoId, $catalogClienteId)) {
            $_SESSION['flash_error'] = 'Função inválida para a empresa selecionada.';
            if (!headers_sent()) {
                header('Location: index.php?route=colaboradores/create&cliente=' . $cliente);
            }
            return;
        }
        $this->colabs->create(['nome' => $nome, 'email' => $email, 'funcao_id' => $funcaoId, 'lider' => $lider, 'cliente_id' => $cliente]);
        if (!headers_sent()) {
            header('Location: index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . $cliente : ''));
        }
        return;
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->colabs->find($id);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente <= 0 && $item) {
            $cliente = (int)($item['cliente_id'] ?? 0);
        }
        if ($cliente > 0) {
            $cliente = (int)($this->resolveScopedClienteId($cliente) ?? 0);
        }
        $catalogClienteId = $cliente > 0 ? $this->catalogClienteIdForCliente($cliente) : 0;
        $departamentos = $catalogClienteId > 0 ? $this->deps->activeByCliente($catalogClienteId) : [];
        $setores = $catalogClienteId > 0 ? $this->setores->activeByCliente($catalogClienteId) : [];
        $funcoes = $catalogClienteId > 0 ? $this->funcoes->activeByCliente($catalogClienteId) : [];
        $path = $item ? $this->resolveFuncaoPathIds((int)($item['funcao_id'] ?? 0)) : ['departamento_id' => 0, 'setor_id' => 0];

        if ($item && (int)($item['funcao_id'] ?? 0) > 0) {
            $currentFuncaoId = (int)$item['funcao_id'];
            $hasCurrentInList = false;
            foreach ($funcoes as $f) {
                if ((int)($f['id'] ?? 0) === $currentFuncaoId) {
                    $hasCurrentInList = true;
                    break;
                }
            }
            if (!$hasCurrentInList) {
                $setorId = (int)($path['setor_id'] ?? 0);
                $depId = (int)($path['departamento_id'] ?? 0);
                $allowedGroupClienteIds = $catalogClienteId > 0 ? array_values(array_unique(array_merge([$catalogClienteId], array_map(static fn(array $r): int => (int)$r['id'], (new ClienteModel())->filiaisByMatriz($catalogClienteId))))) : [];
                $dep = $depId > 0 ? $this->deps->find($depId) : null;
                $depClienteId = (int)($dep['cliente_id'] ?? 0);
                if ($dep && ($catalogClienteId <= 0 || in_array($depClienteId, $allowedGroupClienteIds, true))) {
                    $hasDepInList = false;
                    foreach ($departamentos as $d) {
                        if ((int)($d['id'] ?? 0) === $depId) {
                            $hasDepInList = true;
                            break;
                        }
                    }
                    if (!$hasDepInList) {
                        $departamentos[] = $dep;
                    }
                    $set = $setorId > 0 ? $this->setores->find($setorId) : null;
                    if ($set) {
                        $hasSetInList = false;
                        foreach ($setores as $s) {
                            if ((int)($s['id'] ?? 0) === $setorId) {
                                $hasSetInList = true;
                                break;
                            }
                        }
                        if (!$hasSetInList) {
                            $setores[] = [
                                'id' => (int)$set['id'],
                                'nome' => (string)($set['nome'] ?? ''),
                                'departamento_id' => (int)($set['departamento_id'] ?? 0),
                                'departamento' => (string)($dep['nome'] ?? ''),
                            ];
                        }
                    }
                    $func = $this->funcoes->find($currentFuncaoId);
                    if ($func) {
                        $funcoes[] = [
                            'id' => (int)$func['id'],
                            'nome' => (string)($func['nome'] ?? ''),
                            'setor_id' => (int)($func['setor_id'] ?? 0),
                            'setor' => (string)($set['nome'] ?? ''),
                            'departamento' => (string)($dep['nome'] ?? ''),
                        ];
                    }
                }
            }
        }
        $this->render('colaboradores/edit', [
            'item' => $item,
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
            'cliente' => $cliente,
            'selected_departamento_id' => (int)($path['departamento_id'] ?? 0),
            'selected_setor_id' => (int)($path['setor_id'] ?? 0),
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $current = $id > 0 ? $this->colabs->find($id) : null;
        if ($id <= 0 || !$current) {
            $_SESSION['flash_error'] = 'Colaborador não encontrado.';
            header('Location: index.php?route=colaboradores/index');
            return;
        }
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $funcaoId = (int)($_POST['funcao_id'] ?? 0);
        $lider = ($_POST['lider'] ?? 'não') === 'sim' ? 'sim' : 'não';
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($cliente > 0) {
            $cliente = (int)($this->resolveScopedClienteId($cliente) ?? 0);
        }
        $catalogClienteId = $cliente > 0 ? $this->catalogClienteIdForCliente($cliente) : 0;
        $isCatalogFuncao = $catalogClienteId > 0 && $this->funcaoBelongsToCatalog($funcaoId, $catalogClienteId);
        $isKeepingLegacyFuncao = !$isCatalogFuncao && (int)($current['funcao_id'] ?? 0) > 0 && (int)($current['funcao_id'] ?? 0) === $funcaoId;
        if ($cliente > 0 && $funcaoId > 0 && ($isCatalogFuncao || $isKeepingLegacyFuncao)) {
            $this->colabs->update($id, ['nome' => $nome, 'email' => $email, 'funcao_id' => $funcaoId, 'lider' => $lider, 'cliente_id' => $cliente]);
        } else {
            $_SESSION['flash_error'] = 'Dados inválidos para salvar o colaborador.';
            if (!headers_sent()) {
                header('Location: index.php?route=colaboradores/edit&id=' . $id . ($cliente ? '&cliente=' . $cliente : ''));
            }
            return;
        }
        if (!headers_sent()) {
            header('Location: index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . $cliente : ''));
        }
        return;
    }

    private function catalogClienteIdForCliente(int $clienteId): int
    {
        return (new ClienteModel())->catalogRootIdFor((int)$clienteId);
    }

    private function funcaoBelongsToCatalog(int $funcaoId, int $catalogClienteId): bool
    {
        $funcaoId = (int)$funcaoId;
        $catalogClienteId = (int)$catalogClienteId;
        if ($funcaoId <= 0 || $catalogClienteId <= 0) {
            return false;
        }
        $f = $this->funcoes->find($funcaoId);
        if (!$f) {
            return false;
        }
        if (isset($f['ativo']) && (int)$f['ativo'] !== 1) {
            return false;
        }
        $set = $this->setores->find((int)($f['setor_id'] ?? 0));
        if (!$set) {
            return false;
        }
        if (isset($set['ativo']) && (int)$set['ativo'] !== 1) {
            return false;
        }
        $dep = $this->deps->find((int)($set['departamento_id'] ?? 0));
        if (!$dep) {
            return false;
        }
        if (isset($dep['ativo']) && (int)$dep['ativo'] !== 1) {
            return false;
        }
        return (int)($dep['cliente_id'] ?? 0) === $catalogClienteId;
    }

    private function resolveFuncaoPathIds(int $funcaoId): array
    {
        $funcaoId = (int)$funcaoId;
        if ($funcaoId <= 0) {
            return ['departamento_id' => 0, 'setor_id' => 0];
        }
        $f = $this->funcoes->find($funcaoId);
        if (!$f) {
            return ['departamento_id' => 0, 'setor_id' => 0];
        }
        $setorId = (int)($f['setor_id'] ?? 0);
        $set = $setorId > 0 ? $this->setores->find($setorId) : null;
        if (!$set) {
            return ['departamento_id' => 0, 'setor_id' => 0];
        }
        return [
            'departamento_id' => (int)($set['departamento_id'] ?? 0),
            'setor_id' => $setorId,
        ];
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($id) { $this->colabs->delete($id); }
        header('Location: index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function importTemplate(): void
    {
        $this->requireLogin();
        $format = strtolower(trim((string)($_GET['format'] ?? 'xlsx')));
        if (!in_array($format, ['xlsx', 'csv'], true)) {
            $format = 'xlsx';
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $service = new ColaboradorImportService();
        $def = $service->templateDefinition($userId);
        $columns = (array)($def['columns'] ?? []);
        $rows = is_array($def['rows'] ?? null) ? $def['rows'] : [];

        $normalizedRows = [];
        $keys = array_keys($columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $k) {
                $line[$k] = isset($row[$k]) ? (string)$row[$k] : '';
            }
            $normalizedRows[] = $line;
        }

        $baseName = 'modelo_importacao_colaboradores_' . date('Ymd_His');

        AuditLogger::log('download_template', 'colaboradores', 0, [
            'format' => $format,
            'usuario_id' => $userId,
        ]);

        if ($format === 'csv') {
            $fh = fopen('php://temp', 'wb+');
            if ($fh === false) {
                http_response_code(500);
                echo 'Não foi possível gerar o arquivo.';
                return;
            }
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, array_values($columns), ';', '"', '\\');
            foreach ($normalizedRows as $row) {
                $vals = [];
                foreach ($keys as $k) {
                    $vals[] = (string)($row[$k] ?? '');
                }
                fputcsv($fh, $vals, ';', '"', '\\');
            }
            rewind($fh);
            $csv = stream_get_contents($fh);
            fclose($fh);
            if ($csv === false) {
                http_response_code(500);
                echo 'Não foi possível gerar o arquivo.';
                return;
            }
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $baseName . '.csv"');
            header('X-Content-Type-Options: nosniff');
            echo $csv;
            return;
        }

        $orientationColumns = (array)($def['orientation_columns'] ?? []);
        $orientationRows = is_array($def['orientation_rows'] ?? null) ? $def['orientation_rows'] : [];

        $tmpPath = XlsxExport::exportTemplateWorkbook([
            [
                'name' => 'Modelo',
                'columns' => $columns,
                'rows' => $normalizedRows,
            ],
            [
                'name' => 'Orientações',
                'columns' => $orientationColumns,
                'rows' => $orientationRows,
            ],
        ], $baseName . '.xlsx', [
            'report_title' => 'Modelo de Importação - Colaboradores',
        ]);

        $content = @file_get_contents($tmpPath);
        @unlink($tmpPath);
        if ($content === false) {
            http_response_code(500);
            echo 'Não foi possível gerar o arquivo.';
            return;
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $baseName . '.xlsx"');
        header('X-Content-Type-Options: nosniff');
        echo $content;
    }

    public function import(): void
    {
        $this->requireLogin();
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Método inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Método inválido.';
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'CSRF inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'CSRF inválido.';
            return;
        }

        $file = $_FILES['arquivo'] ?? null;
        if (!is_array($file) || empty($file['name'])) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo obrigatório.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo obrigatório.';
            return;
        }
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            http_response_code(400);
            $msg = 'Falha no upload do arquivo.';
            if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                $msg = 'Arquivo excede o limite permitido pelo servidor.';
            } elseif ($err === UPLOAD_ERR_PARTIAL) {
                $msg = 'Upload incompleto. Tente novamente.';
            } elseif ($err === UPLOAD_ERR_NO_TMP_DIR) {
                $msg = 'Servidor sem diretório temporário para upload.';
            } elseif ($err === UPLOAD_ERR_CANT_WRITE) {
                $msg = 'Servidor não conseguiu gravar o arquivo.';
            } elseif ($err === UPLOAD_ERR_EXTENSION) {
                $msg = 'Upload bloqueado por extensão no servidor.';
            } elseif ($err === UPLOAD_ERR_NO_FILE) {
                $msg = 'Arquivo obrigatório.';
            }
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo $msg;
            return;
        }
        if (!is_uploaded_file((string)($file['tmp_name'] ?? ''))) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo inválido.';
            return;
        }
        $sizeBytes = (int)($file['size'] ?? 0);
        if ($sizeBytes <= 0) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo inválido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo inválido.';
            return;
        }
        if ($sizeBytes > (50 * 1024 * 1024)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Arquivo excede o limite de 50MB.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Arquivo excede o limite de 50MB.';
            return;
        }
        $name = (string)$file['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xls', 'xlsx'], true)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Formato inválido. Use CSV, XLS ou XLSX.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Formato inválido. Use CSV, XLS ou XLSX.';
            return;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $service = new ColaboradorImportService();
        $result = $service->import((string)$file['tmp_name'], $name, $userId);

        if (!empty($result['ok'])) {
            $inserted = (int)($result['inserted'] ?? 0);
            $clienteIds = array_values(array_unique(array_filter(array_map('intval', (array)($result['cliente_ids'] ?? [])))));
            AuditLogger::log('import', 'colaboradores', 0, [
                'via' => 'upload',
                'arquivo' => $name,
                'tamanho' => $sizeBytes,
                'inserted' => $inserted,
                'cliente_ids' => $clienteIds,
                'usuario_id' => $userId,
            ]);
            $_SESSION['flash_success'] = 'Importação concluída. Inseridos: ' . $inserted;
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => true,
                    'inserted' => $inserted,
                    'message' => 'Importação concluída. Inseridos: ' . $inserted,
                    'cliente_ids' => $clienteIds,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            header('Location: index.php?route=colaboradores/index');
            return;
        }

        AuditLogger::log('import_failed', 'colaboradores', 0, [
            'via' => 'upload',
            'arquivo' => $name,
            'tamanho' => $sizeBytes,
            'errors_count' => is_array($result['errors'] ?? null) ? count($result['errors']) : null,
            'usuario_id' => $userId,
        ]);

        http_response_code(400);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'message' => 'Falha na importação.', 'errors' => $result['errors'] ?? []], JSON_UNESCAPED_UNICODE);
            return;
        }
        $_SESSION['flash_error'] = 'Falha na importação.';
        header('Location: index.php?route=colaboradores/index');
    }
}
