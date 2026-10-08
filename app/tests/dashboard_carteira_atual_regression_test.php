<?php
// Dashboard: o bloco "Carteira atual" do cabeçalho passa a mostrar as médias
// de Cumprimento do Cronograma / Auditorias / Indicadores Estratégicos -
// EXATAMENTE a mesma fonte dos 3 cards abaixo (nenhuma consulta/fórmula
// paralela, nenhuma média entre os três indicadores). A sincronização com
// filtros é via JS (mesmo payload de dashboard/metrics que já alimenta os
// cards), já que os cards atualizam sem reload de página.
require_once __DIR__ . '/../autoload.php';

use App\Controllers\DashboardController;
use App\Database\Database;
use App\Models\ClienteModel;

function ok(string $m): void { echo "OK: $m\n"; }
function failFast(string $m): void { echo "FAIL: $m\n"; exit(1); }

function readFilters(DashboardController $controller): array
{
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('readDashboardFilters');
    $m->setAccessible(true);
    return $m->invoke($controller);
}

function computeMetricsOf(DashboardController $controller, array $filters): array
{
    $ref = new ReflectionClass($controller);
    $m = $ref->getMethod('computeMetrics');
    $m->setAccessible(true);
    return $m->invoke($controller, $filters);
}

/** Extrai o corpo de `function $name(...) { ... }` do JS (balanceamento de chaves). */
function extractJsFunction(string $source, string $name): ?string
{
    $needle = 'function ' . $name . '(';
    $start = strpos($source, $needle);
    if ($start === false) {
        return null;
    }
    $braceOpen = strpos($source, '{', $start);
    if ($braceOpen === false) {
        return null;
    }
    $depth = 0;
    for ($i = $braceOpen; $i < strlen($source); $i++) {
        if ($source[$i] === '{') { $depth++; }
        elseif ($source[$i] === '}') { $depth--; if ($depth === 0) { return substr($source, $start, $i - $start + 1); } }
    }
    return null;
}

try {
    Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: sem conexão com DB no ambiente atual.\n";
    exit(0);
}

$_SESSION['user'] = ['id' => 1, 'nome' => 'Instituto', 'email' => 'instituto@example.com', 'tipo_acesso' => 'instituto', 'allowed_client_ids' => []];

// ===================== 1. Markup: bloco "Carteira atual" trocado =====================
$view = (string)file_get_contents(__DIR__ . '/../views/dashboard/kanban.php');
if (!preg_match('/<aside class="dash-mini">.*?<\/aside>/s', $view, $mAside)) {
    failFast('Bloco "Carteira atual" (aside.dash-mini) não encontrado na view');
}
$aside = $mAside[0];
foreach (['Cumprimento do Cronograma', 'Auditorias', 'Indicadores Estratégicos'] as $label) {
    if (!str_contains($aside, $label)) { failFast("Carteira atual deveria conter a linha \"$label\""); }
}
foreach (['Planejado', 'Em Andamento', 'Concluído', 'Pendente'] as $old) {
    if (str_contains($aside, '>' . $old . '<') || str_contains($aside, '>' . $old . ' ')) {
        failFast("Carteira atual não deveria mais conter a linha antiga \"$old\"");
    }
}
foreach (['dashCarteiraCronLabel', 'dashCarteiraCronFill', 'dashCarteiraAudLabel', 'dashCarteiraAudFill', 'dashCarteiraIndLabel', 'dashCarteiraIndFill'] as $id) {
    if (!str_contains($aside, $id)) { failFast("Carteira atual deveria conter o elemento #$id"); }
}
ok('Bloco "Carteira atual" mostra os 3 indicadores (Cronograma/Auditorias/Indicadores), sem as 4 linhas antigas');

