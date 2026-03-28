<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'aud_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$auditoriaIds = [];

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $insCli->execute(['n' => 'Empresa A ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-A', 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $clienteA = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteA;
    $insCli->execute(['n' => 'Filial A1 ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-A1', 'ct' => 'contato', 'm' => 0, 'mid' => $clienteA]);
    $filialA1 = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialA1;
    $insCli->execute(['n' => 'Empresa B ' . $suffix, 'c' => 'CNPJ-' . $suffix . '-B', 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $clienteB = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteB;

    $insDep = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)');
    $insDep->execute(['n' => 'Dep A ' . $suffix, 'cid' => $clienteA]);
    $depA = (int)$pdo->lastInsertId();
    $depIds[] = $depA;
    $insDep->execute(['n' => 'Dep B ' . $suffix, 'cid' => $clienteB]);
    $depB = (int)$pdo->lastInsertId();
    $depIds[] = $depB;

    $insSet = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:n,:did)');
    $insSet->execute(['n' => 'Setor A ' . $suffix, 'did' => $depA]);
    $setorA = (int)$pdo->lastInsertId();
    $setorIds[] = $setorA;
    $insSet->execute(['n' => 'Setor B ' . $suffix, 'did' => $depB]);
    $setorB = (int)$pdo->lastInsertId();
    $setorIds[] = $setorB;

    $insFunc = $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:n,:sid)');
    $insFunc->execute(['n' => 'Func A ' . $suffix, 'sid' => $setorA]);
    $funcA = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcA;
    $insFunc->execute(['n' => 'Func B ' . $suffix, 'sid' => $setorB]);
    $funcB = (int)$pdo->lastInsertId();
    $funcaoIds[] = $funcB;

    $insCol = $pdo->prepare('INSERT INTO colaboradores (nome, email, funcao_id, lider, cliente_id) VALUES (:n,:e,:f,:l,:cid)');
    $insCol->execute(['n' => 'Colab A ' . $suffix, 'e' => 'colab.a.' . $suffix . '@test.local', 'f' => $funcA, 'l' => 'não', 'cid' => $clienteA]);
    $colaboradorA = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorA;
    $insCol->execute(['n' => 'Colab B ' . $suffix, 'e' => 'colab.b.' . $suffix . '@test.local', 'f' => $funcB, 'l' => 'não', 'cid' => $clienteB]);
    $colaboradorB = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorB;

    Auth::login([
        'id' => 2001,
        'nome' => 'Consultor Escopo A',
        'email' => 'scope.a@test.local',
        'tipo_acesso' => 'consultor',
        'id_cliente' => $clienteA,
    ]);

    $model = new AuditoriaModel();
    $first = $model->create([
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Auditoria Processo A ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [
            [
                'responsavel_nome' => 'Responsável A',
                'pergunta' => 'Pergunta inicial de auditoria para validar processo',
                'referencia_esperada' => 'POP-100',
                'processos' => ['P1'],
            ],
        ],
    ], 2001);
    if ($first <= 0) {
        failFast('Criação inicial de auditoria falhou');
    }
    $auditoriaIds[] = $first;
    ok('Criação de auditoria em escopo permitido');

    $second = $model->create([
        'cliente_id' => $clienteB,
        'setor_id' => $setorB,
        'nome_auditoria' => 'Auditoria Processo B ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [
            [
                'responsavel_nome' => 'Responsável B',
                'pergunta' => 'Pergunta fora do escopo da empresa',
                'referencia_esperada' => 'POP-200',
                'processos' => ['P2'],
            ],
        ],
    ], 2001);
    if ($second <= 0) {
        failFast('Consultor deveria criar auditoria em qualquer empresa');
    }
    $auditoriaIds[] = $second;
    ok('Consultor com escrita ampla em auditorias');

    $t0 = microtime(true);
    for ($i = 0; $i < 1000; $i++) {
        $id = $model->create([
            'cliente_id' => ($i % 2 === 0) ? $clienteA : $filialA1,
            'setor_id' => $setorA,
            'nome_auditoria' => 'Auditoria Lote ' . $i,
            'data_auditoria' => date('Y-m-d'),
            'questoes' => [[
                'responsavel_nome' => 'Resp Lote',
                'pergunta' => 'Pergunta lote ' . $i . ' com tamanho adequado para cenário de performance',
                'referencia_esperada' => 'REF-' . $i,
                'processos' => [],
            ]],
        ], 2001);
        if ($id > 0) {
            $auditoriaIds[] = $id;
        }
    }
    $elapsed = microtime(true) - $t0;
    if ($elapsed > 10) {
        failFast('Performance de inserção degradada para 1000+ registros');
    }
    ok('Performance para 1000+ registros dentro do limite');

    $list = $model->list(['cliente' => $clienteA, 'sort_col' => 'data', 'sort_dir' => 'desc'], 1, 10);
    if ((int)$list['total'] < 1 || count($list['items']) < 1) {
        failFast('Listagem paginada não retornou registros esperados');
    }
    ok('Listagem paginada e filtro por cliente');

    $updateOk = $model->updateAgendada($first, [
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Auditoria Atualizada ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Resp Atualizado',
            'pergunta' => 'Pergunta atualizada para auditoria',
            'referencia_esperada' => 'POP-101',
            'processos' => ['P3'],
        ]],
    ], 2001);
    if (!$updateOk) {
        failFast('Atualização de auditoria agendada deveria funcionar');
    }
    ok('Atualização de auditoria agendada');

    $questoes = $model->questoesByAuditoria($first);
    $auditOk = $model->finalizarAuditoria($first, [[
        'questao_id' => (int)$questoes[0]['id'],
        'conformidade' => 'conforme',
        'observacoes' => 'Observações complementares da execução',
    ]], 2001);
    if (!$auditOk) {
        failFast('Execução de auditoria deveria funcionar');
    }
    ok('Execução de auditoria e transição de status');

    $updateAfterAudit = $model->updateAgendada($first, [
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Tentativa indevida',
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Resp Bloqueio',
            'pergunta' => 'Tentativa indevida',
            'referencia_esperada' => 'POP-102',
            'processos' => [],
        ]],
    ], 2001);
    if ($updateAfterAudit) {
        failFast('Edição após auditoria realizada deveria ser bloqueada');
    }
    ok('Bloqueio de edição pós-auditoria');

    $pdo->exec("INSERT INTO auditoria_relatorios (auditoria_id, relatorio_ref, ativo) VALUES ($first, 'REL-001', 1)");
    $cannotDelete = $model->softDelete($first, 2001);
    if ($cannotDelete) {
        failFast('Exclusão com relatório vinculado deveria ser bloqueada');
    }
    ok('Bloqueio de exclusão com dependência');

    echo "All auditoria flow integration tests passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($auditoriaIds)) {
        $in = implode(',', array_map('intval', $auditoriaIds));
        $pdo->exec("DELETE FROM auditoria_relatorios WHERE auditoria_id IN ($in)");
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
