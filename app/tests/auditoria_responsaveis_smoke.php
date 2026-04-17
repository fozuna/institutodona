<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'resp_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$auditoriaIds = [];

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $insCli->execute([
        'n' => 'Cliente Resp ' . $suffix,
        'c' => str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT),
        'ct' => 'contato',
        'm' => 1,
        'mid' => null,
    ]);
    $clienteId = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteId;

    $insDep = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)');
    $insDep->execute(['n' => 'Departamento Resp ' . $suffix, 'cid' => $clienteId]);
    $departamentoId = (int)$pdo->lastInsertId();
    $depIds[] = $departamentoId;

    $insSet = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)');
    $insSet->execute(['n' => 'Setor Resp ' . $suffix, 'did' => $departamentoId]);
    $setorId = (int)$pdo->lastInsertId();
    $setorIds[] = $setorId;

    $insFunc = $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:sid)');
    $insFunc->execute(['n' => 'Funcao Resp 1 ' . $suffix, 'sid' => $setorId]);
    $funcao1 = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcao1;
    $insFunc->execute(['n' => 'Funcao Resp 2 ' . $suffix, 'sid' => $setorId]);
    $funcao2 = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcao2;

    $insCol = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, lider, cliente_id) VALUES (:n,:e,:f,:l,:cid)');
    $insCol->execute(['n' => 'Colaborador 1 ' . $suffix, 'e' => 'resp1.' . $suffix . '@test.local', 'f' => $funcao1, 'l' => 'sim', 'cid' => $clienteId]);
    $colaborador1 = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaborador1;
    $insCol->execute(['n' => 'Colaborador 2 ' . $suffix, 'e' => 'resp2.' . $suffix . '@test.local', 'f' => $funcao2, 'l' => 'nao', 'cid' => $clienteId]);
    $colaborador2 = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaborador2;

    Auth::login([
        'id' => 9001,
        'nome' => 'Teste Responsaveis',
        'email' => 'responsaveis.' . $suffix . '@test.local',
        'tipo_acesso' => 'consultor',
        'id_cliente' => $clienteId,
    ]);

    $model = new AuditoriaModel();
    $auditoriaId = $model->create([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'nome_auditoria' => 'Auditoria Multi Responsaveis ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'responsavel_ids' => [$colaborador1, $colaborador2],
        'questoes' => [[
            'responsavel_nome' => '',
            'responsavel_ids' => [$colaborador2, $colaborador1, $colaborador1],
            'responsavel_labels' => ['Colaborador 2 ' . $suffix, 'Colaborador 1 ' . $suffix],
            'pergunta' => 'Pergunta para validar persistencia de responsaveis multiplos',
            'referencia_esperada' => 'REF-RESP',
            'processos' => ['Proc 1'],
        ]],
    ], 1);
    if ($auditoriaId <= 0) {
        failFast('Criacao de auditoria com multiplos responsaveis falhou');
    }
    $auditoriaIds[] = $auditoriaId;

    $auditRow = $model->find($auditoriaId);
    if ((int)($auditRow['responsavel_id'] ?? 0) !== $colaborador1) {
        failFast('Campo legado responsavel_id deveria manter o primeiro responsavel da lista');
    }
    if (strpos((string)($auditRow['responsaveis_nomes'] ?? ''), 'Colaborador 1 ' . $suffix) === false || strpos((string)($auditRow['responsaveis_nomes'] ?? ''), 'Colaborador 2 ' . $suffix) === false) {
        failFast('Listagem deveria exibir todos os responsaveis da auditoria');
    }
    ok('Persistencia da auditoria com multiplos responsaveis');

    $full = $model->findWithQuestoes($auditoriaId);
    $questao = $full['questoes'][0] ?? null;
    if (!$questao) {
        failFast('Auditoria deveria retornar questao cadastrada');
    }
    $questaoIds = array_map('intval', $questao['responsavel_ids'] ?? []);
    sort($questaoIds);
    if ($questaoIds !== [$colaborador1, $colaborador2]) {
        failFast('Questao deveria manter responsaveis unicos');
    }
    ok('Edicao/leitura preserva responsaveis das questoes');

    $pdo->prepare('DELETE FROM auditoria_responsaveis WHERE auditoria_id = :id')->execute(['id' => $auditoriaId]);
    $legacyOnly = $model->find($auditoriaId);
    if ((string)($legacyOnly['responsaveis_nomes'] ?? '') !== 'Colaborador 1 ' . $suffix) {
        failFast('Compatibilidade com dados legados deveria usar responsavel_id quando nao houver relacao');
    }
    ok('Compatibilidade com registros legados sem tabela relacional');

    $src = $model->findWithQuestoes($auditoriaId);
    if (!$src) {
        failFast('Auditoria de origem deveria existir para duplicacao');
    }
    $duplicadaId = $model->duplicateFrom($src, [
        'nome_auditoria' => 'Auditoria Copia ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
    ], 1);
    if ($duplicadaId <= 0) {
        failFast('Duplicacao da auditoria deveria funcionar');
    }
    $auditoriaIds[] = $duplicadaId;
    $duplicada = $model->findWithQuestoes($duplicadaId);
    if (count($duplicada['responsaveis'] ?? []) < 1) {
        failFast('Duplicacao deveria preservar responsaveis da auditoria');
    }
    ok('Duplicacao preserva responsaveis e compatibilidade');

    echo "Auditoria responsaveis smoke passed.\n";
} catch (Throwable $e) {
    failFast('Excecao: ' . $e->getMessage());
} finally {
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditorias WHERE id IN ($in)");
    }
    if (!empty($colaboradorIds)) {
        $in = implode(',', array_map('intval', $colaboradorIds));
        $pdo->exec("DELETE FROM colaboradores WHERE id IN ($in)");
    }
    if (!empty($funcaoIds)) {
        $in = implode(',', array_map('intval', $funcaoIds));
        $pdo->exec("DELETE FROM funcoes WHERE id IN ($in)");
    }
    if (!empty($setorIds)) {
        $in = implode(',', array_map('intval', $setorIds));
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