// Regressão: a OUTRA seção que usa as mesmas 4 linhas ("Indicadores rápidos",
// mais abaixo na página) não pode ter sido afetada.
if (!preg_match('/Indicadores rápidos.*?dash-quick-grid(.*?)<\/div>\s*<\/div>\s*<\/article>/s', $view, $mQuick)) {
    failFast('Seção "Indicadores rápidos" não encontrada para checagem de regressão');
}
foreach (['Planejado', 'Em Andamento', 'Concluído', 'Pendente'] as $old) {
    if (!str_contains($mQuick[1], $old)) { failFast("Regressão: \"Indicadores rápidos\" deveria continuar mostrando \"$old\""); }
}
ok('Regressão: seção "Indicadores rápidos" (fora do escopo desta mudança) preservada');

// ===================== 2. JS: renderCarteira() lê os MESMOS campos dos cards =====================
$fnCarteira = extractJsFunction($view, 'renderCarteira');
if ($fnCarteira === null) { failFast('Função JS renderCarteira() não encontrada'); }
foreach (['json?.cronograma?.pct', 'json?.auditorias?.media_conformidade_pct', 'json?.indicadores?.media_atingimento_pct'] as $campo) {
    if (!str_contains($fnCarteira, $campo)) { failFast("renderCarteira() deveria ler $campo (mesmo campo usado pelos cards) - sem cálculo paralelo"); }
}
if (substr_count($fnCarteira, 'renderCarteiraLinha(') !== 3) { failFast('renderCarteira() deveria chamar renderCarteiraLinha() exatamente 3 vezes (uma por indicador)'); }
ok('renderCarteira() lê EXATAMENTE os mesmos 3 campos dos cards (cronograma.pct, auditorias.media_conformidade_pct, indicadores.media_atingimento_pct)');

$fnLoadMetrics = extractJsFunction($view, 'loadMetrics');
if ($fnLoadMetrics === null) { failFast('loadMetrics() não encontrada'); }
if (!str_contains($fnLoadMetrics, 'renderCarteira(json)')) { failFast('loadMetrics() deveria chamar renderCarteira(json) para manter a Carteira Atual sincronizada com os filtros, assim como os cards'); }
ok('loadMetrics() chama renderCarteira(json) - Carteira Atual atualiza junto com os cards a cada mudança de filtro (sem reload de página)');

// ===================== 3. Lógica de —%/0%/>100%/negativo (executada de verdade via Node, se disponível) =====================
$fnFormatPct = extractJsFunction($view, 'formatPct');
$fnLinha = extractJsFunction($view, 'renderCarteiraLinha');
$nodeBin = trim((string)shell_exec('node --version 2>&1'));
if ($fnFormatPct === null || $fnLinha === null || $nodeBin === '' || !str_starts_with($nodeBin, 'v')) {
    echo "SKIP: Node.js indisponível ou funções não localizadas - pulando verificação funcional de —%/0%/>100%\n";
} else {
    $harness = $fnFormatPct . "\n" . $fnLinha . "\n" . <<<'JS'
function mockEl() { return { textContent: '', style: { width: '' } }; }
const casos = [
  { valor: null, label: '—%', largura: '0%' },
  { valor: undefined, label: '—%', largura: '0%' },
  { valor: 0, label: '0,00%', largura: '0%' },
  { valor: 55.5, label: '55,50%', largura: '55.5%' },
  { valor: 150, label: '150,00%', largura: '100%' },
  { valor: -10, label: '-10,00%', largura: '0%' },
];
let falhas = 0;
for (const c of casos) {
  const label = mockEl();
  const fill = mockEl();
  renderCarteiraLinha(label, fill, c.valor);
  if (label.textContent !== c.label) { console.log(`FAIL: valor=${c.valor} esperava label "${c.label}" obteve "${label.textContent}"`); falhas++; }
  if (fill.style.width !== c.largura) { console.log(`FAIL: valor=${c.valor} esperava largura "${c.largura}" obteve "${fill.style.width}"`); falhas++; }
}
console.log(falhas === 0 ? 'OK' : ('FALHAS=' + falhas));
JS;
    $tmp = tempnam(sys_get_temp_dir(), 'dashcarteira_') . '.js';
    file_put_contents($tmp, $harness);
    $out = shell_exec('node ' . escapeshellarg($tmp) . ' 2>&1');
    @unlink($tmp);
    if (trim((string)$out) !== 'OK') {
        failFast('Verificação funcional (Node) de renderCarteiraLinha falhou: ' . trim((string)$out));
    }
    ok('renderCarteiraLinha(): null/undefined -> "—%" (nunca "0%"); 0 real -> "0,00%"; >100% preserva o valor no texto e limita a barra a 100%; negativo preserva o texto e limita a barra a 0% (executado com Node)');
}

