<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\DepartamentoModel;

class FuncoesController extends BaseController
{
    private FuncaoModel $funcoes;
    private SetorModel $setores;
    private DepartamentoModel $deps;

    public function __construct()
    {
        $this->funcoes = new FuncaoModel();
        $this->setores = new SetorModel();
        $this->deps = new DepartamentoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $cliente ? $this->funcoes->allByCliente($cliente) : [];
        $departamentos = $cliente ? $this->deps->allByCliente($cliente) : $this->deps->all();
        $setores = $cliente ? $this->setores->allByCliente($cliente) : $this->setores->all();
        $this->render('funcoes/index', ['items' => $items, 'departamentos' => $departamentos, 'setores' => $setores, 'cliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $setores = $cliente ? $this->setores->allByCliente($cliente) : $this->setores->all();
        $this->render('funcoes/create', ['setores' => $setores, 'cliente' => $cliente]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $nome = trim($_POST['nome'] ?? '');
        $setorId = (int)($_POST['setor_id'] ?? 0);
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($nome && $setorId) { $this->funcoes->create(['nome' => $nome, 'setor_id' => $setorId]); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->funcoes->find($id);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $setores = $cliente ? $this->setores->allByCliente($cliente) : $this->setores->all();
        $this->render('funcoes/edit', ['item' => $item, 'setores' => $setores, 'cliente' => $cliente]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $setorId = (int)($_POST['setor_id'] ?? 0);
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($id) { $this->funcoes->update($id, ['nome' => $nome, 'setor_id' => $setorId]); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($id) { $this->funcoes->delete($id); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }
}
