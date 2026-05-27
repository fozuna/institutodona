<?php
namespace App\Controllers;

use App\Core\BaseController;
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
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $items = $effectiveCliente > 0 ? $this->funcoes->allByCliente($effectiveCliente) : [];
        $departamentos = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
        $setores = $effectiveCliente > 0 ? $this->setores->allByCliente($effectiveCliente) : $this->setores->all();
        $this->render('funcoes/index', ['items' => $items, 'departamentos' => $departamentos, 'setores' => $setores, 'cliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Cadastros de Funções são geridos pela Matriz e herdados automaticamente pelas filiais.';
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=funcoes/index&cliente=' . (int)$root);
            return;
        }
        $selectedSetorId = isset($_GET['setor_id']) ? (int)$_GET['setor_id'] : 0;
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $setores = $effectiveCliente > 0 ? $this->setores->allByCliente($effectiveCliente) : $this->setores->all();
        $departamentos = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
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
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem cadastrar Funções. Cadastre na Matriz e a herança será automática.';
            AuditLogger::log('catalog_write_blocked', 'funcoes', null, ['cliente_id' => $cliente]);
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=funcoes/index&cliente=' . (int)$root);
            return;
        }
        if ($nome && $setorId) { $this->funcoes->create(['nome' => $nome, 'setor_id' => $setorId]); }
        header('Location: index.php?route=funcoes/index' . ($cliente ? '&cliente=' . $cliente : ''));
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->funcoes->find($id);
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem editar Funções. Edite na Matriz.';
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=funcoes/index&cliente=' . (int)$root);
            return;
        }
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $setores = $effectiveCliente > 0 ? $this->setores->allByCliente($effectiveCliente) : $this->setores->all();
        $departamentos = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
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
        $cliente = isset($_POST['cliente']) ? (int)$_POST['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem editar Funções. Edite na Matriz.';
            AuditLogger::log('catalog_write_blocked', 'funcoes', $id ?: null, ['cliente_id' => $cliente]);
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=funcoes/index&cliente=' . (int)$root);
            return;
        }
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
