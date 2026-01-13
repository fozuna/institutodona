<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ColaboradorModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\DepartamentoModel;

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
        $items = $cliente ? $this->colabs->allByCliente($cliente) : [];
        $departamentos = $cliente ? $this->deps->allByCliente($cliente) : $this->deps->all();
        $setores = $cliente ? $this->setores->allByCliente($cliente) : $this->setores->all();
        $funcoes = $cliente ? $this->funcoes->allByCliente($cliente) : [];
        $this->render('colaboradores/index', ['items' => $items, 'departamentos' => $departamentos, 'setores' => $setores, 'funcoes' => $funcoes, 'cliente' => $cliente]);
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
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($nome && $funcaoId) { $this->colabs->create(['nome' => $nome, 'email' => $email, 'funcao_id' => $funcaoId]); }
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
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($id) { $this->colabs->update($id, ['nome' => $nome, 'email' => $email, 'funcao_id' => $funcaoId]); }
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
}
