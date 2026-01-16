<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\ClienteModel;
use App\Models\AplicacaoModel;
use App\Models\MetodologiaModel;
use App\Models\PilarModel;
use App\Models\FuncaoModel;

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
        $items = $this->clientes->matrizes();
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
        // Matriz/Filial
        $tipo = $_POST['tipo_unidade'] ?? 'matriz';
        $matrizId = isset($_POST['matriz_id']) ? (int)$_POST['matriz_id'] : null;
        $data['is_matriz'] = $tipo === 'matriz' ? 1 : 0;
        $data['matriz_id'] = $tipo === 'filial' ? $matrizId : null;
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
        if ($data['nome_empresa'] && $data['CNPJ']) {
            $id = $this->clientes->create($data);
            \App\Core\AuditLogger::log('create', 'cliente', $id, $data);
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
        $tipo = $_POST['tipo_unidade'] ?? 'matriz';
        $matrizId = isset($_POST['matriz_id']) ? (int)$_POST['matriz_id'] : null;
        $data['is_matriz'] = $tipo === 'matriz' ? 1 : 0;
        $data['matriz_id'] = $tipo === 'filial' ? $matrizId : null;
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
        if ($id) { $this->clientes->update($id, $data); \App\Core\AuditLogger::log('update', 'cliente', $id, $data); }
        header('Location: index.php?route=clientes/index');
    }

    public function delete(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        if ($id) { $this->clientes->delete($id); \App\Core\AuditLogger::log('delete', 'cliente', $id, []); }
        header('Location: index.php?route=clientes/index');
    }

    public function show(): void
    {
        $this->requireLogin();
        $user = $_SESSION['user'] ?? [];
        $id = (int)($_GET['id'] ?? 0);
        $tipo = $user['tipo_acesso'] ?? null;
        if ($tipo === 'cliente' && (int)($user['id_cliente'] ?? 0) !== $id) {
            http_response_code(403);
            echo 'Acesso negado';
            return;
        }
        $item = $this->clientes->find($id);
        $apl = new AplicacaoModel();
        $statusFilter = $_GET['status'] ?? '';
        $consultorFilter = isset($_GET['consultor']) ? (int)$_GET['consultor'] : 0;
        $apps = $apl->byClienteWithFilters($id, [
            'status' => $statusFilter ?: null,
            'consultor_id' => $consultorFilter ?: null,
        ]);
        $met = new MetodologiaModel();
        $metodologias = $met->byCliente($id);
        $pilares = (new PilarModel())->all();
        $funcoes = (new FuncaoModel())->allByCliente($id);
        $filiais = $this->clientes->filiaisByMatriz($id);
        $matrizes = $this->clientes->matrizes();
        $avaliacoes = (new \App\Models\AvaliacaoModel())->byCliente($id);
        $arquivosCliente = [];
        foreach ($apps as $row) {
            foreach ($apl->arquivosForAplicacao((int)$row['id']) as $f) {
                $arquivosCliente[] = [
                    'aplicacao_id' => (int)$row['id'],
                    'pilar' => $row['pilar_nome'],
                    'tarefa' => $row['item_pilar'],
                    'nome' => $f['nome_original'],
                    'path' => $f['arquivo_path'],
                    'mime' => $f['mime'],
                    'tamanho' => $f['tamanho'],
                    'uploaded_at' => $f['uploaded_at'],
                ];
            }
        }
        $this->render('clientes/show', [
            'item' => $item,
            'apps' => $apps,
            'metodologias' => $metodologias,
            'pilares' => $pilares,
            'funcoes' => $funcoes,
            'filiais' => $filiais,
            'matrizes' => $matrizes,
            'avaliacoes' => $avaliacoes,
            'statusFilter' => $statusFilter,
            'consultorFilter' => $consultorFilter,
            'arquivosCliente' => $arquivosCliente,
        ]);
    }

    public function storeFilial(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $matrizId = (int)($_POST['matriz_id'] ?? 0);
        $data = [
            'nome_empresa' => trim($_POST['nome_empresa'] ?? ''),
            'CNPJ' => trim($_POST['CNPJ'] ?? ''),
            'contato' => trim($_POST['contato'] ?? ''),
            'is_matriz' => 0,
            'matriz_id' => $matrizId ?: null,
        ];
        if ($data['nome_empresa'] && $data['CNPJ'] && $matrizId) {
            $this->clientes->create($data);
        }
        header('Location: index.php?route=clientes/show&id=' . $matrizId);
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
        $colabIds = isset($_POST['colaborador_ids']) ? (array)$_POST['colaborador_ids'] : [];
        $colabIds = array_values(array_filter(array_map('intval', $colabIds)));
        if (empty($colabIds)) { http_response_code(400); echo 'Selecione ao menos um Colaborador'; return; }
        if ($idCliente && $idMetodologia) {
            $aplId = (new AplicacaoModel())->create($idCliente, $idMetodologia, $status, $consultorId, $dataPrevista);
            (new AplicacaoModel())->addColaboradores($aplId, $colabIds);
            \App\Core\AuditLogger::log('create', 'aplicacao', $aplId, ['id_cliente'=>$idCliente,'id_metodologia'=>$idMetodologia,'status'=>$status,'consultor_id'=>$consultorId,'data_prevista'=>$dataPrevista,'colabs'=>$colabIds]);
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
        $colabIds = isset($_POST['colaborador_ids']) ? (array)$_POST['colaborador_ids'] : [];
        $colabIds = array_values(array_filter(array_map('intval', $colabIds)));
        if ($idAplicacao) {
            (new AplicacaoModel())->updateStatus($idAplicacao, $status);
            (new AplicacaoModel())->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
            if (!empty($colabIds)) { (new AplicacaoModel())->setColaboradores($idAplicacao, $colabIds); }
            \App\Core\AuditLogger::log('update', 'aplicacao', $idAplicacao, ['status'=>$status,'data_prevista'=>$dataPrevista,'consultor_id'=>$consultorId,'colabs'=>$colabIds]);
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
            \App\Core\AuditLogger::log('delete', 'aplicacao', $idAplicacao, ['id_cliente'=>$idCliente]);
        }
        header('Location: index.php?route=clientes/show&id=' . $idCliente);
    }
}
