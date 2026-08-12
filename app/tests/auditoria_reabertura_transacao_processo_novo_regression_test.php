<?php
require_once __DIR__ . '/../autoload.php';

use App\Database\Database;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

/**
 * Regressão do bug descoberto durante a implementação do item 10 (Fluxo B):
 * SetorMetricaModel::ensure() executava "CREATE TABLE IF NOT EXISTS" sem
 * memoização; esse DDL causa COMMIT IMPLÍCITO no MySQL/MariaDB mesmo com a
 * tabela já existente. Como cada requisição HTTP real é um processo PHP
 * novo, a PRIMEIRA chamada a SetorMetricaModel nesse processo sempre
 * disparava o DDL - quebrando a transação de reabrirAuditoria() no meio,
 * fazendo o método retornar false (reportando falha) enquanto os dados
 * ERAM alterados de qualquer forma (auto-commit implícito). Corrigido
 * pré-aquecendo o schema (SetorMetricaModel::ensureSchema()) ANTES de abrir
 * a transação. Este teste roda a reabertura num SUBPROCESSO isolado
 * (helpers/auditoria_reabertura_processo_novo_probe.php) para reproduzir
 * fielmente um processo "frio" - rodar no mesmo processo deste teste
 * mascararia o bug, pois SetorMetricaModel já teria sido "aquecido" antes.
 */

$pdo = Database::getConnection();
$suffix = 'audfresh_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$auditoriaIds = [];

try {
    $cnpj = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz) VALUES (:n,:c,:ct,1)')
        ->execute(['n' => 'Cliente Processo Novo ' . $suffix, 'c' => $cnpj, 'ct' => 'contato']);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)')
        ->execute(['n' => 'Dep Processo Novo ' . $suffix, 'cid' => $clienteId]);
    $depId = (int)$pdo->lastInsertId();
    $depIds[] = $depId;

    $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)')
        ->execute(['n' => 'Setor Processo Novo ' . $suffix, 'did' => $depId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    // Garante que a tabela setor_metricas já existe (de execuções anteriores do
    // sistema) com uma linha pré-existente para o mês atual - simula o estado
    // real de produção, sem nunca instanciar SetorMetricaModel neste processo pai.
    $anoMes = date('Y-m');
    $pdo->exec("CREATE TABLE IF NOT EXISTS setor_metricas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setor_id INT NOT NULL,
        ano_mes CHAR(7) NOT NULL,
        total_validas INT NOT NULL DEFAULT 0,
        total_conforme INT NOT NULL DEFAULT 0,
        pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_setor_ano_mes (setor_id, ano_mes)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->prepare('INSERT INTO setor_metricas (setor_id, ano_mes, total_validas, total_conforme, pct) VALUES (:s, :am, 5, 3, 60.00)')
        ->execute(['s' => $setorId, 'am' => $anoMes]);

    // Insere a auditoria já "Realizada" diretamente via SQL - equivalente ao
    // estado que uma finalização anterior (em outro processo/request) teria deixado.
    $pdo->prepare("INSERT INTO auditorias (cliente_id, setor_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, realizada_at, conformidade_pct, semaforo, created_by, updated_by)
        VALUES (:cid, :sid, CURDATE(), :nome, 'P', 'O', 'R', 'Realizada', NOW(), 50.00, 'amarelo', 3001, 3001)")
        ->execute(['cid' => $clienteId, 'sid' => $setorId, 'nome' => 'Auditoria Processo Novo ' . $suffix]);
    $auditoriaId = (int)$pdo->lastInsertId();
    $auditoriaIds[] = $auditoriaId;

    $pdo->prepare("INSERT INTO auditoria_questoes (auditoria_id, responsavel_nome, pergunta, referencia_esperada, processos_json, ordem) VALUES (:aid, 'R', 'P1', 'REF', '[]', 1)")
        ->execute(['aid' => $auditoriaId]);
    $qid = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO auditoria_avaliacoes (auditoria_id, questao_id, conformidade, observacoes, auto_saved_at, finalized_at, updated_by) VALUES (:aid, :qid, 'conforme', '', NOW(), NOW(), 3001)")
        ->execute(['aid' => $auditoriaId, 'qid' => $qid]);
    ok('Montou auditoria "Realizada" + setor_metricas pré-existente via SQL direto (sem tocar SetorMetricaModel neste processo)');

    $probe = __DIR__ . '/helpers/auditoria_reabertura_processo_novo_probe.php';
    $cmd = 'php ' . escapeshellarg($probe) . ' ' . escapeshellarg((string)$auditoriaId) . ' ' . escapeshellarg((string)$setorId);
    $out = [];
    exec($cmd . ' 2>&1', $out);
    $raw = implode("\n", $out);
    $result = json_decode($raw, true);
    if (!is_array($result)) {
        failFast('Probe não retornou JSON válido: ' . $raw);
    }

    if ($result['ok'] !== true) {
        failFast('reabrirAuditoria() falhou como primeira interação de SetorMetricaModel num processo novo (lastError=' . (string)($result['lastError'] ?? 'n/a') . ')');
    }
    ok('reabrirAuditoria() funciona corretamente mesmo como a primeira chamada de SetorMetricaModel num processo novo (regressão do bug de commit implícito)');

    if (($result['status_apos'] ?? '') !== 'Em Auditoria') {
        failFast('Status não voltou para "Em Auditoria" no processo novo');
    }
    if (!empty($result['realizada_at_apos'])) {
        failFast('realizada_at não foi limpo no processo novo');
    }
    ok('Status e realizada_at corretos após reabertura em processo novo');

    $sm = $pdo->prepare('SELECT total_validas, total_conforme FROM setor_metricas WHERE setor_id = :s AND ano_mes = :am');
    $sm->execute(['s' => $setorId, 'am' => $anoMes]);
    $row = $sm->fetch();
    if (!$row || (int)$row['total_validas'] !== 4 || (int)$row['total_conforme'] !== 2) {
        failFast('setor_metricas não foi estornado corretamente em processo novo: ' . json_encode($row));
    }
    ok('setor_metricas estornado corretamente (5→4 validas, 3→2 conforme) mesmo em processo novo');

    $histCount = (int)$pdo->query('SELECT COUNT(*) FROM auditoria_historico WHERE auditoria_id = ' . (int)$auditoriaId)->fetchColumn();
    if ($histCount < 1) {
        failFast('Reabertura em processo novo não registrou snapshot em auditoria_historico');
    }
    ok('Histórico registrado corretamente mesmo em processo novo');

    echo "Auditoria reabertura transação processo novo regression tests passed.\n";
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
}
