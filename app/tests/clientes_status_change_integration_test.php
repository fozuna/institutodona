<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\ClienteModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
if (!Database::tableExists('cliente_status_logs')) {
    failFast('Tabela cliente_status_logs não existe (migration não aplicada).');
}

$suffix = 'cst_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$tarefaIds = [];
$logIds = [];

try {
    Auth::login([
        'id' => 9202,
        'nome' => 'Teste Status Cliente',
        'email' => 'status.cliente.' . $suffix . '@test.local',
        'tipo_acesso' => 'instituto',
        'allowed_client_ids' => [],
    ]);

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, ativo) VALUES (:n,:c,:t,1)')
        ->execute(['n' => 'Cliente Status ' . $suffix, 'c' => '44.444.444/0001-' . random_int(10, 99), 't' => 'Contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;
    ok('Cliente criado ativo');

    $model = new ClienteModel();

    $ativos = array_map(static fn(array $c): int => (int)$c['id'], $model->allActive());
    if (!in_array($clienteId, $ativos, true)) {
        failFast('Cliente recém-criado deveria aparecer em allActive().');
    }
    ok('allActive() inclui o cliente recém-criado');

    $pdo->prepare("INSERT INTO tarefas (cliente_id, titulo, data_inicio, prioridade, status) VALUES (:c,:t,:d,'media','Planejado')")
        ->execute(['c' => $clienteId, 't' => 'Tarefa pendente ' . $suffix, 'd' => date('Y-m-d')]);
    $tarefaId = (int)$pdo->lastInsertId();
    $tarefaIds[] = $tarefaId;
    ok('Tarefa em aberto criada para o cliente');

    $data = ['nome_empresa' => 'Cliente Status ' . $suffix, 'CNPJ' => '44.444.444/0001-' . random_int(10, 99), 'contato' => 'Contato', 'is_matriz' => 1, 'matriz_id' => null, 'logo_path' => null];

    $res = $model->updateWithStatusAudit($clienteId, $data, 0, 9202, 'Encerramento de contrato', '127.0.0.1', 'test-agent');
    if (!empty($res['ok'])) {
        failFast('Inativação deveria ser bloqueada por tarefa em aberto.');
    }
    ok('Inativação bloqueada por tarefa em aberto: ' . $res['message']);

    $stmt = $pdo->prepare('SELECT ativo FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $clienteId]);
    if ((int)$stmt->fetchColumn() !== 1) {
        failFast('Cliente foi inativado mesmo com bloqueio.');
    }
    ok('Ativo permanece 1 após bloqueio');

    $pdo->prepare("UPDATE tarefas SET status = 'Finalizado' WHERE id = :id")->execute(['id' => $tarefaId]);
    ok('Tarefa finalizada, pendência removida');

    $res2 = $model->updateWithStatusAudit($clienteId, $data, 0, 9202, 'Encerramento de contrato', '127.0.0.1', 'test-agent');
    if (empty($res2['ok'])) {
        failFast('Inativação deveria ser permitida sem pendências: ' . json_encode($res2, JSON_UNESCAPED_UNICODE));
    }
    ok('Inativação permitida sem pendências');

    $stmt->execute(['id' => $clienteId]);
    if ((int)$stmt->fetchColumn() !== 0) {
        failFast('Cliente não foi inativado.');
    }
    ok('Ativo atualizado para 0');

    $stmtLog = $pdo->prepare('SELECT id, old_ativo, new_ativo, justificativa, changed_by FROM cliente_status_logs WHERE cliente_id = :id ORDER BY id DESC LIMIT 1');
    $stmtLog->execute(['id' => $clienteId]);
    $log = $stmtLog->fetch();
    if (!$log || (int)($log['old_ativo'] ?? -1) !== 1 || (int)($log['new_ativo'] ?? -1) !== 0 || trim((string)($log['justificativa'] ?? '')) === '' || (int)($log['changed_by'] ?? 0) !== 9202) {
        failFast('Log de status inválido: ' . json_encode($log, JSON_UNESCAPED_UNICODE));
    }
    $logIds[] = (int)$log['id'];
    ok('Log de inativação registrado');

    $ativos2 = array_map(static fn(array $c): int => (int)$c['id'], $model->allActive());
    if (in_array($clienteId, $ativos2, true)) {
        failFast('Cliente inativo não deveria aparecer em allActive().');
    }
    ok('allActive() exclui o cliente inativo');

    $res3 = $model->updateWithStatusAudit($clienteId, $data, 0, 9202, '', '127.0.0.1', 'test-agent');
    if (!empty($res3['ok'])) {
        failFast('Nenhuma mudança de status (já inativo) deveria retornar ok=false sem gravar log novo.');
    }
    ok('Chamada sem mudança de status não altera nada');

    $res4 = $model->updateWithStatusAudit($clienteId, $data, 1, 9202, '', '127.0.0.1', 'test-agent');
    if (!empty($res4['ok'])) {
        failFast('Justificativa vazia deveria bloquear reativação.');
    }
    ok('Justificativa obrigatória validada na reativação');

    $res5 = $model->updateWithStatusAudit($clienteId, $data, 1, 9202, 'Contrato renovado', '127.0.0.1', 'test-agent');
    if (empty($res5['ok'])) {
        failFast('Reativação deveria ser permitida: ' . json_encode($res5, JSON_UNESCAPED_UNICODE));
    }
    ok('Reativação permitida');

    $stmt->execute(['id' => $clienteId]);
    if ((int)$stmt->fetchColumn() !== 1) {
        failFast('Cliente não foi reativado.');
    }
    $stmtLog->execute(['id' => $clienteId]);
    $log2 = $stmtLog->fetch();
    $logIds[] = (int)($log2['id'] ?? 0);
    ok('Ativo atualizado para 1 e log de reativação registrado');

    // Filial não pode ser ativada se a matriz estiver inativa
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, ativo, is_matriz, matriz_id) VALUES (:n,:c,:t,0,1,NULL)')
        ->execute(['n' => 'Matriz Status ' . $suffix, 'c' => '55.555.555/0001-' . random_int(10, 99), 't' => 'Contato']);
    $matrizId = (int)$pdo->lastInsertId();
    $clienteIds[] = $matrizId;

    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, ativo, is_matriz, matriz_id) VALUES (:n,:c,:t,0,0,:m)')
        ->execute(['n' => 'Filial Status ' . $suffix, 'c' => '66.666.666/0001-' . random_int(10, 99), 't' => 'Contato', 'm' => $matrizId]);
    $filialId = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialId;
    ok('Matriz inativa e filial inativa criadas para teste de bloqueio cruzado');

    $filialData = ['nome_empresa' => 'Filial Status ' . $suffix, 'CNPJ' => '66.666.666/0001-' . random_int(10, 99), 'contato' => 'Contato', 'is_matriz' => 0, 'matriz_id' => $matrizId, 'logo_path' => null];
    $res6 = $model->updateWithStatusAudit($filialId, $filialData, 1, 9202, 'Tentando ativar filial', '127.0.0.1', 'test-agent');
    if (!empty($res6['ok'])) {
        failFast('Ativação da filial deveria ser bloqueada por matriz inativa.');
    }
    ok('Ativação de filial bloqueada quando a matriz está inativa: ' . $res6['message']);

    echo "All clientes status change integration tests passed.\n";
} finally {
    if (!empty($logIds)) {
        $ids = array_values(array_unique(array_filter($logIds)));
        if (!empty($ids)) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM cliente_status_logs WHERE id IN ($in)")->execute($ids);
        }
    }
    if (!empty($tarefaIds)) {
        $in = implode(',', array_fill(0, count($tarefaIds), '?'));
        $pdo->prepare("DELETE FROM tarefas WHERE id IN ($in)")->execute($tarefaIds);
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_fill(0, count($clienteIds), '?'));
        $pdo->prepare("DELETE FROM clientes WHERE id IN ($in)")->execute($clienteIds);
    }
}
