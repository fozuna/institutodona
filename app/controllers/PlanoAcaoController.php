<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\PlanoAcaoTaskModel;
use App\Models\PlanoAcaoMetricModel;
use App\Models\PlanoAcaoCheckModel;
use App\Models\PlanoAcaoActionModel;
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
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'id_cliente' => (int)($_POST['id_cliente'] ?? 0),
            'titulo' => trim($_POST['titulo'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'meta_valor' => $_POST['meta_valor'] ?? null,
            'meta_unidade' => $_POST['meta_unidade'] ?? null,
            'prazo' => $_POST['prazo'] ?? null,
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'fase' => 'DO',
            'status' => $_POST['status'] ?? 'A Fazer',
            'progresso' => (int)($_POST['progresso'] ?? 0),
        ];
        if ($data['id_cliente'] && $data['titulo']) {
            $this->tasks->create($data);
            header('Location: index.php?route=planoacao/create&cliente=' . $data['id_cliente']);
            return;
        }
        header('Location: index.php?route=planoacao/create');
    }

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $filterStatus = $_GET['filter_status'] ?? null;
        $task = $this->tasks->find($id);
        $metrics = $this->metrics->byTask($id);
        $checks = $this->checks->byTask($id);
        $actions = $this->actions->byTask($id, $filterStatus);
        $this->render('planoacao/show', compact('task','metrics','checks','actions','filterStatus'));
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
