<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Security;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;

class FuncoesController extends BaseController
{
    private FuncaoModel $funcoes;
    private SetorModel $setores;
    private DepartamentoModel $deps;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->funcoes = new FuncaoModel();
        $this->setores = new SetorModel();
        $this->deps = new DepartamentoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = $this->resolveClienteSelecionado(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null);
        if ($cliente > 0) {
            $items = $this->funcoes->allByCliente($cliente);
            $departamentos = $this->deps->allByCliente($cliente);
            $setores = $this->setores->allByCliente($cliente);
        } elseif (Auth::isInstituto()) {
            $items = $this->funcoes->allByClientes($this->clienteIdsVisiveis());
            $departamentos = $this->deps->allByClientes($this->clienteIdsVisiveis());
            $setores = $this->setores->allByClientes($this->clienteIdsVisiveis());
        } else {
            $items = [];
            $departamentos = [];
            $setores = [];
        }
        $this->render('funcoes/index', ['items' => $items, 'departamentos' => $departamentos, 'setores' => $setores, 'cliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = $this->resolveClienteSelecionado(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null);
        $selectedSetorId = isset($_GET['setor_id']) ? (int)$_GET['setor_id'] : 0;
        if ($cliente > 0) {
            $setores = $this->setores->allByCliente($cliente);
            $departamentos = $this->deps->allByCliente($cliente);
        } else {
            $setores = [];
            $departamentos = [];
        }
        $mapDepartamentos = [];
        foreach ($departamentos as $d) {
            $mapDepartamentos[(int)($d['id'] ?? 0)] = (string)($d['nome'] ?? '');
        }
        $this->render('funcoes/create', [
            'setores' => $setores,
            'cliente' => $cliente,
            'selectedSetorId' => $selectedSetorId,
            'mapDepartamentos' => $mapDepartamentos,
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $nome = trim($_POST['nome'] ?? '');
        $setorId = (int)($_POST['setor_id'] ?? 0);
        $cliente = $this->resolveClienteSelecionado(isset($_POST['cliente']) ? (int)$_POST['cliente'] : null);
        if ($nome && $setorId) { $this->funcoes->create(['nome' => $nome, 'setor_id' => $setorId]); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->funcoes->find($id);
        $cliente = $this->resolveClienteSelecionado(isset($_GET['cliente']) ? (int)$_GET['cliente'] : (($item['cliente_id'] ?? 0) ?: null));
        if ($cliente > 0) {
            $setores = $this->setores->allByCliente($cliente);
            $departamentos = $this->deps->allByCliente($cliente);
        } else {
            $setores = [];
            $departamentos = [];
        }
        $mapDepartamentos = [];
        foreach ($departamentos as $d) {
            $mapDepartamentos[(int)($d['id'] ?? 0)] = (string)($d['nome'] ?? '');
        }
        $this->render('funcoes/edit', [
            'item' => $item,
            'setores' => $setores,
            'cliente' => $cliente,
            'mapDepartamentos' => $mapDepartamentos,
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $setorId = (int)($_POST['setor_id'] ?? 0);
        $cliente = $this->resolveClienteSelecionado(isset($_POST['cliente']) ? (int)$_POST['cliente'] : null);
        if ($id) { $this->funcoes->update($id, ['nome' => $nome, 'setor_id' => $setorId]); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $cliente = $this->resolveClienteSelecionado(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null);
        if ($id) { $this->funcoes->delete($id); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    private function resolveClienteSelecionado(?int $requestedClienteId): int
    {
        $scoped = $this->resolveScopedClienteId($requestedClienteId !== null && $requestedClienteId > 0 ? $requestedClienteId : null);
        if ($scoped !== null && $scoped > 0) {
            return (int)$scoped;
        }
        return Auth::isInstituto() && $requestedClienteId !== null && $requestedClienteId > 0
            ? (int)$requestedClienteId
            : 0;
    }

    private function clienteIdsVisiveis(): array
    {
        return array_values(array_filter(array_map(
            static fn(array $cliente): int => (int)($cliente['id'] ?? 0),
            $this->clientes->all()
        )));
    }

    /**
     * Amplia temporariamente o escopo tenant para permitir leitura do catálogo da matriz
     * quando o usuário autenticado está vinculado a uma filial.
     *
     * @param callable():array $callback
     */
    private function withCatalogScope(int $clienteId, callable $callback): array
    {
        if (Auth::isInstituto() || $clienteId <= 0 || empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            return $callback();
        }

        $rootId = $this->resolveCatalogRootId($clienteId);
        if ($rootId <= 0 || $rootId === $clienteId) {
            return $callback();
        }

        $originalAllowed = $_SESSION['user']['allowed_client_ids'] ?? [];
        $originalSelected = $_SESSION['user']['selected_client_ids'] ?? [];
        $expanded = array_values(array_unique(array_map('intval', array_merge((array)$originalAllowed, [$clienteId, $rootId]))));
        sort($expanded);

        $_SESSION['user']['allowed_client_ids'] = $expanded;
        $_SESSION['user']['selected_client_ids'] = $expanded;

        try {
            return $callback();
        } finally {
            $_SESSION['user']['allowed_client_ids'] = $originalAllowed;
            $_SESSION['user']['selected_client_ids'] = $originalSelected;
        }
    }

    private function resolveCatalogRootId(int $clienteId): int
    {
        $cliente = $this->clientes->findAny($clienteId);
        if (!$cliente) {
            return 0;
        }
        $matrizId = (int)($cliente['matriz_id'] ?? 0);
        $isMatriz = $matrizId <= 0 && (int)($cliente['is_matriz'] ?? 1) === 1;
        if ($isMatriz) {
            return $clienteId;
        }
        return $matrizId > 0 ? $matrizId : $clienteId;
    }
}
