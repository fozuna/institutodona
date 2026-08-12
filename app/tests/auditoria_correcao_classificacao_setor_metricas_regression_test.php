<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Teste obrigatório do item 10 (Fluxo A): prova que corrigir a
 * classificação (departamento/setor) de uma auditoria Realizada TRANSFERE
 * a contribuição já contabilizada em setor_metricas do setor antigo para o
 * setor novo, sem duplicar e sem perder nada - reproduz exatamente o
 * cenário do backlog aprovado:
 *
 *   Setor A: validas=100, conforme=80   Setor B: validas=50, conforme=40
 *   Auditoria Realizada no Setor A contribui com validas=10, conforme=8
 *   Antes da correção: Setor A = 110/88, Setor B = 50/40 (total 160/128)
 *   Depois de corrigir Setor A -> Setor B: Setor A = 100/80, Setor B = 60/48
 *   (total permanece 160/128 - nenhuma duplicação, nenhuma perda)
 */

$pdo = Database::getConnection();
$suffix = 'audcc_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];

function setorMetricas(\PDO $pdo, int $setorId, string $anoMes): array
{
    $stmt = $pdo->prepare('SELECT total_validas, total_conforme, pct FROM setor_metricas WHERE setor_id = :s AND ano_mes = :am');
    $stmt->execute(['s' => $setorId, 'am' => $anoMes]);
    $row = $stmt->fetch();
    return $row ? [
        'total_validas' => (int)$row['total_validas'],
        'total_conforme' => (int)$row['total_conforme'],
        'pct' => (float)$row['pct'],
    ] : ['total_validas' => 0, 'total_conforme' => 0, 'pct' => 0.0];
}

