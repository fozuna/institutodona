<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Auth;
use App\Database\Database;
use App\Models\AuditoriaModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$pdo = Database::getConnection();
$suffix = 'aud_' . date('YmdHis') . '_' . random_int(100, 999);
$cnpjA = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
$cnpjA1 = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
$cnpjB = str_pad((string)random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
$clienteIds = [];
$depIds = [];
$setorIds = [];
$funcaoIds = [];
$colaboradorIds = [];
$auditoriaIds = [];

try {
    $insCli = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,:m,:mid)');
    $insCli->execute(['n' => 'Empresa A ' . $suffix, 'c' => $cnpjA, 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $clienteA = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteA;
    $insCli->execute(['n' => 'Filial A1 ' . $suffix, 'c' => $cnpjA1, 'ct' => 'contato', 'm' => 0, 'mid' => $clienteA]);
    $filialA1 = (int)$pdo->lastInsertId();
    $clienteIds[] = $filialA1;
    $insCli->execute(['n' => 'Empresa B ' . $suffix, 'c' => $cnpjB, 'ct' => 'contato', 'm' => 1, 'mid' => null]);
    $clienteB = (int)$pdo->lastInsertId();
    $clienteIds[] = $clienteB;

    $insDep = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:n,:cid)');
    $insDep->execute(['n' => 'Dep A ' . $suffix, 'cid' => $clienteA]);
    $depA = (int)$pdo->lastInsertId();
    $depIds[] = $depA;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $depA, 'c' => $clienteA]);
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $depA, 'c' => $filialA1]);
    $insDep->execute(['n' => 'Dep B ' . $suffix, 'cid' => $clienteB]);
    $depB = (int)$pdo->lastInsertId();
    $depIds[] = $depB;
    $pdo->prepare('INSERT INTO departamento_clientes (departamento_id, cliente_id) VALUES (:d,:c)')
        ->execute(['d' => $depB, 'c' => $clienteB]);

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
    $insCol->execute(['n' => 'Colab A ' . $suffix, 'e' => 'colab.a.' . $suffix . '@test.local', 'f' => $funcA, 'l' => 'sim', 'cid' => $clienteA]);
    $colaboradorA = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorA;
    $insCol->execute(['n' => 'Colab B ' . $suffix, 'e' => 'colab.b.' . $suffix . '@test.local', 'f' => $funcB, 'l' => 'sim', 'cid' => $clienteB]);
    $colaboradorB = (int)$pdo->lastInsertId();
    $colaboradorIds[] = $colaboradorB;

    /**
     * Sprint B, Achado B: a regra vigente para o Consultor é acesso somente
     * aos clientes vinculados via usuario_empresas (resolvido por
     * TenantScopeResolver / Auth::allowedClientIds() e aplicado pelo
     * AuditoriaModel). Não existe mais "Consultor com escrita ampla" -
     * este bloco cobre o isolamento cross-tenant chamando o Model
     * diretamente (sem passar pelo Controller/AccessControl), validando
     * que a defesa em profundidade não depende exclusivamente da camada
     * HTTP. A matriz completa de RBAC/módulos é coberta em
     * auditorias_consultor_rbac_scope_regression_test.php; este teste foca
     * no comportamento do Model em si dentro de um fluxo operacional mais
     * longo (lote, concorrência, histórico, exclusão).
     */
    Auth::login([
        'id' => 2001,
        'nome' => 'Consultor Escopo A',
        'email' => 'scope.a@test.local',
        'tipo_acesso' => 'consultor',
        'id_cliente' => $clienteA,
    ]);

    $model = new AuditoriaModel();

    // Cenário 1 (próprio tenant): Consultor cria auditoria no Cliente A - SUCESSO.
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
    ok('Cenário 1: Consultor cria auditoria no cliente vinculado (Cliente A)');

    // Cenário 2 (cross-tenant): Consultor sem vínculo ao Cliente B tenta criar
    // auditoria nele diretamente via AuditoriaModel::create() - BLOQUEADO.
    // Nenhum registro pode ser persistido para o Cliente B.
    $countClienteBAntes = (int)$pdo->query('SELECT COUNT(*) FROM auditorias WHERE cliente_id = ' . (int)$clienteB)->fetchColumn();
    $crossTenantCreate = $model->create([
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
    if ($crossTenantCreate > 0) {
        $auditoriaIds[] = $crossTenantCreate;
        failFast('Falha de segurança: Consultor sem vínculo ao Cliente B conseguiu criar auditoria via AuditoriaModel::create() chamado diretamente (isolamento não pode depender do Controller/AccessControl)');
    }
    $countClienteBDepois = (int)$pdo->query('SELECT COUNT(*) FROM auditorias WHERE cliente_id = ' . (int)$clienteB)->fetchColumn();
    if ($countClienteBDepois !== $countClienteBAntes) {
        failFast('Nenhum registro deveria ter sido persistido para o Cliente B fora do escopo do Consultor');
    }
    ok('Cenário 2: Consultor sem vínculo é bloqueado ao criar auditoria no Cliente B (defesa em profundidade no Model)');

    // Cenário 3 (leitura cross-tenant): auditoria pré-existente do Cliente B
    // (inserida diretamente, simulando um registro real de outra empresa) não
    // pode ser lida pelo Consultor via find() - contrato atual do Model é null.
    $pdo->prepare("INSERT INTO auditorias (cliente_id, setor_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, created_by, updated_by)
        VALUES (:cid, :sid, CURDATE(), :nome, 'P', 'O', 'R', 'Agendada', 9999, 9999)")
        ->execute(['cid' => $clienteB, 'sid' => $setorB, 'nome' => 'Auditoria Fora de Escopo ' . $suffix]);
    $auditoriaForaEscopoId = (int)$pdo->lastInsertId();
    $auditoriaIds[] = $auditoriaForaEscopoId;
    if ($model->find($auditoriaForaEscopoId) !== null) {
        failFast('Consultor NÃO deveria conseguir visualizar auditoria do Cliente B via find()');
    }
    ok('Cenário 3: leitura cross-tenant bloqueada (find() retorna null para auditoria do Cliente B)');

    // Cenário 4 (listagem): Consultor lista auditorias e enxerga somente o
    // que pertence ao(s) cliente(s) vinculado(s), sem vazar o Cliente B.
    $listaEscopo = $model->list(['sort_col' => 'data', 'sort_dir' => 'desc'], 1, 50);
    $idsListados = array_map(static fn($row) => (int)$row['id'], $listaEscopo['items']);
    if (in_array($auditoriaForaEscopoId, $idsListados, true)) {
        failFast('Listagem do Consultor vazou uma auditoria do Cliente B');
    }
    if (!in_array($first, $idsListados, true)) {
        failFast('Listagem do Consultor deveria conter a auditoria do próprio Cliente A');
    }
    ok('Cenário 4: listagem permanece escopada ao(s) cliente(s) vinculado(s) do Consultor');

    // Cenário 5 (Instituto): o único perfil com bypass global continua
    // funcionando normalmente, inclusive no Cliente B (sem regressão).
    Auth::login(['id' => 9999, 'nome' => 'Instituto Flow', 'email' => 'instituto.flow@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $institutoCreate = $model->create([
        'cliente_id' => $clienteB,
        'setor_id' => $setorB,
        'nome_auditoria' => 'Auditoria Instituto Cliente B ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [['responsavel_nome' => 'Resp Instituto', 'pergunta' => 'Pergunta de conformidade do processo auditado', 'referencia_esperada' => 'POP-INST', 'processos' => []]],
    ], 9999);
    if ($institutoCreate <= 0) {
        failFast('Instituto deveria continuar conseguindo criar auditoria em qualquer cliente (bypass global mantido)');
    }
    $auditoriaIds[] = $institutoCreate;
    if ($model->find($institutoCreate) === null) {
        failFast('Instituto deveria conseguir visualizar auditoria de qualquer cliente');
    }
    ok('Cenário 5: Instituto mantém bypass global de escopo, sem regressão');

    // Volta ao contexto do Consultor para o restante do fluxo operacional.
    Auth::login([
        'id' => 2001,
        'nome' => 'Consultor Escopo A',
        'email' => 'scope.a@test.local',
        'tipo_acesso' => 'consultor',
        'id_cliente' => $clienteA,
    ]);

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
    ], 2001, null, 1);
    if (!$updateOk) {
        failFast('Atualização de auditoria agendada deveria funcionar');
    }
    ok('Atualização de auditoria agendada');

    $concurrencyAudit = $model->create([
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Auditoria Concorrência ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Resp Concorrência',
            'pergunta' => 'Pergunta para validar conflito de versão',
            'referencia_esperada' => 'POP-LOCK',
            'processos' => [],
        ]],
    ], 2001);
    if ($concurrencyAudit <= 0) {
        failFast('Criação de auditoria para teste de concorrência falhou');
    }
    $auditoriaIds[] = $concurrencyAudit;

    $freshUpdate = $model->updateAgendada($concurrencyAudit, [
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Auditoria Concorrência Atualizada ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Resp Concorrência 2',
            'pergunta' => 'Atualização válida para subir versão',
            'referencia_esperada' => 'POP-LOCK-2',
            'processos' => [],
        ]],
    ], 2001, null, 1);
    if (!$freshUpdate) {
        failFast('Atualização inicial de auditoria de concorrência deveria funcionar');
    }

    $staleUpdate = $model->updateAgendada($concurrencyAudit, [
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Atualização com versão antiga ' . $suffix,
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Resp Stale',
            'pergunta' => 'Tentativa com versão antiga',
            'referencia_esperada' => 'POP-STALE',
            'processos' => [],
        ]],
    ], 2001, null, 1);
    if ($staleUpdate) {
        failFast('Atualização com versão antiga deveria falhar por concorrência');
    }
    if (($model->getLastError() ?? '') !== 'concurrency_conflict') {
        failFast('Falha de concorrência deveria retornar código concurrency_conflict');
    }
    ok('Detecção de conflito de versão otimista');

    $questoes = $model->questoesByAuditoria($first);
    $auditOk = $model->finalizarAuditoria($first, [[
        'questao_id' => (int)$questoes[0]['id'],
        'conformidade' => 'conforme',
        'observacoes' => 'Observações complementares da execução',
    ]], 2001);
    if (!$auditOk) {
        $snapshot = $model->find($first);
        failFast('Execução de auditoria deveria funcionar. erro=' . (string)($model->getLastError() ?? 'n/a') . ' status=' . (string)($snapshot['status'] ?? 'n/a'));
    }
    ok('Execução de auditoria e transição de status');

    $updateAfterAudit = $model->updateAgendada($first, [
        'cliente_id' => $clienteA,
        'setor_id' => $setorA,
        'nome_auditoria' => 'Auditoria Reaberta Controlada',
        'data_auditoria' => date('Y-m-d'),
        'questoes' => [[
            'responsavel_nome' => 'Resp Pos Finalizacao',
            'pergunta' => 'Auditoria editada apos finalizacao',
            'referencia_esperada' => 'POP-102',
            'processos' => [],
        ]],
    ], 2001, (string)($model->find($first)['updated_at'] ?? ''), (int)($model->find($first)['lock_version'] ?? 0));
    if (!$updateAfterAudit) {
        failFast('Edição após auditoria realizada deveria ser permitida com controle de concorrência');
    }
    $histCount = (int)$pdo->query('SELECT COUNT(*) FROM auditoria_historico WHERE auditoria_id = ' . (int)$first)->fetchColumn();
    if ($histCount < 2) {
        failFast('Histórico de auditoria deveria registrar versões anteriores');
    }
    ok('Edição pós-auditoria e histórico de alterações');

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
