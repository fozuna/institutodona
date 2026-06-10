<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\AuditLogger;
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
        $effectiveCliente = $cliente > 0 ? $this->clientes->catalogRootIdFor($cliente) : 0;
        $items = $effectiveCliente > 0 ? $this->deps->allByCliente($effectiveCliente) : $this->deps->all();
        $clientes = $this->clientes->all();
        $this->render('departamentos/index', ['items' => $items, 'clientes' => $clientes, 'selectedCliente' => $cliente]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Cadastros de Departamentos são geridos pela Matriz e herdados automaticamente pelas filiais.';
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=departamentos/index&cliente=' . (int)$root);
            return;
        }
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
        if ($clienteId > 0 && $this->clientes->isFilial($clienteId)) {
            $_SESSION['flash_error'] = 'Filiais não podem cadastrar Departamentos. Cadastre na Matriz e a herança será automática.';
            AuditLogger::log('catalog_write_blocked', 'departamentos', null, ['cliente_id' => $clienteId]);
            $root = $this->clientes->catalogRootIdFor($clienteId);
            header('Location: index.php?route=departamentos/index&cliente=' . (int)$root);
            return;
        }
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
        if ($cliente > 0 && $this->clientes->isFilial($cliente)) {
            $_SESSION['flash_error'] = 'Filiais não podem editar Departamentos. Edite na Matriz.';
            $root = $this->clientes->catalogRootIdFor($cliente);
            header('Location: index.php?route=departamentos/index&cliente=' . (int)$root);
            return;
        }
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
        if ($clienteId > 0 && $this->clientes->isFilial($clienteId)) {
            $_SESSION['flash_error'] = 'Filiais não podem editar Departamentos. Edite na Matriz.';
            AuditLogger::log('catalog_write_blocked', 'departamentos', $id ?: null, ['cliente_id' => $clienteId]);
            $root = $this->clientes->catalogRootIdFor($clienteId);
            header('Location: index.php?route=departamentos/index&cliente=' . (int)$root);
            return;
        }
        $this->deps->update($id, ['nome' => $nome, 'cliente_id' => $clienteId]);
        header('Location: index.php?route=departamentos/index&cliente=' . $clienteId);
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $dep = $this->deps->find($id);
        $this->deps->delete($id);
        $clienteId = $dep['cliente_id'] ?? 0;
        header('Location: index.php?route=departamentos/index' . ($clienteId ? '&cliente=' . (int)$clienteId : ''));
    }
}