try {
    $cnpj = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Correcao Classif ' . $suffix, 'c' => $cnpj, 'ct' => 'contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep A ' . $suffix, 'cid' => $clienteId]);
    $depAId = (int)$pdo->lastInsertId();
    $depIds[] = $depAId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $depAId, 'c' => $clienteId]);

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep B ' . $suffix, 'cid' => $clienteId]);
    $depBId = (int)$pdo->lastInsertId();
    $depIds[] = $depBId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $depBId, 'c' => $clienteId]);

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')
        ->execute(['n' => 'Setor A ' . $suffix, 'did' => $depAId]);
    $setorAId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorAId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')
        ->execute(['n' => 'Setor B ' . $suffix, 'did' => $depBId]);
    $setorBId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorBId;

    $anoMesAtual = date('Y-m');
    $pdo->prepare('INSERT INTO setor_metricas (setor_id, ano_mes, total_validas, total_conforme, pct) VALUES (:s,:am,100,80,80.00)')
        ->execute(['s' => $setorAId, 'am' => $anoMesAtual]);
    $pdo->prepare('INSERT INTO setor_metricas (setor_id, ano_mes, total_validas, total_conforme, pct) VALUES (:s,:am,50,40,80.00)')
        ->execute(['s' => $setorBId, 'am' => $anoMesAtual]);
    ok('Seed de baseline: Setor A = 100/80, Setor B = 50/40');

    Auth::login([
        'id' => 3101,
        'nome' => 'Instituto Teste Correcao',
        'email' => 'instituto.correcao@test.local',
        'tipo_acesso' => 'instituto',
        'id_cliente' => null,
    ]);

    $model = new AuditoriaModel();
    $questoesPayload = [];
    for ($i = 1; $i <= 10; $i++) {
        $questoesPayload[] = ['responsavel_nome' => 'Resp ' . $i, 'pergunta' => 'Pergunta ' . $i . ' de conformidade do processo auditado', 'referencia_esperada' => 'POP-' . $i, 'processos' => []];
    }
    $auditoriaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorAId,
        'nome_auditoria' => 'Auditoria Correcao Classif ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => $questoesPayload,
    ], 3101);
    if ($auditoriaId <= 0) { failFast('Falha ao criar auditoria de teste com 10 questões'); }
    $auditoriaIds[] = $auditoriaId;
    ok('Criou auditoria de teste no Setor A com 10 questões');

    $questoes = $model->questoesByAuditoria($auditoriaId);
    if (count($questoes) !== 10) { failFast('Auditoria deveria ter exatamente 10 questões'); }

    // 8 conforme, 2 nao_conforme -> validas=10, conforme=8 (contribuição do enunciado)
    $avaliacoes = [];
    foreach ($questoes as $idx => $q) {
        $avaliacoes[] = [
            'questao_id' => (int)$q['id'],
            'conformidade' => $idx < 8 ? 'conforme' : 'nao_conforme',
            'observacoes' => '',
        ];
    }
    $okFinalizar = $model->finalizarAuditoria($auditoriaId, $avaliacoes, 3101);
    if (!$okFinalizar) { failFast('Finalização deveria ter sucesso. erro=' . (string)$model->getLastError()); }
    $auditoriaFinalizada = $model->find($auditoriaId);
    if (($auditoriaFinalizada['status'] ?? '') !== 'Realizada') { failFast('Status deveria ser Realizada após finalizar'); }
    ok('Auditoria finalizada no Setor A (contribuição validas=10, conforme=8)');

    $antesA = setorMetricas($pdo, $setorAId, $anoMesAtual);
    $antesB = setorMetricas($pdo, $setorBId, $anoMesAtual);
    if ($antesA['total_validas'] !== 110 || $antesA['total_conforme'] !== 88) {
        failFast(sprintf('Setor A deveria estar em 110/88 após finalizar, obtido %d/%d', $antesA['total_validas'], $antesA['total_conforme']));
    }
    if ($antesB['total_validas'] !== 50 || $antesB['total_conforme'] !== 40) {
        failFast(sprintf('Setor B deveria permanecer em 50/40 antes da correção, obtido %d/%d', $antesB['total_validas'], $antesB['total_conforme']));
    }
    ok('Antes da correção: Setor A = 110/88, Setor B = 50/40 (confere com o enunciado)');
    $totalAntes = $antesA['total_validas'] + $antesB['total_validas'];
    $totalConformeAntes = $antesA['total_conforme'] + $antesB['total_conforme'];

    $lockVersion = (int)$auditoriaFinalizada['lock_version'];
    $okCorrigir = $model->corrigirClassificacao(
        $auditoriaId,
        3101,
        $depBId,
        $setorBId,
        'Auditoria cadastrada no setor incorreto.',
        $lockVersion
    );
    if (!$okCorrigir) { failFast('Correção de classificação deveria ter sucesso. erro=' . (string)$model->getLastError()); }
    ok('corrigirClassificacao() executado com sucesso (Setor A -> Setor B)');

    $depoisA = setorMetricas($pdo, $setorAId, $anoMesAtual);
    $depoisB = setorMetricas($pdo, $setorBId, $anoMesAtual);
    if ($depoisA['total_validas'] !== 100 || $depoisA['total_conforme'] !== 80) {
        failFast(sprintf('ESTORNO INCORRETO: Setor A deveria voltar a 100/80, obtido %d/%d', $depoisA['total_validas'], $depoisA['total_conforme']));
    }
    ok('Setor A volta EXATAMENTE ao baseline (100/80) após a transferência');

    if ($depoisB['total_validas'] !== 60 || $depoisB['total_conforme'] !== 48) {
        failFast(sprintf('CRÉDITO INCORRETO: Setor B deveria ficar em 60/48, obtido %d/%d', $depoisB['total_validas'], $depoisB['total_conforme']));
    }
    ok('Setor B recebe exatamente a contribuição transferida (60/48)');

    $totalDepois = $depoisA['total_validas'] + $depoisB['total_validas'];
    $totalConformeDepois = $depoisA['total_conforme'] + $depoisB['total_conforme'];
    if ($totalDepois !== $totalAntes || $totalConformeDepois !== $totalConformeAntes) {
        failFast(sprintf(
            'TOTAL GLOBAL MUDOU: antes=%d/%d depois=%d/%d (nenhuma duplicação/perda é permitida)',
            $totalAntes, $totalConformeAntes, $totalDepois, $totalConformeDepois
        ));
    }
    ok('Total global (Setor A + Setor B) permanece idêntico antes/depois: 160/128 -> 160/128 (nenhuma duplicação, nenhuma perda)');

    $auditoriaCorrigida = $model->find($auditoriaId);
    if ((int)$auditoriaCorrigida['setor_id'] !== $setorBId) { failFast('setor_id da auditoria deveria ter sido atualizado para o Setor B'); }
    if (($auditoriaCorrigida['status'] ?? '') !== 'Realizada') { failFast('Status deveria permanecer Realizada'); }
    if ((string)$auditoriaCorrigida['realizada_at'] !== (string)$auditoriaFinalizada['realizada_at']) { failFast('realizada_at não deveria mudar'); }
    if ((float)$auditoriaCorrigida['conformidade_pct'] !== (float)$auditoriaFinalizada['conformidade_pct']) { failFast('conformidade_pct (nota) não deveria mudar'); }
    if ((string)$auditoriaCorrigida['semaforo'] !== (string)$auditoriaFinalizada['semaforo']) { failFast('semaforo (farol) não deveria mudar'); }
    ok('Auditoria: setor_id atualizado, status/realizada_at/nota/farol preservados');

    $respostasApos = $model->respostasByAuditoria($auditoriaId);
    $todasTravadas = true;
    foreach ($respostasApos as $r) {
        if (empty($r['finalized_at'])) { $todasTravadas = false; }
    }
    if (!$todasTravadas || count($respostasApos) !== 10) { failFast('Respostas deveriam continuar travadas (finalized_at preenchido) e intactas'); }
    ok('Respostas permanecem travadas e intactas (nenhuma nova finalização ocorreu)');

    echo "Auditoria correção de classificação - setor_metricas regression tests passed.\n";
} catch (\Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditoria_historico WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_avaliacoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditoria_questoes WHERE auditoria_id IN ($in)");
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if (!empty($setorIds)) {
        $in = implode(',', array_map('intval', $setorIds));
        $pdo->exec("DELETE FROM setor_metricas WHERE setor_id IN ($in)");
        $pdo->exec("DELETE FROM setores WHERE id IN ($in)");
    }
    if (!empty($depIds)) {
        $in = implode(',', array_map('intval', $depIds));
        $pdo->exec("DELETE FROM departamento_clientes WHERE departamento_id IN ($in)");
        $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
