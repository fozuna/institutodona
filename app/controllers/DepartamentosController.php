<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\DepartamentoModel;
use App\Models\ClienteModel;

class DepartamentosController extends BaseController
{
    private DepartamentoModel $deps;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->deps = new DepartamentoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $cliente ? $this->deps->allByCliente($cliente) : $this->deps->all();
        $clientes = $this->clientes->all();
        $this->render('departamentos/index', ['items' => $items, 'clientes' => $clientes, 'selectedCliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $clientes = $this->clientes->all();
        $this->render('departamentos/create', ['clientes' => $clientes, 'cliente' => $cliente]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $nome = trim($_POST['nome'] ?? '');
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        if (!$nome || !$clienteId) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        $this->deps->create(['nome' => $nome, 'cliente_id' => $clienteId]);
        header('Location: index.php?route=departamentos/index&cliente=' . $clienteId);
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->deps->find($id);
        $clientes = $this->clientes->all();
        $cliente = (int)($_GET['cliente'] ?? (($item['cliente_id'] ?? 0)));
        $this->render('departamentos/edit', ['item' => $item, 'clientes' => $clientes, 'cliente' => $cliente]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $this->deps->update($id, ['nome' => $nome, 'cliente_id' => $clienteId]);
        header('Location: index.php?route=departamentos/index&cliente=' . $clienteId);
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $dep = $this->deps->find($id);
        $this->deps->delete($id);
        $clienteId = $dep['cliente_id'] ?? 0;
        header('Location: index.php?route=departamentos/index' . ($clienteId ? '&cliente=' . (int)$clienteId : ''));
    }
}
