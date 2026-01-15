<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ColaboradorModel;
use App\Models\FuncaoModel;
use App\Models\SetorModel;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;

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
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per']) ? max(5, min(100, (int)$_GET['per'])) : 20;
        $lider = isset($_GET['lider']) && in_array($_GET['lider'], ['sim','não'], true) ? $_GET['lider'] : '';
        $departamentoId = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
        $funcaoId = isset($_GET['funcao']) ? (int)$_GET['funcao'] : 0;
        $filters = [
            'lider' => $lider,
            'departamento_id' => $departamentoId ?: null,
            'funcao_id' => $funcaoId ?: null,
        ];
        $items = $cliente ? $this->colabs->paginatedByClienteWithFilters($cliente, $page, $perPage, $filters) : [];
        $total = $cliente ? $this->colabs->countByClienteWithFilters($cliente, $filters) : 0;
        $totalPages = $cliente ? max(1, (int)ceil($total / $perPage)) : 1;
        $departamentos = $cliente ? $this->deps->allByCliente($cliente) : $this->deps->all();
        $setores = $cliente ? $this->setores->allByCliente($cliente) : $this->setores->all();
        $funcoes = $cliente ? $this->funcoes->allByCliente($cliente) : [];
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
            'filter_funcao' => $funcaoId
        ]);
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
}
