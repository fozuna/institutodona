<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Models\MetodologiaModel;
use App\Models\PilarModel;

class MetodologiaController extends BaseController
{
    private MetodologiaModel $model;
    private PilarModel $pilares;

    public function __construct()
    {
        $this->model = new MetodologiaModel();
        $this->pilares = new PilarModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $metodologias = $this->model->all();
        $this->render('metodologias/index', compact('metodologias'));
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $pilares = $this->pilares->all();
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $this->render('metodologias/create', ['pilares' => $pilares, 'cliente' => $cliente]);
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $idPilar = (int)($_POST['id_pilar'] ?? 0);
        $itemPilar = trim($_POST['item_pilar'] ?? '');
        $tipo = $_POST['tipo'] ?? 'tarefa';
        $observacoes = trim($_POST['observacoes'] ?? '');
        $arquivoPath = null;
        if (!empty($_FILES['arquivo']['name']) && is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
            $allow = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'text/plain' => 'txt'
            ];
            $type = $_FILES['arquivo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public/assets/files/metodologias';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($itemPilar));
                $name = $safe ? $safe : 'manual';
                $file = $name . '-' . uniqid() . '.' . $ext;
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
                    $arquivoPath = 'public/assets/files/metodologias/' . $file;
                }
            }
        }
        if ($tipo === 'manual' && !$arquivoPath) {
            header('Location: index.php?route=metodologias/create&err=arquivo_obrigatorio');
            return;
        }
        $clienteId = (int)($_POST['id_cliente'] ?? 0);
        if ($idPilar && $itemPilar !== '' && $clienteId) {
            $this->model->create([
                'id_pilar' => $idPilar,
                'item_pilar' => $itemPilar,
                'tipo' => $tipo,
                'arquivo_path' => $arquivoPath,
                'observacoes' => $observacoes,
                'cliente_id' => $clienteId,
            ]);
        }
        $redirectCliente = $clienteId ? ('&id=' . $clienteId) : '';
        header('Location: index.php?route=clientes/show' . $redirectCliente);
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $metodologia = $this->model->find($id);
        $pilares = $this->pilares->all();
        $this->render('metodologias/edit', compact('metodologia', 'pilares'));
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_POST['id'] ?? 0);
        $idPilar = (int)($_POST['id_pilar'] ?? 0);
        $itemPilar = trim($_POST['item_pilar'] ?? '');
        $tipo = $_POST['tipo'] ?? 'tarefa';
        $observacoes = trim($_POST['observacoes'] ?? '');
        $current = $this->model->find($id);
        $arquivoPath = $current['arquivo_path'] ?? null;
        if (!empty($_FILES['arquivo']['name']) && is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
            $allow = [
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'text/plain' => 'txt'
            ];
            $type = $_FILES['arquivo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public/assets/files/metodologias';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($itemPilar));
                $name = $safe ? $safe : 'manual';
                $file = $name . '-' . uniqid() . '.' . $ext;
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
                    $arquivoPath = 'public/assets/files/metodologias/' . $file;
                }
            }
        }
        if ($tipo === 'manual' && !$arquivoPath) {
            header('Location: index.php?route=metodologias/edit&id=' . $id . '&err=arquivo_obrigatorio');
            return;
        }
        $clienteId = (int)($_POST['id_cliente'] ?? 0);
        if ($id && $idPilar && $itemPilar !== '' && $clienteId) {
            $this->model->update($id, [
                'id_pilar' => $idPilar,
                'item_pilar' => $itemPilar,
                'tipo' => $tipo,
                'arquivo_path' => $arquivoPath,
                'observacoes' => $observacoes,
                'cliente_id' => $clienteId,
            ]);
        }
        $redirectCliente = $clienteId ? ('&id=' . $clienteId) : '';
        header('Location: index.php?route=clientes/show' . $redirectCliente);
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) {
            $this->model->delete($id);
        }
        header('Location: index.php?route=metodologias/index');
    }
}
