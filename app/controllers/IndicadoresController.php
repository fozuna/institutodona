<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\IndicadorModel;
use App\Models\ClienteModel;

class IndicadoresController extends BaseController
{
    private IndicadorModel $model;

    public function __construct()
    {
        $this->model = new IndicadorModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $clientes = (new ClienteModel())->all();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $items = $cliente ? $this->model->byCliente($cliente) : [];
        $this->render('indicadores/index', compact('clientes', 'cliente', 'items'));
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $clientes = (new ClienteModel())->all();
        $this->render('indicadores/create', compact('clientes'));
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? ''),
            'referencia' => $_POST['referencia'] ?? null,
            'meta' => isset($_POST['meta']) ? (float)$_POST['meta'] : 0,
            'realizado' => isset($_POST['realizado']) ? (float)$_POST['realizado'] : 0,
        ];
        if ($data['cliente_id'] && $data['nome'] !== '') {
            $id = $this->model->create($data);
            header('Location: index.php?route=indicadores/edit&id=' . $id);
            return;
        }
        header('Location: index.php?route=indicadores/index');
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        $clientes = (new ClienteModel())->all();
        $this->render('indicadores/edit', compact('item', 'clientes'));
    }

    public function charts(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $clientes = (new ClienteModel())->all();
        $items = $cliente ? $this->model->byCliente($cliente) : [];
        // Agrupa por nome de indicador
        $series = [];
        foreach ($items as $it) {
            $name = $it['nome'];
            $ref = $it['referencia'] ?: '';
            $month = $ref ? (int)substr($ref, 5, 2) : null;
            if (!isset($series[$name])) $series[$name] = [];
            $series[$name][] = [
                'referencia' => $ref,
                'month' => $month,
                'meta' => (float)$it['meta'],
                'realizado' => (float)$it['realizado'],
                'unidade' => $it['unidade'] ?? '',
            ];
        }
        // Ordena por mês
        foreach ($series as $k => $arr) {
            usort($arr, function($a,$b){
                return ($a['month'] ?? 0) <=> ($b['month'] ?? 0);
            });
            $series[$k] = $arr;
        }
        $this->render('indicadores/charts', [
            'cliente' => $cliente,
            'clientes' => $clientes,
            'series' => $series,
        ]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'cliente_id' => (int)($_POST['cliente_id'] ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'unidade' => trim($_POST['unidade'] ?? 'R$'),
            'referencia' => $_POST['referencia'] ?? null,
            'meta' => isset($_POST['meta']) ? (float)$_POST['meta'] : 0,
            'realizado' => isset($_POST['realizado']) ? (float)$_POST['realizado'] : 0,
        ];
        if ($id && $data['cliente_id'] && $data['nome'] !== '') {
            $this->model->update($id, $data);
        }
        header('Location: index.php?route=indicadores/edit&id=' . $id);
    }

    public function updateRealizado(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $real = isset($_POST['realizado']) ? (float)$_POST['realizado'] : null;
        $cliente = (int)($_POST['cliente'] ?? 0);
        if ($id && $real !== null) { $this->model->updateRealizado($id, $real); }
        header('Location: index.php?route=indicadores/index&cliente=' . $cliente);
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $this->model->delete($id); }
        header('Location: index.php?route=indicadores/index');
    }
}
