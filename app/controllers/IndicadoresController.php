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
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $items = $cliente ? $this->model->byCliente($cliente) : [];
        \App\Core\AuditLogger::log('indicadores_view', 'indicador', null, ['cliente_id' => $cliente]);
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
            'cliente_id' => (int)($this->resolveScopedClienteId((int)($_POST['cliente_id'] ?? 0)) ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'unidade' => 'R$',
            'referencia' => $_POST['referencia'] ?? null,
            'meta' => isset($_POST['meta']) ? (float)$_POST['meta'] : 0,
            'realizado' => 0,
        ];
        if ($data['meta'] < 0) $data['meta'] = 0;
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
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
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

    public function realizado(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $clientes = (new ClienteModel())->all();
        $items = $cliente ? $this->model->byCliente($cliente) : [];
        $this->render('indicadores/realizado', compact('clientes','cliente','items'));
    }

    public function painel(): void
    {
        $this->requireLogin();
        $cliente = (int)($this->resolveScopedClienteId(isset($_GET['cliente']) ? (int)$_GET['cliente'] : null) ?? 0);
        $ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
        $clientes = (new ClienteModel())->all();
        $items = $cliente ? $this->model->byCliente($cliente) : [];
        $months = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        $rows = [];
        foreach ($items as $it) {
            $ref = $it['referencia'] ?? '';
            $y = $ref ? (int)substr($ref,0,4) : null;
            if ($y !== null && $y !== $ano) continue;
            $m = $ref ? (int)substr($ref,5,2) : null;
            $name = $it['nome'];
            if (!isset($rows[$name])) {
                $rows[$name] = [
                    'nome' => $name,
                    'meses' => array_fill(1, 12, ['meta'=>0.0,'real'=>0.0]),
                    'total_meta' => 0.0,
                    'total_real' => 0.0,
                ];
            }
            if ($m && $m>=1 && $m<=12) {
                $rows[$name]['meses'][$m]['meta'] = (float)$it['meta'];
                $rows[$name]['meses'][$m]['real'] = (float)$it['realizado'];
                $rows[$name]['total_meta'] += (float)$it['meta'];
                $rows[$name]['total_real'] += (float)$it['realizado'];
            }
        }
        $this->render('indicadores/painel', [
            'cliente' => $cliente,
            'clientes' => $clientes,
            'ano' => $ano,
            'months' => $months,
            'rows' => $rows,
        ]);
    }


    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'cliente_id' => (int)($this->resolveScopedClienteId((int)($_POST['cliente_id'] ?? 0)) ?? 0),
            'nome' => trim($_POST['nome'] ?? ''),
            'unidade' => 'R$',
            'referencia' => $_POST['referencia'] ?? null,
            'meta' => isset($_POST['meta']) ? (float)$_POST['meta'] : 0,
            'realizado' => null,
        ];
        if ($data['meta'] < 0) $data['meta'] = 0;
        if ($id && $data['cliente_id'] && $data['nome'] !== '') {
            // Mantém realizado atual (não editável aqui)
            $curr = $this->model->find($id);
            $data['realizado'] = (float)($curr['realizado'] ?? 0);
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
        if ($real !== null && $real < 0) $real = 0;
        $cliente = (int)($this->resolveScopedClienteId((int)($_POST['cliente'] ?? 0)) ?? 0);
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
