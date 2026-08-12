<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Teste obrigatório do item 10 (Fluxo B): prova que
 * finalizar -> reabrir -> finalizar novamente NUNCA duplica setor_metricas.
 * Ver SetorMetricaModel::registrarConclusao() (aditivo, não idempotente) e
 * AuditoriaModel::reabrirAuditoria() (estorna a contribuição ANTES de
 * destravar respostas).
 */

$pdo = Database::getConnection();
$suffix = 'audrb_' . date('YmdHis') . '_' . random_int(100, 999);
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
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)');
    $insCli->execute(['n' => 'Cliente Reabertura ' . $suffix, 'c' => $cnpj, 'ct' => 'contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $insDep = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)');
    $insDep->execute(['n' => 'Dep Reabertura ' . $suffix, 'cid' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $depIds[] = $depId;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $depId, 'c' => $clienteId]);

    $insSet = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)');
    $insSet->execute(['n' => 'Setor Reabertura ' . $suffix, 'did' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    Auth::login([
        'id' => 3001,
        'nome' => 'Instituto Teste',
        'email' => 'instituto.reabertura@test.local',
        'tipo_acesso' => 'instituto',
        'id_cliente' => null,
    ]);

    $model = new AuditoriaModel();
    $auditoriaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Reabertura ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [
            ['responsavel_nome' => 'Resp 1', 'pergunta' => 'Pergunta 1 de conformidade do processo auditado', 'referencia_esperada' => 'POP-1', 'processos' => []],
            ['responsavel_nome' => 'Resp 2', 'pergunta' => 'Pergunta 2 de conformidade do processo auditado', 'referencia_esperada' => 'POP-2', 'processos' => []],
        ],
    ], 3001);
    if ($auditoriaId <= 0) { failFast('Falha ao criar auditoria de teste'); }
    $auditoriaIds[] = $auditoriaId;
    ok('Criou auditoria com 2 questões');

    $questoes = $model->questoesByAuditoria($auditoriaId);
    if (count($questoes) !== 2) { failFast('Auditoria deveria ter exatamente 2 questões'); }
    $q1 = (int)$questoes[0]['id'];
    $q2 = (int)$questoes[1]['id'];

    $anoMesAtual = date('Y-m');
    $baseline = setorMetricas($pdo, $setorId, $anoMesAtual);
    ok('Capturou baseline de setor_metricas antes de qualquer finalização (validas=' . $baseline['total_validas'] . ', conforme=' . $baseline['total_conforme'] . ')');

    // ===================== PRIMEIRA FINALIZAÇÃO =====================
    // Q1 conforme, Q2 não conforme -> validas=2, conforme=1.
    $ok1 = $model->finalizarAuditoria($auditoriaId, [
        ['questao_id' => $q1, 'conformidade' => 'conforme', 'observacoes' => ''],
        ['questao_id' => $q2, 'conformidade' => 'nao_conforme', 'observacoes' => ''],
    ], 3001);
    if (!$ok1) { failFast('Primeira finalização deveria ter sucesso. erro=' . (string)$model->getLastError()); }
    $auditoria1 = $model->find($auditoriaId);
    if (($auditoria1['status'] ?? '') !== 'Realizada') { failFast('Status deveria ser Realizada após finalizar'); }
    if (empty($auditoria1['realizada_at'])) { failFast('realizada_at deveria estar preenchido após finalizar'); }
    ok('Primeira finalização: status Realizada, realizada_at preenchido');

    $apos1 = setorMetricas($pdo, $setorId, $anoMesAtual);
    $esperadoValidas1 = $baseline['total_validas'] + 2;
    $esperadoConforme1 = $baseline['total_conforme'] + 1;
    if ($apos1['total_validas'] !== $esperadoValidas1 || $apos1['total_conforme'] !== $esperadoConforme1) {
        failFast(sprintf(
            'setor_metricas após 1ª finalização incorreto: esperado validas=%d conforme=%d, obtido validas=%d conforme=%d',
            $esperadoValidas1, $esperadoConforme1, $apos1['total_validas'], $apos1['total_conforme']
        ));
    }
    ok('setor_metricas incrementado corretamente após a 1ª finalização (baseline + contribuição)');

    // ===================== REABERTURA =====================
    $ok2 = $model->reabrirAuditoria($auditoriaId, 3001, 'Finalizada por engano antes da conclusão das respostas.');
    if (!$ok2) { failFast('Reabertura deveria ter sucesso. erro=' . (string)$model->getLastError()); }
    $auditoria2 = $model->find($auditoriaId);
    if (($auditoria2['status'] ?? '') !== 'Em Auditoria') { failFast('Status deveria voltar para "Em Auditoria" após reabrir'); }
    if (!empty($auditoria2['realizada_at'])) { failFast('realizada_at deveria voltar a NULL após reabrir (senão a 2ª finalização não conseguiria persistir)'); }
    ok('Reabertura: status volta para "Em Auditoria", realizada_at limpo');

    $respostasDestravadas = $model->respostasByAuditoria($auditoriaId);
    foreach ($respostasDestravadas as $r) {
        if (!empty($r['finalized_at'])) { failFast('Respostas deveriam estar destravadas (finalized_at NULL) após reabrir'); }
    }
    ok('Respostas destravadas (finalized_at = NULL) após reabrir');

    $aposReabertura = setorMetricas($pdo, $setorId, $anoMesAtual);
    if ($aposReabertura['total_validas'] !== $baseline['total_validas'] || $aposReabertura['total_conforme'] !== $baseline['total_conforme']) {
        failFast(sprintf(
            'ESTORNO INCORRETO: setor_metricas deveria voltar exatamente ao baseline (validas=%d conforme=%d), obtido validas=%d conforme=%d',
            $baseline['total_validas'], $baseline['total_conforme'], $aposReabertura['total_validas'], $aposReabertura['total_conforme']
        ));
    }
    ok('setor_metricas volta EXATAMENTE ao baseline após a reabertura (estorno correto)');

    // ===================== SEGUNDA FINALIZAÇÃO (com dados alterados) =====================
    // Corrige Q2 para conforme -> validas=2, conforme=2 (contribuição diferente da primeira vez).
    $ok3 = $model->finalizarAuditoria($auditoriaId, [
        ['questao_id' => $q1, 'conformidade' => 'conforme', 'observacoes' => 'Confirmado'],
        ['questao_id' => $q2, 'conformidade' => 'conforme', 'observacoes' => 'Corrigido após reabertura'],
    ], 3001);
    if (!$ok3) { failFast('Segunda finalização deveria ter sucesso. erro=' . (string)$model->getLastError()); }
    $auditoria3 = $model->find($auditoriaId);
    if (($auditoria3['status'] ?? '') !== 'Realizada') { failFast('Status deveria ser Realizada novamente após a 2ª finalização'); }
    if (empty($auditoria3['realizada_at'])) { failFast('realizada_at deveria estar preenchido novamente após a 2ª finalização'); }
    if ((float)$auditoria3['conformidade_pct'] !== 100.0) { failFast('conformidade_pct deveria refletir os novos dados (100%, ambas conformes)'); }
    ok('Segunda finalização: status Realizada de novo, nota/farol recalculados com os dados atuais (100%)');

    $apos3 = setorMetricas($pdo, $setorId, $anoMesAtual);
    $esperadoValidasFinal = $baseline['total_validas'] + 2;
    $esperadoConformeFinal = $baseline['total_conforme'] + 2;
    if ($apos3['total_validas'] !== $esperadoValidasFinal || $apos3['total_conforme'] !== $esperadoConformeFinal) {
        failFast(sprintf(
            'DUPLICAÇÃO DETECTADA: setor_metricas deveria refletir UMA conclusão (validas=%d conforme=%d), obtido validas=%d conforme=%d',
            $esperadoValidasFinal, $esperadoConformeFinal, $apos3['total_validas'], $apos3['total_conforme']
        ));
    }
    // Prova negativa explícita: nunca pode ser baseline + primeira contribuição + segunda contribuição.
    $duplicado = $baseline['total_validas'] + 2 + 2;
    if ($apos3['total_validas'] === $duplicado && $duplicado !== $esperadoValidasFinal) {
        failFast('setor_metricas contabilizou as DUAS finalizações somadas (duplicação real)');
    }
    ok('setor_metricas após 2ª finalização representa UMA conclusão, não duas (sem duplicação)');

    echo "Auditoria reabertura setor_metricas regression tests passed.\n";
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
        $pdo->exec("DELETE FROM departamentos WHERE id IN ($in)");
    }
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', $clienteIds));
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
