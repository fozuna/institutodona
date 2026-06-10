<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\ColaboradorModel;
use App\Models\TreinamentoModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
if (!Database::tableExists('colaborador_status_logs')) {
    failFast('Tabela colaborador_status_logs não existe (migration não aplicada).');
}

$suffix = 'st_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$departamentoIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$treinamentoIds = [];
$treinColabIds = [];
$logIds = [];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:n,:c,:t)')
        ->execute(['n' => 'Cliente Status ' . $suffix, 'c' => '33.333.333/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    Auth::login([
        'id' => 9101,
        'nome' => 'Teste Status',
        'email' => 'status.' . $suffix . '@test.local',
        'tipo_acesso' => 'cliente',
        'id_cliente' => $clienteId,
        'allowed_client_ids' => [$clienteId],
    ]);

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:c)')
        ->execute(['n' => 'Departamento Status ' . $suffix, 'c' => $clienteId]);
    $departamentoId = (int)$pdo->lastInsertId();
    $departamentoIds[] = $departamentoId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:d)')
        ->execute(['n' => 'Setor Status ' . $suffix, 'd' => $departamentoId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:s)')
        ->execute(['n' => 'Funcao Status ' . $suffix, 's' => $setorId]);
    $funcaoId = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcaoId;

    $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, cliente_id, ativo) VALUES (:n,:e,:f,:c,1)')
        ->execute([
            'n' => 'Colaborador Status ' . $suffix,
            'e' => 'colab.status.' . $suffix . '@test.local',
            'f' => $funcaoId,
            'c' => $clienteId,
        ]);
    $colaboradorId = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorId;
    ok('Setup base criado');

    $treinamentoModel = new TreinamentoModel();
    $treinamentoId = $treinamentoModel->create([
        'nome' => 'Treinamento Status ' . $suffix,
        'objetivo' => 'Validar bloqueio de inativação',
        'publico' => 'Equipe',
        'carga_horaria' => '2',
        'departamento_id' => $departamentoId,
        'periodicidade' => 'anual',
        'fornecedor' => 'Fornecedor',
        'tipo_treinamento' => 'Integracao',
        'template_certificado' => 'Template',
        'assinatura_responsavel' => 'Responsavel',
        'setor_ids' => [$setorId],
        'funcao_ids' => [$funcaoId],
    ]);
    if ($treinamentoId <= 0) {
        failFast('Falha ao criar treinamento');
    }
    $treinamentoIds[] = $treinamentoId;
    ok('Treinamento criado');

    $pdo->prepare("INSERT INTO treinamento_colaboradores (treinamento_id, colaborador_id, status) VALUES (:t,:c,'pendente')")
        ->execute(['t' => $treinamentoId, 'c' => $colaboradorId]);
    $treinColabIds[] = (int)$pdo->lastInsertId();
    ok('Vínculo de treinamento pendente criado');

    $model = new ColaboradorModel();
    $res = $model->updateWithStatusAudit(
        $colaboradorId,
        ['nome' => 'Colaborador Status ' . $suffix, 'email' => 'colab.status.' . $suffix . '@test.local', 'funcao_id' => $funcaoId, 'lider' => 'não', 'cliente_id' => $clienteId],
        0,
        9101,
        'Inativação por teste',
        '127.0.0.1',
        'test-agent'
    );
    if (!empty($res['ok'])) {
        failFast('Inativação deveria ser bloqueada por treinamento pendente.');
    }
    ok('Inativação bloqueada por vínculo pendente');

    $stmt = $pdo->prepare('SELECT ativo FROM colaboradores WHERE id = :id');
    $stmt->execute(['id' => $colaboradorId]);
    if ((int)$stmt->fetchColumn() !== 1) {
        failFast('Colaborador foi inativado mesmo com bloqueio.');
    }
    ok('Ativo permanece 1 após bloqueio');

    $pdo->prepare('DELETE FROM treinamento_colaboradores WHERE treinamento_id = :t AND colaborador_id = :c')
        ->execute(['t' => $treinamentoId, 'c' => $colaboradorId]);
    ok('Vínculo pendente removido');

    $res2 = $model->updateWithStatusAudit(
        $colaboradorId,
        ['nome' => 'Colaborador Status ' . $suffix, 'email' => 'colab.status.' . $suffix . '@test.local', 'funcao_id' => $funcaoId, 'lider' => 'não', 'cliente_id' => $clienteId],
        0,
        9101,
        'Inativação por desligamento',
        '127.0.0.1',
        'test-agent'
    );
    if (empty($res2['ok'])) {
        failFast('Inativação deveria ser permitida sem vínculos pendentes: ' . json_encode($res2, JSON_UNESCAPED_UNICODE));
    }
    ok('Inativação permitida');

    $stmt->execute(['id' => $colaboradorId]);
    if ((int)$stmt->fetchColumn() !== 0) {
        failFast('Colaborador não foi inativado.');
    }
    ok('Ativo atualizado para 0');

    $stmtLog = $pdo->prepare('SELECT id, old_ativo, new_ativo, justificativa, changed_by FROM colaborador_status_logs WHERE colaborador_id = :id ORDER BY id DESC LIMIT 1');
    $stmtLog->execute(['id' => $colaboradorId]);
    $log = $stmtLog->fetch();
    if (!$log || (int)($log['old_ativo'] ?? -1) !== 1 || (int)($log['new_ativo'] ?? -1) !== 0 || trim((string)($log['justificativa'] ?? '')) === '' || (int)($log['changed_by'] ?? 0) !== 9101) {
        failFast('Log de status inválido: ' . json_encode($log, JSON_UNESCAPED_UNICODE));
    }
    $logIds[] = (int)$log['id'];
    ok('Log de inativação registrado');

    $res3 = $model->updateWithStatusAudit(
        $colaboradorId,
        ['nome' => 'Colaborador Status ' . $suffix, 'email' => 'colab.status.' . $suffix . '@test.local', 'funcao_id' => $funcaoId, 'lider' => 'não', 'cliente_id' => $clienteId],
        1,
        9101,
        'Reativação por teste',
        '127.0.0.1',
        'test-agent'
    );
    if (empty($res3['ok'])) {
        failFast('Ativação deveria ser permitida: ' . json_encode($res3, JSON_UNESCAPED_UNICODE));
    }
    ok('Ativação permitida');

    $stmt->execute(['id' => $colaboradorId]);
    if ((int)$stmt->fetchColumn() !== 1) {
        failFast('Colaborador não foi reativado.');
    }
    ok('Ativo atualizado para 1');

    $stmtLog->execute(['id' => $colaboradorId]);
    $log2 = $stmtLog->fetch();
    if (!$log2 || (int)($log2['old_ativo'] ?? -1) !== 0 || (int)($log2['new_ativo'] ?? -1) !== 1) {
        failFast('Log de ativação inválido: ' . json_encode($log2, JSON_UNESCAPED_UNICODE));
    }
    $logIds[] = (int)$log2['id'];
    ok('Log de ativação registrado');

    $res4 = $model->updateWithStatusAudit(
        $colaboradorId,
        ['nome' => 'Colaborador Status ' . $suffix, 'email' => 'colab.status.' . $suffix . '@test.local', 'funcao_id' => $funcaoId, 'lider' => 'não', 'cliente_id' => $clienteId],
        0,
        9101,
        '',
        '127.0.0.1',
        'test-agent'
    );
    if (!empty($res4['ok'])) {
        failFast('Justificativa vazia deveria bloquear alteração.');
    }
    ok('Justificativa obrigatória validada');

    echo "All colaboradores status change integration tests passed.\n";
} finally {
    if (!empty($logIds)) {
        $in = implode(',', array_fill(0, count($logIds), '?'));
        $pdo->prepare("DELETE FROM colaborador_status_logs WHERE id IN ($in)")->execute($logIds);
    }
    if (!empty($treinColabIds)) {
        $in = implode(',', array_fill(0, count($treinColabIds), '?'));
        $pdo->prepare("DELETE FROM treinamento_colaboradores WHERE id IN ($in)")->execute($treinColabIds);
    }
    if (!empty($treinamentoIds)) {
        $in = implode(',', array_fill(0, count($treinamentoIds), '?'));
        $pdo->prepare("DELETE FROM treinamentos WHERE id IN ($in)")->execute($treinamentoIds);
    }
    if (!empty($colaboradorIds)) {
        $in = implode(',', array_fill(0, count($colaboradorIds), '?'));
        $pdo->prepare("DELETE FROM colaboradores WHERE id IN ($in)")->execute($colaboradorIds);
    }
    if (!empty($funcaoIds)) {
        $in = implode(',', array_fill(0, count($funcaoIds), '?'));
        $pdo->prepare("DELETE FROM funcoes WHERE id IN ($in)")->execute($funcaoIds);
    }
    if (!empty($setorIds)) {
        $in = implode(',', array_fill(0, count($setorIds), '?'));
        $pdo->prepare("DELETE FROM setores WHERE id IN ($in)")->execute($setorIds);
    }
    if (!empty($departamentoIds)) {
        $in = implode(',', array_fill(0, count($departamentoIds), '?'));
        $pdo->prepare("DELETE FROM departamentos WHERE id IN ($in)")->execute($departamentoIds);
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_fill(0, count($clienteIds), '?'));
        $pdo->prepare("DELETE FROM clientes WHERE id IN ($in)")->execute($clienteIds);
    }
}

