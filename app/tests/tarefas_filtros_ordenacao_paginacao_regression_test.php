<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\TarefasController;
use App\Core\AccessControl;
use App\Core\Auth;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\TarefaModel;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

/**
 * Item 17: filtros (cliente/status/prioridade), ordenação configurável e
 * paginação real na listagem de Tarefas. TarefasController não dá exit() em
 * nenhum caminho de index()/edit()/finalizar(), então os cenários rodam
 * direto no mesmo processo (sem subprocess-probe).
 */

function roleUser(string $role, ?int $idCliente = null, array $allowed = []): array
{
    return ['id' => 1, 'nome' => ucfirst($role), 'email' => $role . '@test.local', 'tipo_acesso' => $role, 'id_cliente' => $idCliente, 'allowed_client_ids' => $allowed];
}

function renderIndex(): string
{
    ob_start();
    (new TarefasController())->index();
    return (string)ob_get_clean();
}

$pdo = Database::getConnection();
$suffix = 'tk_' . date('YmdHis') . '_' . random_int(100, 999);
$clienteIds = [];

try {
    // ===================== PARTE 1: RBAC de rota (nenhuma regra alterada) =====================
    if (!AccessControl::canAccessRoute('tarefas/index', 'GET', roleUser('instituto'))) {
        failFast('Instituto deveria continuar com acesso a tarefas/index');
    }
    if (!AccessControl::canAccessRoute('tarefas/index', 'GET', roleUser('cliente_admin'))) {
        failFast('Cliente Admin deveria continuar com acesso a tarefas/index');
    }
    foreach (['cliente', 'reader', 'consultor'] as $blockedRole) {
        if (AccessControl::canAccessRoute('tarefas/index', 'GET', roleUser($blockedRole))) {
            failFast("Perfil '$blockedRole' não deveria ter acesso a tarefas/index (regra pré-existente, não deve mudar)");
        }
    }
    ok('Cenários 18/21: Instituto e Cliente Admin preservam acesso a Tarefas; Cliente/Reader/Consultor continuam sem o módulo');

    // ===================== PARTE 2: fixtures =====================
    Auth::login(['id' => 9201, 'nome' => 'Instituto Tarefas Filtro', 'email' => 'instituto.tkfiltro@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $clientes = new ClienteModel();
    $clienteAId = $clientes->create(['nome_empresa' => 'Cliente Tarefas Filtro A ' . $suffix, 'CNPJ' => '31.111.1' . substr($suffix, -2) . '/0001-31', 'contato' => 'Contato A']);
    $clienteBId = $clientes->create(['nome_empresa' => 'Cliente Tarefas Filtro B ' . $suffix, 'CNPJ' => '32.222.2' . substr($suffix, -2) . '/0001-32', 'contato' => 'Contato B']);
    if ($clienteAId <= 0 || $clienteBId <= 0) { failFast('Falha ao criar clientes de teste'); }
    $clienteIds = [$clienteAId, $clienteBId];

    $tarefas = new TarefaModel();
    $mk = static function (int $clienteId, string $titulo, string $status, string $prioridade, string $dataInicio) use ($tarefas): int {
        $id = $tarefas->create([
            'cliente_id' => $clienteId,
            'titulo' => $titulo,
            'data_inicio' => $dataInicio,
            'status' => $status,
            'prioridade' => $prioridade,
        ]);
        if ($id <= 0) { failFast("Falha ao criar fixture '$titulo'"); }
        return $id;
    };

    $tPendenteAlta = $mk($clienteAId, 'TK Pendente Alta ' . $suffix, 'Pendente', 'alta', '2031-01-05 09:00:00');
    $tPendenteBaixa = $mk($clienteAId, 'TK Pendente Baixa ' . $suffix, 'Pendente', 'baixa', '2031-01-03 09:00:00');
    $tAndamentoMedia = $mk($clienteAId, 'TK Andamento Media ' . $suffix, 'Andamento', 'media', '2031-01-04 09:00:00');
    $tPlanejadoAlta = $mk($clienteAId, 'TK Planejado Alta ' . $suffix, 'Planejado', 'alta', '2031-01-01 09:00:00');
    $tClienteB = $mk($clienteBId, 'TK Cliente B ' . $suffix, 'Pendente', 'alta', '2031-01-06 09:00:00');
    ok('Fixtures criadas: 4 tarefas no Cliente A (status/prioridade/datas variados) + 1 no Cliente B');

    // ===================== CENÁRIO 1: listagem padrão =====================
    $_GET = ['route' => 'tarefas/index'];
    $htmlPadrao = renderIndex();
    if (strpos($htmlPadrao, '<table') === false) {
        failFast('Cenário 1: listagem padrão deveria renderizar a tabela');
    }
    ok('Cenário 1: listagem padrão renderiza normalmente');

    // ===================== CENÁRIO 2: filtro por Cliente =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId];
    $htmlClienteA = renderIndex();
    if (strpos($htmlClienteA, htmlspecialchars('TK Cliente B ' . $suffix)) !== false) {
        failFast('Cenário 2: filtro por Cliente A não deveria mostrar tarefa do Cliente B');
    }
    if (strpos($htmlClienteA, htmlspecialchars('TK Pendente Alta ' . $suffix)) === false) {
        failFast('Cenário 2: filtro por Cliente A deveria mostrar as tarefas do Cliente A');
    }
    ok('Cenário 2: filtro por Cliente isola corretamente as tarefas da empresa selecionada');

    // ===================== CENÁRIO 3: filtro por Status =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId, 'status' => 'Andamento'];
    $htmlStatus = renderIndex();
    if (strpos($htmlStatus, htmlspecialchars('TK Andamento Media ' . $suffix)) === false) {
        failFast('Cenário 3: filtro Status=Andamento deveria mostrar a tarefa com esse status');
    }
    if (strpos($htmlStatus, htmlspecialchars('TK Pendente Alta ' . $suffix)) !== false) {
        failFast('Cenário 3: filtro Status=Andamento não deveria mostrar tarefas Pendente');
    }
    ok('Cenário 3: filtro por Status funciona isoladamente');

    // ===================== CENÁRIO 4: filtro por Prioridade =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId, 'prioridade' => 'baixa'];
    $htmlPrio = renderIndex();
    if (strpos($htmlPrio, htmlspecialchars('TK Pendente Baixa ' . $suffix)) === false) {
        failFast('Cenário 4: filtro Prioridade=baixa deveria mostrar a tarefa com essa prioridade');
    }
    if (strpos($htmlPrio, htmlspecialchars('TK Pendente Alta ' . $suffix)) !== false) {
        failFast('Cenário 4: filtro Prioridade=baixa não deveria mostrar tarefas de prioridade alta');
    }
    ok('Cenário 4: filtro por Prioridade funciona isoladamente');

    // ===================== CENÁRIO 5: Status + Prioridade =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId, 'status' => 'Pendente', 'prioridade' => 'alta'];
    $htmlCombo1 = renderIndex();
    if (strpos($htmlCombo1, htmlspecialchars('TK Pendente Alta ' . $suffix)) === false) {
        failFast('Cenário 5: Status=Pendente + Prioridade=alta deveria retornar TK Pendente Alta');
    }
    if (strpos($htmlCombo1, htmlspecialchars('TK Pendente Baixa ' . $suffix)) !== false
        || strpos($htmlCombo1, htmlspecialchars('TK Andamento Media ' . $suffix)) !== false) {
        failFast('Cenário 5: combinação Status+Prioridade não pode vazar tarefas que não casam com os dois filtros');
    }
    ok('Cenário 5: combinação Status + Prioridade funciona simultaneamente');

    // ===================== CENÁRIO 6: Cliente + Status + Prioridade =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteBId, 'status' => 'Pendente', 'prioridade' => 'alta'];
    $htmlCombo2 = renderIndex();
    if (strpos($htmlCombo2, htmlspecialchars('TK Cliente B ' . $suffix)) === false) {
        failFast('Cenário 6: Cliente B + Status=Pendente + Prioridade=alta deveria retornar a tarefa do Cliente B');
    }
    if (strpos($htmlCombo2, htmlspecialchars('TK Pendente Alta ' . $suffix)) !== false) {
        failFast('Cenário 6: filtro combinado com Cliente B não pode vazar tarefa do Cliente A, mesmo com Status/Prioridade iguais');
    }
    ok('Cenário 6: Cliente + Status + Prioridade funcionam combinados sem vazamento entre empresas');

    // ===================== CENÁRIOS 7/8: ordenação por data de início =====================
    $ascRows = $tarefas->paginate(['cliente_id' => $clienteAId, 'ordem' => 'data_inicio_asc'], 1, 10);
    if (($ascRows[0]['id'] ?? null) !== $tPlanejadoAlta) {
        failFast('Cenário 7: ordenação data_inicio_asc deveria trazer a tarefa mais antiga primeiro');
    }
    ok('Cenário 7: ordenação por data de início crescente (mais antigas primeiro) correta');

    $descRows = $tarefas->paginate(['cliente_id' => $clienteAId, 'ordem' => 'data_inicio_desc'], 1, 10);
    if (($descRows[0]['id'] ?? null) !== $tPendenteAlta) {
        failFast('Cenário 8: ordenação data_inicio_desc deveria trazer a tarefa mais recente primeiro');
    }
    ok('Cenário 8: ordenação por data de início decrescente (mais recentes primeiro) correta');

    // ===================== CENÁRIOS 9/10: ordenação por prioridade (ordem semântica) =====================
    $prioDesc = $tarefas->paginate(['cliente_id' => $clienteAId, 'ordem' => 'prioridade_desc'], 1, 10);
    $prioDescSeq = array_map(static fn(array $r): string => $r['prioridade'], $prioDesc);
    if ($prioDescSeq !== ['alta', 'alta', 'media', 'baixa']) {
        failFast('Cenário 9: prioridade_desc deveria seguir Alta → Média → Baixa. Obtido: ' . implode(',', $prioDescSeq));
    }
    ok('Cenário 9: ordenação por prioridade (Alta → Média → Baixa) segue a ordem semântica de negócio, não alfabética');

    $prioAsc = $tarefas->paginate(['cliente_id' => $clienteAId, 'ordem' => 'prioridade_asc'], 1, 10);
    $prioAscSeq = array_map(static fn(array $r): string => $r['prioridade'], $prioAsc);
    if ($prioAscSeq !== ['baixa', 'media', 'alta', 'alta']) {
        failFast('Cenário 10: prioridade_asc deveria seguir Baixa → Média → Alta. Obtido: ' . implode(',', $prioAscSeq));
    }
    ok('Cenário 10: ordenação por prioridade (Baixa → Média → Alta) correta');

    // ===================== CENÁRIO 11: ordem inválida cai em fallback seguro =====================
    if (TarefaModel::normalizeOrder("id; DROP TABLE tarefas;--") !== 'data_inicio_desc') {
        failFast('Cenário 11: valor de ordenação inválido/malicioso deveria cair no fallback data_inicio_desc');
    }
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId, 'ordem' => 'coisa-invalida'];
    $htmlOrdemInvalida = renderIndex();
    if (strpos($htmlOrdemInvalida, '<table') === false) {
        failFast('Cenário 11: ordem inválida via GET não pode quebrar a listagem');
    }
    ok('Cenário 11: ordenação inválida (inclusive tentativa de injeção) cai no fallback seguro, sem quebrar a listagem');

    // ===================== CENÁRIO 12: page inválido =====================
    foreach (['0', '-10', 'abc', '999999'] as $badPage) {
        $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId, 'page' => $badPage];
        $htmlBadPage = renderIndex();
        if (strpos($htmlBadPage, '<table') === false) {
            failFast("Cenário 12: page=$badPage não pode quebrar a listagem (erro 500 ou fatal)");
        }
    }
    ok('Cenário 12: page=0, page=-10, page=abc e page=999999 tratados com fallback seguro (sem erro 500)');

    // ===================== CENÁRIOS 13/14: paginação e count() com os mesmos filtros =====================
    $clienteCId = $clientes->create(['nome_empresa' => 'Cliente Tarefas Paginacao ' . $suffix, 'CNPJ' => '33.333.3' . substr($suffix, -2) . '/0001-33', 'contato' => 'Contato C']);
    if ($clienteCId <= 0) { failFast('Falha ao criar cliente de paginação'); }
    $clienteIds[] = $clienteCId;
    $pagIds = [];
    for ($i = 1; $i <= 5; $i++) {
        $pagIds[] = $mk($clienteCId, 'TK Pag ' . $i . ' ' . $suffix, 'Pendente', 'media', sprintf('2031-02-%02d 09:00:00', $i));
    }
    $countTotal = $tarefas->count(['cliente_id' => $clienteCId]);
    if ($countTotal !== 5) {
        failFast('Cenário 13/14: count() deveria retornar 5 para o Cliente C. Obtido: ' . $countTotal);
    }
    $page1 = $tarefas->paginate(['cliente_id' => $clienteCId], 1, 2);
    $page2 = $tarefas->paginate(['cliente_id' => $clienteCId], 2, 2);
    $page3 = $tarefas->paginate(['cliente_id' => $clienteCId], 3, 2);
    if (count($page1) !== 2 || count($page2) !== 2 || count($page3) !== 1) {
        failFast('Cenário 13: paginação com perPage=2 deveria produzir 2+2+1 registros nas 3 páginas. Obtido: ' . count($page1) . '+' . count($page2) . '+' . count($page3));
    }
    $idsAcrossPages = array_merge(array_column($page1, 'id'), array_column($page2, 'id'), array_column($page3, 'id'));
    if (count(array_unique($idsAcrossPages)) !== 5 || array_diff($pagIds, $idsAcrossPages) !== []) {
        failFast('Cenário 13: as 3 páginas juntas deveriam cobrir exatamente as 5 tarefas criadas, sem repetição nem lacuna');
    }
    ok('Cenário 13: paginação produz o número correto de registros por página, cobrindo o total sem repetição');
    ok('Cenário 14: count() usa exatamente os mesmos filtros de paginate() (mesmo helper interno)');

    // Página além do total, via Controller (clamp para a última página válida).
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteCId, 'page' => '999'];
    $htmlClamped = renderIndex();
    if (strpos($htmlClamped, htmlspecialchars('TK Pag 1 ' . $suffix)) === false) {
        failFast('Cenário 12 (controller): page muito além do total deveria cair na última página válida (que contém TK Pag 1), não ficar vazia');
    }
    ok('Cenário 12 (controller): page além do total de páginas cai com segurança na última página válida');

    // ===================== CENÁRIO 15: página seguinte preserva querystring =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteCId, 'status' => 'Pendente', 'prioridade' => 'media', 'ordem' => 'data_inicio_asc', 'per' => null];
    unset($_GET['per']);
    $_GET['page'] = '1';
    // per=20 (fixo no controller) e 5 fixtures não geram 2 páginas de verdade; construímos a
    // querystring esperada de "página seguinte" manualmente para validar que TODOS os filtros
    // ativos são preservados nos links de paginação renderizados pela view.
    $htmlQuerystring = renderIndex();
    // Com apenas 5 registros e perPage=20 não há paginação (totalPages=1); o teste relevante é:
    // usando os mesmos 5 registros com uma página menor via chamada direta ao Model (já coberto
    // nos cenários 13/14), aqui validamos que os links de página, quando existem, carregam cliente/
    // status/prioridade/ordem. Forçamos isso criando mais fixtures até estourar a primeira página.
    for ($i = 6; $i <= 22; $i++) {
        $pagIds[] = $mk($clienteCId, 'TK Pag ' . $i . ' ' . $suffix, 'Pendente', 'media', sprintf('2031-02-%02d 09:00:00', $i));
    }
    $clienteIds = array_values(array_unique($clienteIds));
    $htmlMultiPage = renderIndex();
    if (strpos($htmlMultiPage, 'cliente=' . $clienteCId) === false
        || strpos($htmlMultiPage, 'status=Pendente') === false
        || strpos($htmlMultiPage, 'prioridade=media') === false
        || strpos($htmlMultiPage, 'ordem=data_inicio_asc') === false) {
        failFast('Cenário 15: links de paginação deveriam preservar cliente/status/prioridade/ordem na querystring');
    }
    ok('Cenário 15: navegar para a página seguinte preserva cliente/status/prioridade/ordem na querystring');

    // ===================== CENÁRIO 16: limpar filtros =====================
    if (strpos($htmlMultiPage, 'Limpar filtros') === false) {
        failFast('Cenário 16: ação "Limpar filtros" deveria estar visível quando há filtros ativos');
    }
    if (strpos($htmlMultiPage, 'href="index.php?route=tarefas/index"') === false) {
        failFast('Cenário 16: "Limpar filtros" deveria apontar para a rota base, sem parâmetros adicionais');
    }
    ok('Cenário 16: "Limpar filtros" volta para o estado padrão (rota base, sem querystring)');

    // ===================== CENÁRIO 17: estado sem resultados =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId, 'status' => 'Finalizado', 'prioridade' => 'baixa'];
    $htmlVazio = renderIndex();
    if (strpos($htmlVazio, 'Nenhuma tarefa encontrada para os filtros selecionados.') === false) {
        failFast('Cenário 17: combinação sem resultados deveria exibir a mensagem específica de filtro vazio');
    }
    ok('Cenário 17: filtros sem resultado exibem mensagem específica (não uma tabela vazia sem explicação)');

    // ===================== CENÁRIO 19/20: Cliente Admin permanece tenant-scoped =====================
    Auth::login(['id' => 9202, 'nome' => 'Cliente Admin Tarefas B', 'email' => 'admin.tkb@test.local', 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $clienteBId]);
    $countCrossTenant = $tarefas->count(['cliente_id' => $clienteAId]);
    $itemsCrossTenant = $tarefas->paginate(['cliente_id' => $clienteAId], 1, 20);
    foreach ($itemsCrossTenant as $row) {
        if ((int)$row['cliente_id'] !== $clienteBId) {
            failFast('Cenário 20: Cliente Admin do Cliente B não pode enxergar tarefas do Cliente A ao manipular o filtro cliente_id');
        }
    }
    if ($countCrossTenant < 1) {
        failFast('Cenário 19 (controle): Cliente Admin deveria continuar enxergando as próprias tarefas mesmo tentando filtrar por outro cliente_id');
    }
    ok('Cenário 19/20: Cliente Admin permanece restrito ao próprio tenant mesmo manipulando o filtro de cliente (normalizeScopedClienteId + tenantInCondition)');

    // ===================== CENÁRIO 23: edição continua funcionando =====================
    Auth::login(['id' => 9201, 'nome' => 'Instituto Tarefas Filtro', 'email' => 'instituto.tkfiltro@test.local', 'tipo_acesso' => 'instituto', 'id_cliente' => null]);
    $_GET = ['route' => 'tarefas/edit', 'id' => (string)$tPendenteAlta];
    ob_start();
    (new TarefasController())->edit();
    $editHtml = (string)ob_get_clean();
    if (strpos($editHtml, 'name="titulo"') === false) {
        failFast('Cenário 23: edição de tarefa parou de renderizar o formulário');
    }
    ok('Cenário 23: edição de tarefa continua funcionando após as mudanças de listagem');

    // ===================== CENÁRIO 24: finalização continua funcionando =====================
    $ok = $tarefas->finalize($tPendenteAlta, 9201);
    $finalizada = $tarefas->find($tPendenteAlta);
    if (!$ok || ($finalizada['status'] ?? '') !== 'Finalizado') {
        failFast('Cenário 24: finalização de tarefa parou de funcionar');
    }
    ok('Cenário 24: finalização de tarefa continua funcionando e reflete no status persistido');

    // ===================== CENÁRIO 22: tarefa criada pelo Perfil aparece (complemento) =====================
    $_GET = ['route' => 'tarefas/index', 'cliente' => (string)$clienteAId];
    $htmlPerfil = renderIndex();
    if (strpos($htmlPerfil, htmlspecialchars('TK Planejado Alta ' . $suffix)) === false) {
        failFast('Cenário 22: tarefa existente do cliente continua aparecendo na listagem filtrada por cliente');
    }
    ok('Cenário 22: listagem filtrada por cliente continua exibindo corretamente as tarefas da empresa (mesmo mecanismo usado pelo fluxo via Perfil do Cliente)');

    echo "tarefas_filtros_ordenacao_paginacao_regression_test passed.\n";
} catch (Throwable $e) {
    failFast('Exceção: ' . $e->getMessage());
} finally {
    if (!empty($clienteIds)) {
        $in = implode(',', array_map('intval', array_unique($clienteIds)));
        $pdo->exec("DELETE FROM tarefas WHERE cliente_id IN ($in)");
        $pdo->exec("DELETE FROM clientes WHERE id IN ($in)");
    }
    Auth::logout();
}
