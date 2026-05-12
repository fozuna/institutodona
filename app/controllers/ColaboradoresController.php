<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Security;
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
        $funcaoId = isset($_GET['funcao']) ? (int)$_GET['funcao'] : 0;
        $allFuncionarios = !empty($_GET['all_funcionarios']) && (string)$_GET['all_funcionarios'] !== '0';
        $filters = [
            'lider' => $lider,
            'departamento_id' => $departamentoId ?: null,
            'funcao_id' => $funcaoId ?: null,
        ];

        $clienteModel = new ClienteModel();
        $selectedCliente = $cliente > 0 ? $clienteModel->find($cliente) : null;
        if ($cliente > 0 && !$selectedCliente) {
            $cliente = 0;
        }

        $scopeClienteIds = [];
        $canAllFuncionarios = false;
        if ($cliente > 0 && $selectedCliente) {
            $matrizId = (int)($selectedCliente['matriz_id'] ?? 0);
            $isMatriz = $matrizId <= 0 && (int)($selectedCliente['is_matriz'] ?? 1) === 1;
            $groupRootId = $isMatriz ? $cliente : $matrizId;
            if ($groupRootId <= 0) {
                $groupRootId = $cliente;
            }
            if ($groupRootId !== $cliente && !$clienteModel->find($groupRootId)) {
                $groupRootId = $cliente;
            }
            $filiais = $clienteModel->filiaisByMatriz($groupRootId);
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

        $items = !empty($scopeClienteIds) ? $this->colabs->paginatedByClientesWithFilters($scopeClienteIds, $page, $perPage, $filters) : [];
        $total = !empty($scopeClienteIds) ? $this->colabs->countByClientesWithFilters($scopeClienteIds, $filters) : 0;
        $totalPages = !empty($scopeClienteIds) ? max(1, (int)ceil($total / $perPage)) : 1;

        if (!empty($scopeClienteIds)) {
            if (count($scopeClienteIds) > 1) {
                $departamentos = $this->deps->allByClientes($scopeClienteIds);
                $setores = $this->setores->allByClientes($scopeClienteIds);
                $funcoes = $this->funcoes->allByClientes($scopeClienteIds);
            } else {
                $departamentos = $this->deps->allByCliente($scopeClienteIds[0]);
                $setores = $this->setores->allByCliente($scopeClienteIds[0]);
                $funcoes = $this->funcoes->allByCliente($scopeClienteIds[0]);
            }
        } else {
            $departamentos = $this->deps->all();
            $setores = $this->setores->all();
            $funcoes = [];
        }

        $clientes = (new ClienteModel())->all();
        $this->render('colaboradores/index', [
            'items' => $items,
            'departamentos' => $departamentos,
            'setores' => $setores,
            'funcoes' => $funcoes,
            'cliente' => $cliente,
            'clientes' => $clientes,
            'page' => $page,
            'per' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'filter_lider' => $lider,
            'filter_departamento' => $departamentoId,
            'filter_funcao' => $funcaoId,
            'filter_all_funcionarios' => $allFuncionarios,
            'can_all_funcionarios' => $canAllFuncionarios
        ]);
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
        $funcoes = $cliente ? $this->funcoes->allByCliente($cliente) : [];
        $this->render('colaboradores/create', ['funcoes' => $funcoes, 'cliente' => $cliente]);
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
        if ($nome && $funcaoId) { $this->colabs->create(['nome' => $nome, 'email' => $email, 'funcao_id' => $funcaoId, 'lider' => $lider, 'cliente_id' => $cliente]); }
        header('Location: index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->colabs->find($id);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $funcoes = $cliente ? $this->funcoes->allByCliente($cliente) : [];
        $this->render('colaboradores/edit', ['item' => $item, 'funcoes' => $funcoes, 'cliente' => $cliente]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $funcaoId = (int)($_POST['funcao_id'] ?? 0);
        $lider = ($_POST['lider'] ?? 'não') === 'sim' ? 'sim' : 'não';
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($id) { $this->colabs->update($id, ['nome' => $nome, 'email' => $email, 'funcao_id' => $funcaoId, 'lider' => $lider, 'cliente_id' => $cliente]); }
        header('Location: index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($id) { $this->colabs->delete($id); }
        header('Location: index.php?route=colaboradores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function import(): void
    {
        $this->requireLogin();
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if (!(Auth::isInstituto() || Auth::isClienteAdmin())) {
            http_response_code(403);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Sem permissão para importar colaboradores.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo 'Sem permissão para importar colaboradores.';
            return;
        }
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
            AuditLogger::log('import', 'colaboradores', 0, [
                'via' => 'upload',
                'arquivo' => $name,
                'tamanho' => $sizeBytes,
                'inserted' => (int)($result['inserted'] ?? 0),
                'usuario_id' => $userId,
            ]);
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'inserted' => (int)($result['inserted'] ?? 0)], JSON_UNESCAPED_UNICODE);
                return;
            }
            $_SESSION['flash_success'] = 'Importação concluída. Inseridos: ' . (int)($result['inserted'] ?? 0);
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
