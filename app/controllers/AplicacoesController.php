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
            if (in_array($status, ['Em Andamento','Concluído'], true) && (!$consultorId || $consultorId === 0)) {
                http_response_code(400);
                echo 'Selecione um consultor para iniciar/avançar a tarefa.';
                return;
            }
            // Normaliza valores vazios
            $dataPrevista = ($dataPrevista === '' ? null : $dataPrevista);
            $consultorId = ($consultorId === 0 ? null : $consultorId);
            $this->aplicacoes->updateStatus($idAplicacao, $status);
            $this->aplicacoes->updateSchedule($idAplicacao, $dataPrevista, $consultorId);
            if (!empty($colabIds)) {
                $this->aplicacoes->setColaboradores($idAplicacao, $colabIds);
            }
            \App\Core\AuditLogger::log('update', 'aplicacao', $idAplicacao, ['status'=>$status,'data_prevista'=>$dataPrevista,'consultor_id'=>$consultorId,'colabs'=>$colabIds, 'obs'=>$obs]);
            $changes = [];
            if (($prev['status'] ?? null) !== $status) { $changes[] = 'Status: ' . (($prev['status'] ?? '') !== '' ? $prev['status'] : '—') . ' → ' . ($status !== '' ? $status : '—'); }
            $prevData = $prev['data_prevista'] ?? null;
            if ($prevData === '') $prevData = null;
            if ($prevData !== $dataPrevista) { $changes[] = 'Data prevista: ' . ($prevData ?: '—') . ' → ' . ($dataPrevista ?: '—'); }
            $prevConsultor = isset($prev['consultor_id']) ? (int)$prev['consultor_id'] : null;
            if ($prevConsultor === 0) $prevConsultor = null;
            if ($prevConsultor !== $consultorId) { $changes[] = 'Consultor: ' . ($prevConsultor ?? '—') . ' → ' . ($consultorId ?? '—'); }
            $added = array_values(array_diff($colabIds, $prevCols));
            $removed = array_values(array_diff($prevCols, $colabIds));
            if ($added || $removed) {
                // Mapeia nomes para melhorar leitura
                $mapNames = [];
                foreach ($this->aplicacoes->colaboradoresForAplicacao($idAplicacao) as $c) { $mapNames[(int)$c['id']] = $c['nome']; }
                if ($added) { $changes[] = 'Colaboradores adicionados: ' . implode(', ', array_map(fn($id)=> $mapNames[$id] ?? $id, $added)); }
                if ($removed) { $changes[] = 'Colaboradores removidos: ' . implode(', ', array_map(fn($id)=> $mapNames[$id] ?? $id, $removed)); }
            }
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
        $error = null;
        if ($id && in_array($status, ['A Fazer','Em Andamento','Concluído','Pendente'], true)) {
            $app = $this->aplicacoes->find($id);
            $needsConsultor = in_array($status, ['Em Andamento','Concluído'], true);
            $hasConsultor = isset($app['consultor_id']) && (int)$app['consultor_id'] > 0;
            if ($needsConsultor && !$hasConsultor) {
                $error = 'Para iniciar/avançar a tarefa, selecione um consultor.';
            } else {
                $ok = $this->aplicacoes->updateStatus($id, $status);
                if ($ok) { \App\Core\AuditLogger::log('update', 'aplicacao', $id, ['status'=>$status]); }
            }
        }
        echo json_encode(['ok' => $ok, 'error' => $error]);
    }

    public function upload(): void
    {
        $this->requireRole('instituto');
        $csrf = $_POST['csrf'] ?? null;
        if (!Security::verifyCsrf($csrf)) { http_response_code(400); echo 'CSRF inválido'; return; }
        $id = (int)($_POST['id_aplicacao'] ?? 0);
        $app = $this->aplicacoes->find($id);
        if (!$app) { http_response_code(404); echo 'Tarefa não encontrada'; return; }
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
        $saved = [];
        $dir = __DIR__ . '/../../public/assets/files/' . (int)$app['id_cliente'] . '/aplicacao_' . (int)$app['id'];
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        // Suporta múltiplos arquivos: 'arquivos'[] ou único 'arquivo'
        $files = [];
        if (!empty($_FILES['arquivos']['name'])) {
            $count = is_array($_FILES['arquivos']['name']) ? count($_FILES['arquivos']['name']) : 0;
            for ($i = 0; $i < $count; $i++) {
                $files[] = [
                    'name' => $_FILES['arquivos']['name'][$i],
                    'type' => $_FILES['arquivos']['type'][$i],
                    'tmp_name' => $_FILES['arquivos']['tmp_name'][$i],
                    'size' => $_FILES['arquivos']['size'][$i],
                    'error' => $_FILES['arquivos']['error'][$i] ?? 0,
                ];
            }
        } elseif (!empty($_FILES['arquivo']['name']) && is_uploaded_file($_FILES['arquivo']['tmp_name'])) {
            $files[] = [
                'name' => $_FILES['arquivo']['name'],
                'type' => $_FILES['arquivo']['type'],
                'tmp_name' => $_FILES['arquivo']['tmp_name'],
                'size' => $_FILES['arquivo']['size'],
                'error' => $_FILES['arquivo']['error'] ?? 0,
            ];
        }
        foreach ($files as $f) {
            if ($f['error'] !== 0 || !is_uploaded_file($f['tmp_name'])) { continue; }
            $type = $f['type'] ?? '';
            $ext = $allow[$type] ?? null;
            if (!$ext) { continue; }
            $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', strtolower($f['name']));
            $file = date('YmdHis') . '-' . ($safe ?: ('arquivo.' . $ext));
            $dest = $dir . '/' . $file;
            if (@move_uploaded_file($f['tmp_name'], $dest)) {
                $rel = 'public/assets/files/' . (int)$app['id_cliente'] . '/aplicacao_' . (int)$app['id'] . '/' . $file;
                $this->aplicacoes->addArquivo((int)$app['id'], (int)$app['id_cliente'], $f['name'], $rel, $type, (int)($f['size'] ?? 0));
                \App\Core\AuditLogger::log('upload', 'aplicacao_arquivo', (int)$app['id'], ['arquivo'=>$f['name'],'path'=>$rel,'mime'=>$type,'size'=>(int)($f['size'] ?? 0)]);
                $saved[] = ['nome_original'=>$f['name'],'arquivo_path'=>$rel,'mime'=>$type,'tamanho'=>(int)($f['size'] ?? 0),'uploaded_at'=>date('Y-m-d H:i:s')];
            }
        }
        if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok'=>true,'files'=>$saved]);
            return;
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
