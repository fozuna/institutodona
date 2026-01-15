<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Security;
use App\Models\AplicacaoModel;
use App\Models\ConsultorModel;
use App\Models\FuncaoModel;

class AplicacoesController extends BaseController
{
    private AplicacaoModel $aplicacoes;

    public function __construct()
    {
        $this->aplicacoes = new AplicacaoModel();
    }

    public function create(): void
    {
        $this->requireRole('instituto');
        $cliente = isset($_GET['cliente']) ? (int)$_GET['cliente'] : 0;
        $consultores = (new ConsultorModel())->all();
        $metModel = new \App\Models\MetodologiaModel();
        $funModel = new FuncaoModel();
        $metodologias = $cliente ? $metModel->byCliente($cliente) : $metModel->all();
        $funcoes = $cliente ? $funModel->allByCliente($cliente) : [];
        $clientes = (new \App\Models\ClienteModel())->all();
        $this->render('aplicacoes/create', [
            'cliente' => $cliente,
            'clientes' => $clientes,
            'metodologias' => $metodologias,
            'funcoes' => $funcoes,
            'consultores' => $consultores,
        ]);
    }

    public function show(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $app = $this->aplicacoes->find($id);
        $consultores = (new ConsultorModel())->all();
        $colabs = $app ? $this->aplicacoes->colaboradoresForAplicacao((int)$app['id']) : [];
        $arquivos = $app ? $this->aplicacoes->arquivosForAplicacao((int)$app['id']) : [];
        $updates = $app ? $this->aplicacoes->updatesForAplicacao((int)$app['id']) : [];
        $this->render('aplicacoes/show', [
            'app' => $app,
            'consultores' => $consultores,
            'colabs' => $colabs,
            'arquivos' => $arquivos,
            'updates' => $updates,
        ]);
    }

    public function update(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $idAplicacao = (int)($_POST['id_aplicacao'] ?? 0);
        $status = $_POST['status'] ?? 'A Fazer';
        $dataPrevista = $_POST['data_prevista'] ?? null;
        $consultorId = isset($_POST['consultor_id']) ? (int)$_POST['consultor_id'] : null;
        $colabIds = isset($_POST['colaborador_ids']) ? (array)$_POST['colaborador_ids'] : [];
        $colabIds = array_values(array_filter(array_map('intval', $colabIds)));
        $obs = trim($_POST['observacao_update'] ?? '');
        if ($idAplicacao) {
            $prev = $this->aplicacoes->find($idAplicacao);
            $prevCols = array_map(fn($r)=> (int)$r['id'], $this->aplicacoes->colaboradoresForAplicacao($idAplicacao));
            $this->aplicacoes->updateStatus($idAplicacao, $status);
            $this->aplicacoes->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
            if (!empty($colabIds)) {
                $this->aplicacoes->setColaboradores($idAplicacao, $colabIds);
            }
            \App\Core\AuditLogger::log('update', 'aplicacao', $idAplicacao, ['status'=>$status,'data_prevista'=>$dataPrevista,'consultor_id'=>$consultorId,'colabs'=>$colabIds, 'obs'=>$obs]);
            $changes = [];
            if (($prev['status'] ?? null) !== $status) { $changes[] = 'Status: ' . ($prev['status'] ?? '—') . ' → ' . $status; }
            if (($prev['data_prevista'] ?? null) !== $dataPrevista) { $changes[] = 'Data prevista: ' . ($prev['data_prevista'] ?? '—') . ' → ' . ($dataPrevista ?: '—'); }
            if ((int)($prev['consultor_id'] ?? 0) !== (int)$consultorId) { $changes[] = 'Consultor: ' . (int)($prev['consultor_id'] ?? 0) . ' → ' . (int)$consultorId; }
            $added = array_values(array_diff($colabIds, $prevCols));
            $removed = array_values(array_diff($prevCols, $colabIds));
            if ($added) { $changes[] = 'Colaboradores adicionados: ' . implode(', ', $added); }
            if ($removed) { $changes[] = 'Colaboradores removidos: ' . implode(', ', $removed); }
            $summary = ($obs !== '' ? ('Obs: ' . $obs . ' — ') : '') . (empty($changes) ? 'Sem alterações de campos' : implode(' | ', $changes));
            $user = $_SESSION['user'] ?? [];
            $this->aplicacoes->addUpdate($idAplicacao, (string)($user['email'] ?? ''), (string)($user['nome'] ?? ''), $summary, [
                'antes' => ['status'=>$prev['status'] ?? null,'data_prevista'=>$prev['data_prevista'] ?? null,'consultor_id'=>$prev['consultor_id'] ?? null,'colabs'=>$prevCols],
                'depois' => ['status'=>$status,'data_prevista'=>$dataPrevista,'consultor_id'=>$consultorId,'colabs'=>$colabIds],
            ]);
        }
        header('Location: index.php?route=aplicacoes/show&id=' . $idAplicacao);
    }

    public function set_status(): void
    {
        $this->requireLogin();
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $ok = false;
        if ($id && in_array($status, ['A Fazer','Em Andamento','Concluído','Pendente'], true)) {
            $ok = $this->aplicacoes->updateStatus($id, $status);
            if ($ok) { \App\Core\AuditLogger::log('update', 'aplicacao', $id, ['status'=>$status]); }
        }
        echo json_encode(['ok' => $ok]);
    }

    public function upload(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id_aplicacao'] ?? 0);
        $app = $this->aplicacoes->find($id);
        if (!$app) { http_response_code(404); echo 'Tarefa não encontrada'; return; }
        if (!empty($_FILES['arquivo']['name']) && is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
            $allow = [
                'application/pdf' => 'pdf',
                'image/png' => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'text/plain' => 'txt',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx'
            ];
            $type = $_FILES['arquivo']['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if ($ext) {
                $dir = __DIR__ . '/../../public/assets/files/' . (int)$app['id_cliente'] . '/aplicacao_' . (int)$app['id'];
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', strtolower($_FILES['arquivo']['name']));
                $file = date('YmdHis') . '-' . ($safe ?: ('arquivo.' . $ext));
                $dest = $dir . '/' . $file;
                if (@move_uploaded_file($_FILES['arquivo']['tmp_name'], $dest)) {
                    $rel = 'public/assets/files/' . (int)$app['id_cliente'] . '/aplicacao_' . (int)$app['id'] . '/' . $file;
                    $this->aplicacoes->addArquivo((int)$app['id'], (int)$app['id_cliente'], $_FILES['arquivo']['name'], $rel, $type, (int)($_FILES['arquivo']['size'] ?? 0));
                    \App\Core\AuditLogger::log('upload', 'aplicacao_arquivo', (int)$app['id'], ['arquivo'=>$_FILES['arquivo']['name'],'path'=>$rel,'mime'=>$type,'size'=>(int)($_FILES['arquivo']['size'] ?? 0)]);
                }
            }
        }
        header('Location: index.php?route=aplicacoes/show&id=' . $id);
    }

    public function delete_update(): void
    {
        $this->requireRole('instituto');
        $id = (int)($_GET['id'] ?? 0);
        $ap = (int)($_GET['ap'] ?? 0);
        if ($id && $ap) { $this->aplicacoes->deleteUpdate($id, $ap); }
        header('Location: index.php?route=aplicacoes/show&id=' . $ap);
    }
}
