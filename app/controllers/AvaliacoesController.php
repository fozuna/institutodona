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
        $notaFin = is_array($fin) ? count($fin) : 0;
        $notaMer = is_array($mer) ? count($mer) : 0;
        $notaPes = is_array($pes) ? count($pes) : 0;
        $notaPro = is_array($pro) ? count($pro) : 0;
        $payload = [
            'cliente_id' => $clienteId ?: null,
            'empresa_nome' => $clienteId ? null : ($empresaNome ?: null),
            'contato' => $contato ?: null,
            'respostas_json' => json_encode(['financeiro' => $fin, 'mercado' => $mer, 'pessoas' => $pes, 'processo' => $pro]),
            'nota_financeiro' => $notaFin,
            'nota_mercado' => $notaMer,
            'nota_pessoas' => $notaPes,
            'nota_processo' => $notaPro,
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
