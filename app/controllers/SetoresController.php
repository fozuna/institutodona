<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuditLogger;
use App\Core\Security;
use App\Models\SetorModel;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;

class SetoresController extends BaseController
{
    private SetorModel $setores;
    private DepartamentoModel $deps;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->setores = new SetorModel();
        $this->deps = new DepartamentoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $items = $effectiveCliente > 0 ? $this->setores->allByCliente($effectiveCliente) : $this->setores->all();
        $departamentos = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
        $this->render('setores/index', ['items' => $items, 'departamentos' => $departamentos, 'selectedCliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Cadastros de Setores são geridos pela Matriz e herdados automaticamente pelas filiais.';
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=setores/index&cliente=' . (int)$root);
            return;
        }
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $departamentos = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
        $this->render('setores/create', ['departamentos' => $departamentos, 'cliente' => $cliente]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $nome = trim($_POST['nome'] ?? '');
        $depId = (int)($_POST['departamento_id'] ?? 0);
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem cadastrar Setores. Cadastre na Matriz e a herança será automática.';
            AuditLogger::log('catalog_write_blocked', 'setores', null, ['cliente_id' => $cliente]);
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=setores/index&cliente=' . (int)$root);
            return;
        }
        if ($nome && $depId) { $this->setores->create(['nome' => $nome, 'departamento_id' => $depId]); }
        header('Location: index.php?route=setores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->setores->find($id);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem editar Setores. Edite na Matriz.';
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=setores/index&cliente=' . (int)$root);
            return;
        }
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $departamentos = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
        $this->render('setores/edit', ['item' => $item, 'departamentos' => $departamentos, 'cliente' => $cliente]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $depId = (int)($_POST['departamento_id'] ?? 0);
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem editar Setores. Edite na Matriz.';
            AuditLogger::log('catalog_write_blocked', 'setores', $id ?: null, ['cliente_id' => $cliente]);
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=setores/index&cliente=' . (int)$root);
            return;
        }
        if ($id) { $this->setores->update($id, ['nome' => $nome, 'departamento_id' => $depId]); }
        header('Location: index.php?route=setores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($id) { $this->setores->delete($id); }
        header('Location: index.php?route=setores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }
}
