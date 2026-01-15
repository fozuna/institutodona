<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\AvaliacaoModel;
use App\Models\ClienteModel;

class AvaliacoesController extends BaseController
{
    private AvaliacaoModel $model;

    public function __construct()
    {
        $this->model = new AvaliacaoModel();
    }

    public function index(): void
    {
        $this->requireLogin();
        $items = $this->model->all();
        $clientes = (new ClienteModel())->all();
        $this->render('avaliacoes/index', compact('items', 'clientes'));
    }

    public function create(): void
    {
        $this->requireLogin();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $clientes = (new ClienteModel())->all();
        $this->render('avaliacoes/create', compact('cliente', 'clientes'));
    }

    public function store(): void
    {
        $this->requireLogin();
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $clienteId = isset($_POST['cliente_id']) ? (int)$_POST['cliente_id'] : 0;
        $empresaNome = trim($_POST['empresa_nome'] ?? '');
        $contato = trim($_POST['contato'] ?? '');
        $fin = $_POST['financeiro'] ?? [];
        $mer = $_POST['mercado'] ?? [];
        $pes = $_POST['pessoas'] ?? [];
        $pro = $_POST['processo'] ?? [];
        $notaFin = isset($_POST['nota_financeiro']) ? (int)$_POST['nota_financeiro'] : (is_array($fin) ? count($fin) : 0);
        $notaMer = isset($_POST['nota_mercado']) ? (int)$_POST['nota_mercado'] : (is_array($mer) ? count($mer) : 0);
        $notaPes = isset($_POST['nota_pessoas']) ? (int)$_POST['nota_pessoas'] : (is_array($pes) ? count($pes) : 0);
        $notaPro = isset($_POST['nota_processo']) ? (int)$_POST['nota_processo'] : (is_array($pro) ? count($pro) : 0);
        $realFin = isset($_POST['realidade_financeiro']) ? (int)$_POST['realidade_financeiro'] : null;
        $realMer = isset($_POST['realidade_mercado']) ? (int)$_POST['realidade_mercado'] : null;
        $realPes = isset($_POST['realidade_pessoas']) ? (int)$_POST['realidade_pessoas'] : null;
        $realPro = isset($_POST['realidade_processo']) ? (int)$_POST['realidade_processo'] : null;
        $payload = [
            'cliente_id' => $clienteId ?: null,
            'empresa_nome' => $clienteId ? null : ($empresaNome ?: null),
            'contato' => $contato ?: null,
            'respostas_json' => json_encode(['financeiro' => $fin, 'mercado' => $mer, 'pessoas' => $pes, 'processo' => $pro]),
            'nota_financeiro' => $notaFin,
            'nota_mercado' => $notaMer,
            'nota_pessoas' => $notaPes,
            'nota_processo' => $notaPro,
            'realidade_financeiro' => $realFin,
            'realidade_mercado' => $realMer,
            'realidade_pessoas' => $realPes,
            'realidade_processo' => $realPro,
        ];
        $id = $this->model->create($payload);
        if ($clienteId) {
            header('Location: index.php?route=avaliacoes/show&id=' . $id . '&cliente=' . $clienteId);
        } else {
            header('Location: index.php?route=avaliacoes/show&id=' . $id);
        }
    }

    public function show(): void
    {
        $this->requireLogin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->model->find($id);
        $this->render('avaliacoes/show', compact('item'));
    }
}
