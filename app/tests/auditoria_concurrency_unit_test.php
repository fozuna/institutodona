<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'cc_' . date('YmdHis') . '_' . random_int(100, 999);
$cnpj = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
$ids = ['clientes' => [], 'departamentos' => [], 'setores' => [], 'funcoes' => [], 'auditorias' => []];

try {
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Empresa Concurrency ' . $suffix, 'c' => $cnpj, 'ct' => 'contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $ids['clientes'][] = $clienteId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep ' . $suffix, 'cid' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $ids['departamentos'][] = $depId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')
        ->execute(['n' => 'Setor ' . $suffix, 'did' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $ids['setores'][] = $setorId;

    $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:sid)')
        ->execute(['n' => 'Func ' . $suffix, 'sid' => $setorId]);
    $funcId = (int)$pdo->lastInsertId();
    $ids['funcoes'][] = $funcId;

    Auth::login([
        'id' => 991,
        'nome' => 'Teste Concurrency',
        'email' => 'cc@test.local',
        'tipo_acesso' => 'consultor',
        'id_cliente' => $clienteId,
    ]);

    $model = new AuditoriaModel();
    $auditoriaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Concurrency ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Responsável',
            'pergunta' => 'Pergunta para validar controle de versão.',
            'referencia_esperada' => 'REF-CC',
            'processos' => [],
        ]],
    ], 991);
    if ($auditoriaId <= 0) {
        failFast('Falha ao criar auditoria para teste de concorrência');
    }
    $ids['auditorias'][] = $auditoriaId;

    $created = $model->find($auditoriaId);
    if ((int)($created['lock_version'] ?? 0) !== 1) {
        failFast('lock_version inicial deveria ser 1');
    }
    ok('lock_version inicial definido');

    $okPartial = $model->updatePartial($auditoriaId, [
        'nome_auditoria' => 'Auditoria Concurrency Atualizada ' . $suffix,
    ], 991, null, 1);
    if (!$okPartial) {
        failFast('Update parcial com versão correta deveria funcionar');
    }
    $after = $model->find($auditoriaId);
    if ((int)($after['lock_version'] ?? 0) !== 2) {
        failFast('lock_version deveria incrementar após atualização');
    }
    ok('Incremento de lock_version após update');

    $stale = $model->updatePartial($auditoriaId, [
        'nome_auditoria' => 'Tentativa stale ' . $suffix,
    ], 991, null, 1);
    if ($stale) {
        failFast('Update com lock_version stale deveria falhar');
    }
    if (($model->getLastError() ?? '') !== 'concurrency_conflict') {
        failFast('Falha stale deveria sinalizar concurrency_conflict');
    }
    ok('Bloqueio de atualização stale por controle otimista');

    echo "All auditoria concurrency unit tests passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($ids['auditorias'])) {
        $in = implode(',', array_map('intval', $ids['auditorias']));
        $pdo->exec("DELETE FROM auditoria_relatorios WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if (!empty($ids['funcoes'])) {
        $in = implode(',', array_map('intval', $ids['funcoes']));
        $pdo->exec("DELETE FROM funcoes WHERE id IN ($in)");
    }
    if (!empty($ids['setores'])) {
        $in = implode(',', array_map('intval', $ids['setores']));
        $pdo->exec("DELETE FROM setores WHERE id IN ($in)");
    }
    if (!empty($ids['departamentos'])) {
        $in = implode(',', array_map('intval', $ids['departamentos']));
        $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
    }
    if (!empty($ids['clientes'])) {
        $in = implode(',', array_map('intval', $ids['clientes']));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
