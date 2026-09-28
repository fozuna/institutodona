<?php
// Pilar de Pessoas - Sprint 05: listagens operacionais (GAPs, Ações,
// Necessidades) + coerência com a Visão Geral. Cobre tenant, filtros,
// status, com/sem ação, atrasadas (regra centralizada), paginação,
// ordenação por atenção, encaminhamentos, cross-tenant, whitelist do
// Controller e links acionáveis da Visão Geral.
require __DIR__ . '/../autoload.php';
require __DIR__ . '/helpers/pessoas_sprint05_fixtures.php';

use App\Controllers\PessoasOperacionalController;
use App\Controllers\PessoasVisaoController;
use App\Models\PessoaDashboardModel;
use App\Models\PessoaOperacionalModel;

ob_start();
function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }
function asInstituto(): void { $_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []]; }
function asClienteAdmin(int $eid): void { $_SESSION['user'] = ['id' => 987654, 'nome' => 'CA', 'tipo_acesso' => 'cliente_admin', 'id_cliente' => $eid, 'allowed_client_ids' => [$eid]]; }
function eq($got, $exp, string $what): void { if ($got !== $exp) { failFast("$what: esperado " . json_encode($exp) . ' obtido ' . json_encode($got)); } }
function titulos(array $items): array { return array_map(static fn(array $r): string => (string)$r['titulo'], $items); }

asInstituto();
$F = s05_fixtures();
$eA = $F['eA']; $eB = $F['B']['eid'];
$m = new PessoaOperacionalModel();
$dash = new PessoaDashboardModel();
ok('Fixtures da Sprint 05 criadas (Empresa A completa + Empresa B isolada)');

// ===================== GAPs =====================
$all = $m->listarGaps([$eA], [], 1, 50);
eq($all['total'], 6, 'GAPs de A (B excluída)');
eq(titulos($all['items']), ['gA4 acao concluida', 'gA2 so cancelada', 'gA1 sem acao', 'gA6 antigo', 'gA3 com acao', 'gA5 resolvido'],
    'Ordenação: abertos (mais recentes primeiro) -> em tratamento -> resolvidos');
ok('GAPs: tenant de empresa e ordenação por atenção determinística');

eq($m->listarGaps([$eA], ['status' => 'aberto'], 1, 50)['total'], 4, 'status=aberto');
eq($m->listarGaps([$eA], ['status' => 'em_tratamento'], 1, 50)['total'], 1, 'status=em_tratamento');
eq($m->listarGaps([$eA], ['status' => 'resolvido'], 1, 50)['total'], 1, 'status=resolvido');
eq($m->listarGaps([$eA], ['status' => 'nao_resolvido'], 1, 50)['total'], 5, 'status=nao_resolvido');
ok('GAPs: filtro de status (incluindo "não resolvidos")');

$semAcao = $m->listarGaps([$eA], ['status' => 'aberto', 'tratamento' => 'sem_acao'], 1, 50);
eq(titulos($semAcao['items']), ['gA2 so cancelada', 'gA1 sem acao', 'gA6 antigo'], 'aberto + sem ação (ação cancelada NÃO trata o GAP)');
eq(titulos($m->listarGaps([$eA], ['tratamento' => 'com_acao'], 1, 50)['items']), ['gA4 acao concluida', 'gA3 com acao'], 'com ação');
$gA4 = $m->listarGaps([$eA], ['tratamento' => 'com_acao'], 1, 50)['items'][0];
eq([(int)$gA4['acoes_total'], (int)$gA4['acoes_ativas'], (int)$gA4['primeira_acao_id']], [1, 0, $F['ac']['ac3']], 'colunas de ação relacionada do GAP');
ok('GAPs: com ação / sem ação e ação relacionada');

