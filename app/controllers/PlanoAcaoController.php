<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Core\PlanoAcaoImportService;
use App\Core\AuditLogger;
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

    public function setActionsModel(PlanoAcaoActionModel $actions): void
    {
        $this->actions = $actions;
    }

    public function setTaskModel(PlanoAcaoTaskModel $tasks): void
    {
        $this->tasks = $tasks;
    }

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
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per']) ? max(1, (int)$_GET['per']) : 12;
        $viewMode = trim((string)($_GET['view'] ?? 'cards'));
        if (!in_array($viewMode, ['cards', 'list'], true)) {
            $viewMode = 'cards';
        }
        $statusFilters = $_GET['status'] ?? [];
        if (!is_array($statusFilters)) {
            $statusFilters = [$statusFilters];
        }
        $statusFilters = array_values(array_filter(array_map('trim', $statusFilters)));
        $items = [];
        $total = 0;
        $totalPages = 1;
        if ($selectedCliente) {
            if (!empty($statusFilters)) {
                $total = $this->tasks->countByClienteMulti($selectedCliente, $statusFilters);
                $totalPages = max(1, (int)ceil($total / $per));
                if ($page > $totalPages) {
                    $page = $totalPages;
                }
                $items = $this->tasks->paginateByClienteMulti($selectedCliente, $page, $per, $statusFilters);
            } else {
                $total = $this->tasks->countByCliente($selectedCliente);
                $totalPages = max(1, (int)ceil($total / $per));
                if ($page > $totalPages) {
                    $page = $totalPages;
                }
                $items = $this->tasks->paginateByCliente($selectedCliente, $page, $per);
            }
        }
        $this->render('planoacao/index', [
            'clientes' => $clientes,
            'selectedCliente' => $selectedCliente,
            'items' => $items,
            'page' => $page,
            'per' => $per,
            'total' => $total,
            'totalPages' => $totalPages,
            'statusFilters' => $statusFilters,
            'viewMode' => $viewMode,
            'importEnabled' => getenv('PLANOACAO_IMPORT_ENABLED') === '1',
            'importAlreadyRun' => is_file(__DIR__ . '/../../storage/imports/planoacao_import_done.flag'),
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
            
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per = isset($_GET['per']) ? max(1, (int)$_GET['per']) : 12;
            $statusFilters = $_GET['status'] ?? [];
            if (!is_array($statusFilters)) {
                $statusFilters = [$statusFilters];
            }
            $statusFilters = array_values(array_filter(array_map('trim', $statusFilters)));
            if (!empty($statusFilters)) {
                $total = $this->tasks->countByClienteMulti($clienteId, $statusFilters);
                $totalPages = max(1, (int)ceil($total / $per));
                if ($page > $totalPages) {
                    $page = $totalPages;
                }
                $items = $this->tasks->paginateByClienteMulti($clienteId, $page, $per, $statusFilters);
            } else {
                $total = $this->tasks->countByCliente($clienteId);
                $totalPages = max(1, (int)ceil($total / $per));
                if ($page > $totalPages) {
                    $page = $totalPages;
                }
                $items = $this->tasks->paginateByCliente($clienteId, $page, $per);
            }
            echo json_encode([
                'success' => true,
                'data' => $items,
                'pagination' => [
                    'page' => $page,
                    'per' => $per,
                    'total' => $total,
                    'totalPages' => $totalPages,
                ],
                'filters' => ['status' => $statusFilters],
            ]);
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
        $statusFilters = $_GET['status'] ?? [];
        if (!is_array($statusFilters)) {
            $statusFilters = [$statusFilters];
        }
        $statusFilters = array_values(array_filter(array_map('trim', $statusFilters)));
        $search = trim($_GET['q'] ?? '');
        $items = [];
        if ($selectedCliente) {
            $items = $this->tasks->byCliente($selectedCliente);
            $items = array_values(array_filter($items, function(array $row) use ($statusFilters, $search): bool {
                if (!empty($statusFilters)) {
                    $match = false;
                    foreach ($statusFilters as $st) {
                        if ($st === 'Atrasado') {
                            $prazo = $row['prazo'] ?? null;
                            if (!empty($prazo) && $prazo < date('Y-m-d') && ($row['status'] ?? '') !== 'Concluído') {
                                $match = true;
                            }
                        } elseif (($row['status'] ?? '') === $st) {
                            $match = true;
                        }
                    }
                    if (!$match) return false;
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
            'statusFilters' => $statusFilters,
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
            $status = $_POST['status'] ?? 'Planejado';
            
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
                'prazo' => !empty($_POST['prazo']) ? $_POST['prazo'] : null, // Date or null
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
        if (!$id) { $this->redirect('index.php?route=planoacao/index'); return; }

        // Checkbox handling for updates
        $isConcluido = isset($_POST['concluido']);
        $progresso = $isConcluido ? 100 : 0;
        $status = $_POST['status'] ?? 'Planejado';
        
        if ($isConcluido && $status !== 'Concluído') {
            $status = 'Concluído';
        }

        $data = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'meta_valor' => trim($_POST['meta_valor'] ?? ''),
            'meta_unidade' => trim($_POST['meta_unidade'] ?? ''),
            'prazo' => !empty($_POST['prazo']) ? $_POST['prazo'] : null,
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'status' => $status,
            'progresso' => $progresso,
        ];
        
        try {
            $this->tasks->update($id, $data);
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Dados atualizados com sucesso']);
                return;
            }

            $_SESSION['flash_success'] = 'Dados atualizados com sucesso';
        } catch (\Exception $e) {
            error_log($e->getMessage());
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
                return;
            }

            $_SESSION['flash_error'] = 'Erro ao atualizar: ' . $e->getMessage();
        }
        
        $this->redirect('index.php?route=planoacao/show&id=' . $id);
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
                $_SESSION['flash_success'] = 'Item atualizado com sucesso';
            }
            
            $this->redirect('index.php?route=planoacao/show&id=' . $taskId);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $_SESSION['flash_error'] = 'Erro ao atualizar item: ' . $e->getMessage();
            $this->redirect('index.php?route=planoacao/show&id=' . $taskId);
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

    public function importForm(): void
    {
        $this->requireRole('instituto');
        $enabled = getenv('PLANOACAO_IMPORT_ENABLED') === '1';
        $flagPath = __DIR__ . '/../../storage/imports/planoacao_import_done.flag';
        $alreadyRun = is_file($flagPath);
        if (!$enabled) {
            http_response_code(403);
            echo 'Ferramenta de importação desativada.';
            return;
        }
        $clientes = (new ClienteModel())->all();
        $this->render('planoacao/import', [
            'clientes' => $clientes,
            'alreadyRun' => $alreadyRun,
        ]);
    }

    public function importRun(): void
    {
        $this->requireRole('instituto');
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_GET['ajax']) && $_GET['ajax'] === '1');
        $enabled = getenv('PLANOACAO_IMPORT_ENABLED') === '1';
        $flagDir = __DIR__ . '/../../storage/imports';
        $flagPath = $flagDir . '/planoacao_import_done.flag';
        if (!$enabled) {
            http_response_code(403);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Ferramenta de importação desativada.']);
            } else {
                echo 'Ferramenta de importação desativada.';
            }
            return;
        }
        if (is_file($flagPath)) {
            http_response_code(403);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Importação já foi executada anteriormente.']);
            } else {
                echo 'Importação já foi executada anteriormente.';
            }
            return;
        }
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'CSRF inválido']);
            } else {
                echo 'CSRF inválido';
            }
            return;
        }
        if (empty($_FILES['arquivo']) || ($_FILES['arquivo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Arquivo não enviado ou inválido.']);
            } else {
                echo 'Arquivo não enviado ou inválido.';
            }
            return;
        }
        $defaultClienteId = (int)($_POST['id_cliente'] ?? 0);
        $tmpName = $_FILES['arquivo']['tmp_name'];
        $origName = $_FILES['arquivo']['name'] ?? 'planilha';
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'csv'], true)) {
            http_response_code(400);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Apenas arquivos XLSX ou CSV são aceitos.']);
            } else {
                echo 'Apenas arquivos XLSX ou CSV são aceitos.';
            }
            return;
        }
        if (!is_dir($flagDir)) {
            @mkdir($flagDir, 0775, true);
        }
        $destName = date('Ymd_His') . '_planoacao_import.' . $ext;
        $destPath = $flagDir . '/' . $destName;
        if (!@move_uploaded_file($tmpName, $destPath)) {
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Falha ao salvar arquivo enviado.']);
            } else {
                echo 'Falha ao salvar arquivo enviado.';
            }
            return;
        }
        $service = new PlanoAcaoImportService();
        AuditLogger::log('planoacao_import_start', 'pdca_tasks', null, [
            'file' => $origName,
            'stored_as' => $destName,
            'default_cliente_id' => $defaultClienteId ?: null,
        ]);
        try {
            $stats = $service->import($destPath, $defaultClienteId ?: null);
            if (($stats['imported'] ?? 0) > 0) {
                @file_put_contents($flagPath, json_encode([
                    'file' => $destName,
                    'executed_at' => date('c'),
                    'stats' => $stats,
                ], JSON_UNESCAPED_UNICODE));
            }
            AuditLogger::log('planoacao_import_finished', 'pdca_tasks', null, $stats);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'report' => $stats,
                    'uploadedFile' => $origName,
                    'alreadyRun' => true,
                ]);
            } else {
                $clientes = (new ClienteModel())->all();
                $this->render('planoacao/import', [
                    'clientes' => $clientes,
                    'alreadyRun' => true,
                    'report' => $stats,
                    'uploadedFile' => $origName,
                ]);
            }
        } catch (\Throwable $e) {
            AuditLogger::log('planoacao_import_failed', 'pdca_tasks', null, [
                'message' => $e->getMessage(),
            ]);
            http_response_code(500);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Erro ao processar importação: ' . $e->getMessage()]);
            } else {
                echo 'Erro ao processar importação: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
