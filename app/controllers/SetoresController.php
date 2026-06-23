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
        $items = $cliente > 0 ? $this->setores->allByCliente($cliente) : $this->setores->all();
        $departamentos = $cliente > 0 ? $this->deps->allByCliente($cliente) : $this->deps->all();
        $this->render('setores/index', ['items' => $items, 'departamentos' => $departamentos, 'selectedCliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $departamentos = $cliente > 0 ? $this->deps->allByCliente($cliente) : $this->deps->all();
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
        if ($nome && $depId) { $this->setores->create(['nome' => $nome, 'departamento_id' => $depId]); }
        header('Location: index.php?route=setores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->setores->find($id);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $departamentos = $cliente > 0 ? $this->deps->allByCliente($cliente) : $this->deps->all();
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
        if ($id) { $this->setores->update($id, ['nome' => $nome, 'departamento_id' => $depId]); }
        header('Location: index.php?route=setores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($id) { $this->setores->delete($id); }
        header('Location: index.php?route=setores/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }
}
