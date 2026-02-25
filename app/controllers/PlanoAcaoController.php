<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\PlanoAcaoTaskModel;
use App\Models\PlanoAcaoMetricModel;
use App\Models\PlanoAcaoCheckModel;
use App\Models\PlanoAcaoActionModel;
use App\Models\PlanoAcaoHistoryModel;
use App\Models\ClienteModel;

class PlanoAcaoController extends BaseController
{
    private PlanoAcaoTaskModel $tasks;
    private PlanoAcaoMetricModel $metrics;
    private PlanoAcaoCheckModel $checks;
    private PlanoAcaoActionModel $actions;

    public function __construct()
    {
        $this->tasks = new PlanoAcaoTaskModel();
        $this->metrics = new PlanoAcaoMetricModel();
        $this->checks = new PlanoAcaoCheckModel();
        $this->actions = new PlanoAcaoActionModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $clientes = (new ClienteModel())->all();
        $selectedCliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : null;
        $items = $selectedCliente ? $this->tasks->byCliente($selectedCliente) : [];
        $this->render('planoacao/index', [
            'clientes' => $clientes,
            'selectedCliente' => $selectedCliente,
            'items' => $items,
        ]);
    }

    public function apiList(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        $clienteId = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        
        if (!$clienteId) {
            echo json_encode(['success' => false, 'error' => 'Cliente não informado']);
            return;
        }

        try {
            // Simulate network delay for loading indicator demonstration if needed, but keeping it fast
            // usleep(300000); 
            
            $items = $this->tasks->byCliente($clienteId);
            
            // Format items for frontend if necessary, but raw data seems fine based on current view usage
            echo json_encode(['success' => true, 'data' => $items]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $selectedCliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : null;
        $statusFilter = $_GET['status'] ?? '';
        $search = trim($_GET['q'] ?? '');
        $items = [];
        if ($selectedCliente) {
            $items = $this->tasks->byCliente($selectedCliente);
            $items = array_values(array_filter($items, function(array $row) use ($statusFilter, $search): bool {
                if ($statusFilter !== '' && ($row['status'] ?? '') !== $statusFilter) {
                    return false;
                }
                if ($search !== '') {
                    $hay = mb_strtolower(($row['titulo'] ?? '') . ' ' . ($row['responsavel'] ?? ''));
                    if (mb_strpos($hay, mb_strtolower($search)) === false) {
                        return false;
                    }
                }
                return true;
            }));
        }
        $this->render('planoacao/create', [
            'clientes' => $clientes,
            'selectedCliente' => $selectedCliente,
            'items' => $items,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    public function store(): void
    {
        try {
            $this->requireRole('instituto');
            $csrf = $_POST['csrf'] ?? null;
            if (!Security::verifyCsrf($csrf)) { 
                http_response_code(400); 
                echo 'CSRF inválido'; 
                return; 
            }
            
            // Checkbox handling: if 'concluido' is present, progress is 100, else 0
            $isConcluido = isset($_POST['concluido']);
            $progresso = $isConcluido ? 100 : 0;
            $status = $_POST['status'] ?? 'A Fazer';
            
            // Auto-update status based on completion if needed, or trust user input
            if ($isConcluido && $status !== 'Concluído') {
                $status = 'Concluído';
            }

            $data = [
                'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
                'titulo' => trim($_POST['titulo'] ?? ''),
                'descricao' => trim($_POST['descricao'] ?? ''),
                'meta_valor' => trim($_POST['meta_valor'] ?? ''), // Now text
                'meta_unidade' => trim($_POST['meta_unidade'] ?? ''), // Now text
                'prazo' => $_POST['prazo'] ?: null, // Date or null
                'responsavel' => trim($_POST['responsavel'] ?? ''),
                'fase' => 'DO',
                'status' => $status,
                'progresso' => $progresso,
            ];
            if ($data['id_cliente'] && $data['titulo']) {
                $this->tasks->create($data);
                header('Location: index.php?route=planoacao/create&cliente=' . $data['id_cliente']);
                return;
            }
            header('Location: index.php?route=planoacao/create');
        } catch (\Throwable $e) {
            echo '<div style="padding:20px; font-family:monospace; background:#ffebeb; color:#900;">';
            echo '<strong>Erro ao salvar plano de ação:</strong><br>';
            echo htmlspecialchars($e->getMessage()) . '<br><br>';
            echo '<strong>Arquivo:</strong> ' . $e->getFile() . ' (Linha ' . $e->getLine() . ')<br><br>';
            echo '<strong>Trace:</strong><pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
            exit;
        }
    }

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $filterStatus = $_GET['filter_status'] ?? null;
        $historyFilters = [
            'user_id' => $_GET['h_user'] ?? null,
            'action_type' => $_GET['h_action'] ?? null,
            'date_start' => $_GET['h_start'] ?? null,
            'date_end' => $_GET['h_end'] ?? null,
        ];

        $task = $this->tasks->find($id);
        $metrics = $this->metrics->byTask($id);
        $checks = $this->checks->byTask($id);
        $actions = $this->actions->byTask($id, $filterStatus);
        $history = (new PlanoAcaoHistoryModel())->getByTask($id, $historyFilters);
        $users = (new \App\Models\UsuarioModel())->all(); // Assuming UsuarioModel exists and has all()
        
        $this->render('planoacao/show', compact('task','metrics','checks','actions','filterStatus','history','historyFilters','users'));
    }

    public function updateTask(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header('Location: index.php?route=planoacao/index'); return; }

        // Checkbox handling for updates
        $isConcluido = isset($_POST['concluido']);
        $progresso = $isConcluido ? 100 : 0;
        $status = $_POST['status'] ?? 'A Fazer';
        
        if ($isConcluido && $status !== 'Concluído') {
            $status = 'Concluído';
        }

        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'meta_valor' => trim($_POST['meta_valor'] ?? ''),
            'meta_unidade' => trim($_POST['meta_unidade'] ?? ''),
            'prazo' => $_POST['prazo'] ?: null,
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'status' => $status,
            'progresso' => $progresso,
        ];
        
        $this->tasks->update($id, $data);
        $_SESSION['flash_success'] = 'Dados atualizados com sucesso';
        header('Location: index.php?route=planoacao/show&id=' . $id);
    }

    public function updateAction(): void
    {
        try {
            $this->requireRole('instituto');
            $csrf = $_POST['csrf'] ?? null;
            if (!Security::verifyCsrf($csrf)) {
                http_response_code(400);
                echo 'CSRF inválido';
                return;
            }
            
            $id = (int)($_POST['id'] ?? 0);
            $taskId = (int)($_POST['task_id'] ?? 0);
            
            if ($id) {
                $data = [
                    'titulo' => trim($_POST['titulo'] ?? ''),
                    'owner' => trim($_POST['owner'] ?? ''),
                    'due_date' => $_POST['due_date'] ?? null,
                    'status' => $_POST['status'] ?? 'Planejado',
                ];
                
                $this->actions->update($id, $data);
            }
            
            header('Location: index.php?route=planoacao/show&id=' . $taskId);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            header('Location: index.php?route=planoacao/show&id=' . $taskId . '&error=1');
        }
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            echo 'CSRF inválido';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $task = $this->tasks->find($id);
        
        if ($task) {
            $this->tasks->delete($id);
            // Optionally delete related actions, metrics, etc.
            // Ideally use foreign keys with ON DELETE CASCADE or handle here
            
            // Log deletion
            (new PlanoAcaoHistoryModel())->log('task', $id, 'delete', [], $_SESSION['user']['id'] ?? null);
            
            header('Location: index.php?route=planoacao/index&cliente=' . $task['id_cliente']);
        } else {
            header('Location: index.php?route=planoacao/index');
        }
    }

    public function upsertMetric(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $metric = [
            'id' => $_POST['id'] ?? null,
            'nome' => trim($_POST['nome'] ?? ''),
            'planejado' => $_POST['planejado'] ?? null,
            'realizado' => $_POST['realizado'] ?? null,
            'unidade' => $_POST['unidade'] ?? null,
        ];
        if ($taskId && $metric['nome']) { $this->metrics->upsert($taskId, $metric); }
        header('Location: index.php?route=planoacao/show&id=' . $taskId);
    }

    public function addCheck(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $data = ['gap' => $_POST['gap'] ?? null, 'analise' => trim($_POST['analise'] ?? '')];
        if ($taskId) { $this->checks->add($taskId, $data); }
        header('Location: index.php?route=planoacao/show&id=' . $taskId);
    }

    public function createAction(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'owner' => trim($_POST['owner'] ?? ''),
            'due_date' => $_POST['due_date'] ?? null,
            'status' => $_POST['status'] ?? 'Planejado',
        ];
        if ($taskId && $data['titulo']) {
            $this->actions->create($taskId, $data);
            $_SESSION['flash_success'] = 'Ação de melhoria criada com sucesso';
        }
        // Redirect to prevent form resubmission and show flash message
        header('Location: index.php?route=planoacao/show&id=' . $taskId);
    }
}