eq($m->listarGaps([$eA], ['departamento_id' => $F['A']['deps'][2]], 1, 50)['total'], 1, 'filtro departamento');
eq($m->listarGaps([$eA], ['setor_id' => $F['A']['sets'][1]], 1, 50)['total'], 5, 'filtro setor');
eq($m->listarGaps([$eA], ['funcao_id' => $F['A']['funs'][2]], 1, 50)['total'], 1, 'filtro função');
eq($m->listarGaps([$eA], ['colaborador_id' => $F['a2']], 1, 50)['total'], 2, 'filtro colaborador');
// Busca pelo nome completo do colaborador: o filtro faz trim() e as fixtures
// compartilham um sufixo hex aleatório (buscar só "A3" casava todos quando o
// sufixo continha "a3").
$nomeA3 = (string)\App\Database\Database::getConnection()->query('SELECT nome FROM colaboradores WHERE id = ' . (int)$F['a3'])->fetchColumn();
eq($m->listarGaps([$eA], ['q' => $nomeA3], 1, 50)['total'], 1, 'busca por nome');
eq($m->listarGaps([$eA], ['inicio' => '2026-01-01', 'fim' => '2026-12-31'], 1, 50)['total'], 5, 'período de registro exclui GAP de 2020');
ok('GAPs: filtros organizacionais, colaborador, busca e período');

$p1 = $m->listarGaps([$eA], [], 1, 2); $p3 = $m->listarGaps([$eA], [], 3, 2); $p99 = $m->listarGaps([$eA], [], 99, 2);
eq([count($p1['items']), $p1['total'], $p3['page'], count($p3['items'])], [2, 6, 3, 2], 'paginação 2 por página');
eq([$p99['page'], titulos($p99['items'])], [3, titulos($p3['items'])], 'página além do fim é limitada à última');
$paginas = array_merge(titulos($p1['items']), titulos($m->listarGaps([$eA], [], 2, 2)['items']), titulos($p3['items']));
eq($paginas, titulos($all['items']), 'páginas concatenadas = listagem completa (sem duplicar/perder)');
ok('GAPs: paginação consistente');

// ===================== Ações =====================
$acs = $m->listarAcoes([$eA], [], 1, 50);
eq($acs['total'], 6, 'Ações de A');
eq(array_slice(titulos($acs['items']), 0, 2), ['ac1 vencida plano', 'ac4 vencida treinamento'], 'atrasadas primeiro');
eq(end($acs['items'])['titulo'], 'ac2 cancelada', 'canceladas por último');
$atr = $m->listarAcoes([$eA], ['atrasadas' => 1], 1, 50);
eq(titulos($atr['items']), ['ac1 vencida plano', 'ac4 vencida treinamento'], 'atrasadas = prazo passado e pendente/em andamento (cancelada/concluída não)');
foreach ($atr['items'] as $r) { eq((int)$r['vencida'], 1, 'coluna vencida'); }
ok('Ações: atrasadas pela regra centralizada e priorizadas');

eq($m->listarAcoes([$eA], ['status' => 'ativas'], 1, 50)['total'], 4, 'status=ativas');
eq($m->listarAcoes([$eA], ['status' => 'concluida'], 1, 50)['total'], 1, 'status=concluida');
eq(titulos($m->listarAcoes([$eA], ['responsavel_id' => $F['u']], 1, 50)['items']), ['ac4 vencida treinamento'], 'responsável');
$comPlano = $m->listarAcoes([$eA], ['com_plano' => 1], 1, 50)['items'];
eq([count($comPlano), (int)$comPlano[0]['plano_task_id'], $comPlano[0]['plano_status']], [1, $F['task'], 'Em Andamento'], 'com Plano de Ação');
$comTrein = $m->listarAcoes([$eA], ['com_treinamento' => 1], 1, 50)['items'];
eq([count($comTrein), (int)$comTrein[0]['treinamentos_total'], (int)$comTrein[0]['necessidades_pendentes']], [1, 1, 1], 'com Treinamento + necessidade pendente');
eq($m->listarAcoes([$eA], ['departamento_id' => $F['A']['deps'][2]], 1, 50)['total'], 2, 'filtro organizacional');
eq(count($m->listarAcoes([$eA], [], 2, 4)['items']), 2, 'paginação de ações');
ok('Ações: status, responsável, Plano, Treinamento, filtros e paginação');

