<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\ProcessoModel;

final class ProcessosController extends BaseController
{
    private ProcessoModel $processos;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->processos = new ProcessoModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $this->processos->all($cliente ?: null);
        $clientes = $this->clientes->all();
        $this->render('processos/index', [
            'items' => $items,
            'clientes' => $clientes,
            'selectedCliente' => $cliente,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $clientes = $this->clientes->allActive();
        $this->render('processos/create', ['clientes' => $clientes]);
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
        $data = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        $id = $this->processos->create($data);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        header('Location: index.php?route=processos/index&cliente=' . (int)$data['cliente_id']);
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->processos->find($id);
        $clientes = $this->clientes->all();
        $this->render('processos/edit', [
            'item' => $item,
            'clientes' => $clientes,
        ]);
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
        $data = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        $this->processos->update($id, $data);
        header('Location: index.php?route=processos/index&cliente=' . (int)$data['cliente_id']);
    }

    public function finalizar(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        $clienteId = (int)($_POST['cliente_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($id > 0) {
            $this->processos->finalize($id, $userId ?: null);
        }
        header('Location: index.php?route=processos/index' . ($clienteId ? '&cliente=' . $clienteId : ''));
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->processos->find($id);
        $this->processos->delete($id);
        $clienteId = (int)($item['cliente_id'] ?? 0);
        header('Location: index.php?route=processos/index' . ($clienteId ? '&cliente=' . $clienteId : ''));
    }
}