// ===================== 4. Integração: Controller/View - período e nº de empresas preservados =====================
$clientes = new ClienteModel();
$suffix = 'dashcarteira_' . date('YmdHis') . '_' . random_int(100, 999);
$makeCnpj = static function (): string {
    $base = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
    return substr($base, 0, 2) . '.' . substr($base, 2, 3) . '.' . substr($base, 5, 3) . '/' . substr($base, 8, 4) . '-' . substr($base, 12, 2);
};
$clienteId = $clientes->create(['nome_empresa' => 'Cliente Carteira ' . $suffix, 'CNPJ' => $makeCnpj(), 'contato' => 'Contato']);
if ($clienteId <= 0) { failFast('Falha ao criar cliente de teste'); }
register_shutdown_function(static function () use ($clienteId) {
    try { Database::getConnection()->exec('DELETE FROM clientes WHERE id=' . (int)$clienteId); } catch (\Throwable $e) {}
});

$_GET = ['route' => 'dashboard/index', 'cliente' => (string)$clienteId, 'month_start' => '2026-01', 'month_end' => '2026-01'];
$controller = new DashboardController();
$filters = readFilters($controller);
$metrics = computeMetricsOf($controller, $filters);
if (!array_key_exists('pct', $metrics['cronograma'] ?? []) || !array_key_exists('media_conformidade_pct', $metrics['auditorias'] ?? []) || !array_key_exists('media_atingimento_pct', $metrics['indicadores'] ?? [])) {
    failFast('computeMetrics() não retornou os 3 campos que a Carteira Atual e os cards consomem');
}
// Sem dados: cronograma.pct é 0.0 (nunca null - mesma regra do card); auditorias/indicadores são null (ausência de dados, igual ao card "—").
if ($metrics['cronograma']['pct'] !== 0.0) { failFast('Sem eventos de cronograma, pct deveria ser 0.0 (não nulo) - mesma regra do card'); }
if ($metrics['auditorias']['media_conformidade_pct'] !== null) { failFast('Sem auditorias, media_conformidade_pct deveria ser null (ausência de dados = "—", nunca 0%)'); }
if ($metrics['indicadores']['media_atingimento_pct'] !== null) { failFast('Sem eventos de indicadores, media_atingimento_pct deveria ser null (ausência de dados = "—", nunca 0%)'); }
ok('Ausência de dados: cronograma usa 0,00% (mesma regra do card, não tem estado "sem dados"); auditorias/indicadores usam null -> "—%" (nunca 0%)');

ob_start();
$controller2 = new DashboardController();
$_GET = ['route' => 'dashboard/index', 'cliente' => (string)$clienteId, 'month_start' => '2026-01', 'month_end' => '2026-01'];
$controller2->index();
$html = (string)ob_get_clean();
if (!preg_match('/<aside class="dash-mini">.*?<\/aside>/s', $html, $mAside2)) { failFast('Render real não produziu o bloco Carteira Atual'); }
$asideReal = $mAside2[0];
if (!str_contains($asideReal, '>1</span> empresa(s)')) { failFast('Carteira Atual deveria indicar 1 empresa selecionada: ' . substr($asideReal, 0, 300)); }
if (!preg_match('/dashCarteiraPeriodLabel">[^<]*Janeiro[^<]*2026/', $asideReal)) { failFast('Carteira Atual deveria indicar o período (Janeiro/2026) no carregamento inicial'); }
if (!str_contains($asideReal, '—%')) { failFast('Carteira Atual deveria iniciar com "—%" antes do primeiro carregamento via AJAX (mesmo estado inicial dos cards)'); }
ok('Render real: nº de empresas e período preservados no bloco Carteira Atual; estado inicial "—%" igual aos cards');

echo "\nTodos os testes do bloco Carteira Atual passaram.\n";