// ===================== Necessidades =====================
$nes = $m->listarNecessidades([$eA], [], 1, 50);
eq(titulos($nes['items']), ['n1 pendente alta', 'n4 pendente baixa', 'n2 atendida', 'n3 cancelada'], 'ordem: pendentes (prioridade) -> atendidas -> canceladas');
eq($m->listarNecessidades([$eA], ['status' => 'pendente'], 1, 50)['total'], 2, 'pendentes');
$at = $m->listarNecessidades([$eA], ['status' => 'atendida'], 1, 50)['items'];
eq([count($at), (int)$at[0]['treinamento_id'], $at[0]['acao_titulo']], [1, $F['trein'], 'ac1 vencida plano'], 'atendida com treinamento vinculado e ação de origem');
eq($m->listarNecessidades([$eA], ['status' => 'cancelada'], 1, 50)['total'], 1, 'canceladas (histórico preservado)');
eq($m->listarNecessidades([$eA], ['colaborador_id' => $F['a1']], 1, 50)['total'], 2, 'filtro colaborador');
eq($m->listarNecessidades([$eA], ['funcao_id' => $F['A']['funs'][2]], 1, 50)['total'], 1, 'filtro função');
eq([count($m->listarNecessidades([$eA], [], 2, 3)['items'])], [1], 'paginação necessidades');
ok('Necessidades: status, treinamento vinculado, filtros e paginação');

// ===================== Tenant / cross-tenant =====================
asClienteAdmin($eA);
eq($m->listarGaps([$eA, $eB], [], 1, 50)['total'], 6, 'Cliente Admin A pedindo A+B vê só A (GAPs)');
eq($m->listarAcoes([$eB], [], 1, 50)['total'], 0, 'Cliente Admin A não enxerga ações de B');
eq($m->listarNecessidades([$eB], ['status' => 'pendente'], 1, 50)['total'], 0, 'Cliente Admin A não enxerga necessidades de B');
eq($m->listarGaps([$eA], ['colaborador_id' => $F['b1']], 1, 50)['total'], 0, 'colaborador de B como filtro não traz nada');
eq($m->listarGaps([], [], 1, 50)['total'], 0, 'sem empresas => nada');
ok('Tenant aplicado no SQL em todas as listagens');

// Controller: whitelist + colaborador cross-tenant descartado + escaping.
$render = static function (string $acao, array $get): string {
    $_GET = array_merge(['route' => 'pessoas/' . $acao], $get);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    ob_start();
    (new PessoasOperacionalController())->{$acao}();
    return (string)ob_get_clean();
};
// (empresa_id de outro tenant é barrado antes, com 404 oculto, por authorizeRoute() - coberto no teste HTTP.)
$html = $render('gaps', ['status' => "aberto' OR 1=1 --", 'tratamento' => '<script>alert(905)</script>', 'colaborador_id' => (string)$F['b1']]);
if (!str_contains($html, 'gA4 acao concluida') || !str_contains($html, 'gA5 resolvido')) { failFast('Parâmetros fora da whitelist deveriam ser descartados (listagem completa de A)'); }
if (str_contains($html, 'gB1') || str_contains($html, 'B1 ' . $F['sfx'])) { failFast('Nada de B pode aparecer via colaborador_id manipulado'); }
if (str_contains($html, 'OR 1=1') || str_contains($html, 'alert(905)')) { failFast('Valores fora da whitelist não podem ser refletidos na página'); }
$htmlXss = $render('gaps', ['q' => '"><img src=x onerror=alert(906)>']);
if (str_contains($htmlXss, '<img src=x onerror=alert(906)>')) { failFast('Busca livre precisa ser escapada (XSS)'); }
if (!str_contains($htmlXss, '&quot;&gt;&lt;img src=x onerror=alert(906)&gt;')) { failFast('Busca livre deveria ser devolvida escapada no campo de filtro'); }
$htmlAtr = $render('acoes', ['atrasadas' => '1', 'responsavel_id' => '999999999']);
if (!str_contains($htmlAtr, 'ac1 vencida plano') || str_contains($htmlAtr, 'ac5 futura')) { failFast('Controller de ações: atrasadas=1 deveria filtrar'); }
$htmlNec = $render('necessidades', ['status' => 'pendente', 'page' => '-5']);
if (!str_contains($htmlNec, 'n1 pendente alta') || str_contains($htmlNec, 'n2 atendida')) { failFast('Controller de necessidades: status=pendente'); }
$htmlVazio = $render('gaps', ['status' => 'aberto', 'q' => 'ninguem-com-este-nome']);
if (!str_contains($htmlVazio, 'Nenhum GAP em aberto para os filtros selecionados.')) { failFast('Estado vazio útil'); }
ok('Controller: whitelist, descarte de IDs de outro tenant, sem refletir entrada inválida, estado vazio');
asInstituto();

