<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\PdcaTaskModel;
use App\Models\PdcaMetricModel;
use App\Models\PdcaCheckModel;
use App\Models\PdcaActionModel;
use App\Models\ClienteModel;

class PdcaController extends BaseController
{
    private PdcaTaskModel $tasks;
    private PdcaMetricModel $metrics;
    private PdcaCheckModel $checks;
    private PdcaActionModel $actions;

    public function __construct()
    {
        $this->tasks = new PdcaTaskModel();
        $this->metrics = new PdcaMetricModel();
        $this->checks = new PdcaCheckModel();
        $this->actions = new PdcaActionModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $clientes = (new ClienteModel())->all();
        $selectedCliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : null;
        $items = $selectedCliente ? $this->tasks->byCliente($selectedCliente) : [];
        $this->render('pdca/index', [
            'clientes' => $clientes,
            'selectedCliente' => $selectedCliente,
            'items' => $items,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $this->render('pdca/create', ['clientes' => $clientes]);
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
            'meta_valor' => null,
            'meta_unidade' => null,
            'prazo' => $_POST['prazo'] ?? null,
            'responsavel' => trim($_POST['responsavel'] ?? ''),
            'fase' => 'PLAN',
            'status' => 'A Fazer',
            'progresso' => 0,
        ];
        if ($data['id_cliente'] && $data['titulo'] && $data['responsavel'] && $data['prazo']) {
            $id = $this->tasks->create($data);
            header('Location: index.php?route=pdca/show&id=' . $id);
            return;
        }
        header('Location: index.php?route=pdca/index');
    }

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $task = $this->tasks->find($id);
        $actions = $this->actions->byTask($id);
        $this->render('pdca/show', compact('task','actions'));
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
        header('Location: index.php?route=pdca/show&id=' . $taskId);
    }

    public function addCheck(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $taskId = (int)($_POST['task_id'] ?? 0);
        $data = ['gap' => $_POST['gap'] ?? null, 'analise' => trim($_POST['analise'] ?? '')];
        if ($taskId) { $this->checks->add($taskId, $data); }
        header('Location: index.php?route=pdca/show&id=' . $taskId);
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
        if ($taskId && $data['titulo']) { $this->actions->create($taskId, $data); }
        header('Location: index.php?route=pdca/show&id=' . $taskId);
    }
}
