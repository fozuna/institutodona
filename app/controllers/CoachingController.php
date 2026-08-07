<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\CoachingModel;

final class CoachingController extends BaseController
{
    private CoachingModel $coachings;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->coachings = new CoachingModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $this->coachings->all($cliente ?: null);
        $clientes = $this->clientes->all();
        $this->render('coaching/index', [
            'items' => $items,
            'clientes' => $clientes,
            'selectedCliente' => $cliente,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $clientes = $this->clientes->allActive();
        $this->render('coaching/create', ['clientes' => $clientes]);
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
            'titulo' => trim($_POST['titulo'] ?? ''),
            'coach' => trim($_POST['coach'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        $id = $this->coachings->create($data);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        header('Location: index.php?route=coaching/index&cliente=' . (int)$data['cliente_id']);
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->coachings->find($id);
        $clientes = $this->clientes->all();
        $this->render('coaching/edit', [
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
            'titulo' => trim($_POST['titulo'] ?? ''),
            'coach' => trim($_POST['coach'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        $this->coachings->update($id, $data);
        header('Location: index.php?route=coaching/index&cliente=' . (int)$data['cliente_id']);
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
            $this->coachings->finalize($id, $userId ?: null);
        }
        header('Location: index.php?route=coaching/index' . ($clienteId ? '&cliente=' . $clienteId : ''));
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->coachings->find($id);
        $this->coachings->delete($id);
        $clienteId = (int)($item['cliente_id'] ?? 0);
        header('Location: index.php?route=coaching/index' . ($clienteId ? '&cliente=' . $clienteId : ''));
    }
}