// ===================== Visão Geral: contadores = listagens =====================
foreach ([[], ['departamento_id' => $F['A']['deps'][1]], ['funcao_id' => $F['A']['funs'][2]]] as $org) {
    $R = $dash->resumo([$eA], ['inicio' => '2026-01-01', 'fim' => '2026-12-31'] + $org);
    $tag = json_encode($org);
    eq($m->listarGaps([$eA], $org + ['status' => 'aberto'], 1, 1)['total'], $R['gaps']['abertos'], "GAPs abertos = listagem $tag");
    eq($m->listarGaps([$eA], $org + ['status' => 'em_tratamento'], 1, 1)['total'], $R['gaps']['em_tratamento'], "GAPs em tratamento = listagem $tag");
    eq($m->listarGaps([$eA], $org + ['status' => 'aberto', 'tratamento' => 'sem_acao'], 1, 1)['total'], $R['gaps']['abertos_sem_acao'], "GAPs sem ação = listagem $tag");
    eq($m->listarAcoes([$eA], $org + ['atrasadas' => 1], 1, 1)['total'], $R['acoes']['vencidas'], "Ações atrasadas = listagem $tag");
    eq($m->listarAcoes([$eA], $org + ['status' => 'pendente'], 1, 1)['total'], $R['acoes']['pendentes'], "Ações pendentes = listagem $tag");
    eq($m->listarNecessidades([$eA], $org + ['status' => 'pendente'], 1, 1)['total'], $R['necessidades_pendentes'], "Necessidades pendentes = listagem $tag");
}
eq($dash->resumo([$eA], ['inicio' => '2026-01-01', 'fim' => '2026-12-31'])['gaps']['abertos_sem_acao'], 3, 'contador GAP aberto sem ação');
ok('Visão Geral e listagens usam a mesma semântica (com e sem filtros organizacionais)');

$_GET = ['route' => 'pessoas/visaoGeral', 'empresa_id' => (string)$eA, 'departamento_id' => (string)$F['A']['deps'][1], 'inicio' => '2026-01-01', 'fim' => '2026-12-31'];
ob_start();
(new PessoasVisaoController())->index();
$vh = (string)ob_get_clean();
$dep1 = $F['A']['deps'][1];
foreach ([
    "route=pessoas%2Fgaps&amp;empresa_id={$eA}&amp;departamento_id={$dep1}&amp;status=aberto&amp;tratamento=sem_acao",
    "route=pessoas%2Fgaps&amp;empresa_id={$eA}&amp;departamento_id={$dep1}&amp;status=aberto\"",
    "route=pessoas%2Facoes&amp;empresa_id={$eA}&amp;departamento_id={$dep1}&amp;atrasadas=1",
    "route=pessoas%2Fnecessidades&amp;empresa_id={$eA}&amp;departamento_id={$dep1}&amp;status=pendente",
] as $href) {
    if (!str_contains($vh, $href)) { failFast('Visão Geral deveria linkar: ' . $href); }
}
if (str_contains($vh, 'route=pessoas%2Fgaps&amp;empresa_id=' . $eA . '&amp;departamento_id=' . $dep1 . '&amp;inicio')) { failFast('Links de situação atual não devem carregar período'); }
if (!str_contains($vh, 'aria-label="Navegação do Pilar de Pessoas"')) { failFast('Subnavegação do Pilar ausente na Visão Geral'); }
ok('Visão Geral: pontos de atenção e cards abrem listagens filtradas (empresa + organização, sem período) e subnavegação presente');

echo "\nTodos os testes das listagens operacionais passaram.\n";
