<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\TarefaModel;

final class TarefasController extends BaseController
{
    private TarefaModel $tarefas;
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->tarefas = new TarefaModel();
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $this->tarefas->all($cliente ?: null);
        $clientes = $this->clientes->all();
        $this->render('tarefas/index', [
            'items' => $items,
            'clientes' => $clientes,
            'selectedCliente' => $cliente,
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $clientes = $this->clientes->allActive();
        // Contexto opcional vindo do Perfil do Cliente (index.php?route=tarefas/create&cliente=ID).
        // So pre-seleciona se o cliente informado estiver entre os que o usuario pode acessar
        // (mesma lista ja tenant-scoped usada para popular o <select>); caso contrario, ignora.
        $requestedCliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $selectedCliente = 0;
        if ($requestedCliente > 0) {
            foreach ($clientes as $c) {
                if ((int)$c['id'] === $requestedCliente) {
                    $selectedCliente = $requestedCliente;
                    break;
                }
            }
        }
        $this->render('tarefas/create', [
            'clientes' => $clientes,
            'selectedCliente' => $selectedCliente,
        ]);
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
            'descricao' => trim($_POST['descricao'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'prioridade' => $_POST['prioridade'] ?? 'media',
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        $id = $this->tarefas->create($data);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Campos obrigatórios faltando';
            return;
        }
        // Quando o cadastro partiu do Perfil do Cliente (tarefas/create&cliente=ID), retorna
        // para la em vez da listagem geral de Tarefas, preservando o padrao ja usado pelas
        // demais acoes disparadas daquela tela (ver ClientesController::attach/storeFilial/...).
        if (!empty($_POST['voltar_perfil']) && (int)$data['cliente_id'] > 0) {
            header('Location: index.php?route=clientes/show&id=' . (int)$data['cliente_id']);
            return;
        }
        header('Location: index.php?route=tarefas/index&cliente=' . (int)$data['cliente_id']);
    }

    public function edit(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->tarefas->find($id);
        $clientes = $this->clientes->all();
        $this->render('tarefas/edit', [
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
            'descricao' => trim($_POST['descricao'] ?? ''),
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'prioridade' => $_POST['prioridade'] ?? 'media',
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        $this->tarefas->update($id, $data);
        header('Location: index.php?route=tarefas/index&cliente=' . (int)$data['cliente_id']);
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
            $this->tarefas->finalize($id, $userId ?: null);
        }
        header('Location: index.php?route=tarefas/index' . ($clienteId ? '&cliente=' . $clienteId : ''));
    }

    public function delete(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->tarefas->find($id);
        $this->tarefas->delete($id);
        $clienteId = (int)($item['cliente_id'] ?? 0);
        header('Location: index.php?route=tarefas/index' . ($clienteId ? '&cliente=' . $clienteId : ''));
    }
}

