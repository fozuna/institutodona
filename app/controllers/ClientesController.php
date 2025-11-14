<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;

class ClientesController extends BaseController
{
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $items = $this->clientes->all();
        $this->render('clientes/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $this->render('clientes/create');
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
        ];
        if ($data['nome_empresa'] && $data['CNPJ']) {
            $this->clientes->create($data);
        }
        header('Location: index.php?route=clientes/index');
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->clientes->find($id);
        $this->render('clientes/edit', ['item' => $item]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
        ];
        if ($id) { $this->clientes->update($id, $data); }
        header('Location: index.php?route=clientes/index');
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $this->clientes->delete($id); }
        header('Location: index.php?route=clientes/index');
    }
}