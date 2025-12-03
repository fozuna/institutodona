<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\AplicacaoModel;
use App\Models\MetodologiaModel;

class ClientesController extends BaseController
{
    private ClienteModel $clientes;

    public function __construct()
    {
        $this->clientes = new ClienteModel();
    }

    public function index(): void
    {
        $this->requireRole('instituto');
        $items = $this->clientes->all();
        $this->render('clientes/index', ['items' => $items]);
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $this->render('clientes/create');
    }

    public function store(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
        ];
        $data['logo_path'] = null;
        if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $allow = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            $type = $_FILES['logo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public/assets/img/clients';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['nome_empresa']));
                $name = $safe ? $safe : 'cliente';
                $file = $name . '-' . uniqid() . '.' . $ext;
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $data['logo_path'] = 'public/assets/img/clients/' . $file;
                }
            }
        }
        if ($data['logo_path'] === null) {
            $current = $this->clientes->find($id);
            if ($current && !empty($current['logo_path'])) { $data['logo_path'] = $current['logo_path']; }
        }
        if ($data['nome_empresa'] && $data['CNPJ']) {
            $this->clientes->create($data);
        }
        header('Location: index.php?route=clientes/index');
    }

    public function edit(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->clientes->find($id);
        $this->render('clientes/edit', ['item' => $item]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
        ];
        $data['logo_path'] = null;
        if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
            $allow = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];
            $type = $_FILES['logo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public/assets/img/clients';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', strtolower($data['nome_empresa']));
                $name = $safe ? $safe : 'cliente';
                $file = $name . '-' . uniqid() . '.' . $ext;
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                    $data['logo_path'] = 'public/assets/img/clients/' . $file;
                }
            }
        }
        if ($id) { $this->clientes->update($id, $data); }
        header('Location: index.php?route=clientes/index');
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $this->clientes->delete($id); }
        header('Location: index.php?route=clientes/index');
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $item = $this->clientes->find($id);
        $apl = new AplicacaoModel();
        $apps = $apl->byCliente($id);
        $met = new MetodologiaModel();
        $metodologias = $met->all();
        $this->render('clientes/show', [
            'item' => $item,
            'apps' => $apps,
            'metodologias' => $metodologias,
        ]);
    }

    public function attach(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCliente = (int)($_POST['id_cliente'] ?? 0);
        $idMetodologia = (int)($_POST['id_metodologia'] ?? 0);
        $status = $_POST['status'] ?? 'A Fazer';
        $consultorId = isset($_POST['consultor_id']) ? (int)$_POST['consultor_id'] : null;
        $dataPrevista = $_POST['data_prevista'] ?? null;
        if ($idCliente && $idMetodologia) {
            (new AplicacaoModel())->create($idCliente, $idMetodologia, $status, $consultorId, $dataPrevista);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }

    public function updateAplicacao(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idCliente = (int)($_POST['id_cliente'] ?? 0);
        $idAplicacao = (int)($_POST['id_aplicacao'] ?? 0);
        $status = $_POST['status'] ?? 'A Fazer';
        $dataPrevista = $_POST['data_prevista'] ?? null;
        $consultorId = isset($_POST['consultor_id']) ? (int)$_POST['consultor_id'] : null;
        if ($idAplicacao) {
            (new AplicacaoModel())->updateStatus($idAplicacao, $status);
            (new AplicacaoModel())->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }

    public function deleteAplicacao(): void
    {
        $this->requireRole('instituto');
        $idCliente = (int)($_GET['id_cliente'] ?? 0);
        $idAplicacao = (int)($_GET['id_aplicacao'] ?? 0);
        if ($idAplicacao) {
            (new AplicacaoModel())->delete($idAplicacao);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }
}
