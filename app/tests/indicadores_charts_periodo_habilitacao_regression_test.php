<?php
// Item 01 (bug secundário confirmado): ao selecionar um Cliente na tela de
// Gráficos de Indicadores sem recarregar a página, o JavaScript reabilitava
// Departamento e Indicador via AJAX, mas nunca reabilitava os campos de
// Período de apuração (`periodo_inicio`/`periodo_fim`) - eles permaneciam
// `disabled` (renderizados assim no primeiro carregamento, sem Cliente),
// impedindo o usuário de informar um período já no primeiro filtro da
// sessão de navegação (só funcionava depois de um reload completo com
// `cliente` na URL, quando o PHP já renderizava os campos habilitados).
//
// Este teste reproduz o payload HTML/JS real entregue ao navegador
// (via IndicadoresController::charts(), sem mock de view) e verifica:
//  1) o estado inicial correto (sem Cliente = período disabled; com
//     Cliente = período habilitado) - comportamento server-side já correto
//     e preservado;
//  2) que o `<script>` da tela liga a reabilitação dos campos de período
//     ao evento `change` do select de Cliente, para o caso em que o
//     usuário seleciona o Cliente sem reload de página.
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Controllers\IndicadoresController;
use App\Models\IndicadorModel;

ob_start();

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['indicador_ids' => [], 'setor_ids' => [], 'departamento_ids' => [], 'unidade_id' => 0, 'cliente_ids' => []];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        foreach ($cleanup['indicador_ids'] as $id) {
            $pdo->prepare('DELETE FROM indicador_eventos WHERE indicador_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $id]);
        }
        foreach ($cleanup['setor_ids'] as $id) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $id]); }
        foreach ($cleanup['departamento_ids'] as $id) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $id]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        foreach ($cleanup['cliente_ids'] as $id) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $id]); }
    } catch (\Throwable $e) {}
});

$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
$stmt->execute(['nome' => "Cliente ChartsPeriodo {$suffix}", 'cnpj' => '55.555.5' . substr($suffix, 0, 1) . '/0001-55', 'contato' => 'Test']);
$clienteId = (int)$pdo->lastInsertId();
$cleanup['cliente_ids'][] = $clienteId;

$stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
$stmt->execute(['nome' => "Dep ChartsPeriodo {$suffix}", 'cid' => $clienteId]);
$depId = (int)$pdo->lastInsertId();
$cleanup['departamento_ids'][] = $depId;

$stmt = $pdo->prepare('INSERT INTO setores (nome, departamento_id) VALUES (:nome, :did)');
$stmt->execute(['nome' => "Setor ChartsPeriodo {$suffix}", 'did' => $depId]);
$setorId = (int)$pdo->lastInsertId();
$cleanup['setor_ids'][] = $setorId;

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:nome, :simbolo, :tipo, 1)');
$stmt->execute(['nome' => 'Unidade ChartsPeriodo Teste ' . $suffix, 'simbolo' => '', 'tipo' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

$model = new IndicadorModel();
$payload = [
    'cliente_id' => $clienteId,
    'indicador' => "Indicador ChartsPeriodo {$suffix}",
    'departamento_id' => $depId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => date('Y-m-01'),
    'data_final' => date('Y-m-t'),
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = $model->validate($payload);
if ($errors) failFast('Payload inválido para fixture: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $model->create($payload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador de fixture');
$cleanup['indicador_ids'][] = $indicadorId;
ok('Fixture criada: Cliente + Departamento + Setor + Indicador');

function renderCharts(array $get): string
{
    $_GET = $get;
    ob_start();
    (new IndicadoresController())->charts();
    return (string)ob_get_clean();
}

// Cenário 1: sem Cliente -> campos de período continuam "disabled" no HTML
// inicial (comportamento server-side já correto, preservado sem alteração).
$htmlSemCliente = renderCharts(['route' => 'indicadores/charts']);
if (!preg_match('/name="periodo_inicio"[^>]*disabled/', $htmlSemCliente)) {
    failFast('Cenário 1: sem Cliente, "Início" deveria continuar disabled no HTML inicial.');
}
if (!preg_match('/name="periodo_fim"[^>]*disabled/', $htmlSemCliente)) {
    failFast('Cenário 1: sem Cliente, "Fim" deveria continuar disabled no HTML inicial.');
}
ok('Cenário 1: sem Cliente, período nasce disabled (preservado)');

// Cenário 2: com Cliente já na URL (reload completo) -> período já nasce
// habilitado (comportamento server-side já correto, preservado).
$htmlComCliente = renderCharts(['route' => 'indicadores/charts', 'cliente' => (string)$clienteId]);
if (preg_match('/name="periodo_inicio"[^>]*disabled/', $htmlComCliente)) {
    failFast('Cenário 2: com Cliente na URL, "Início" não deveria estar disabled.');
}
if (preg_match('/name="periodo_fim"[^>]*disabled/', $htmlComCliente)) {
    failFast('Cenário 2: com Cliente na URL, "Fim" não deveria estar disabled.');
}
ok('Cenário 2: com Cliente já na URL, período nasce habilitado (preservado)');

// Cenário 3: o JS entregue ao navegador precisa reabilitar o período no
// MESMO carregamento de página, quando o usuário troca o Cliente sem
// reload (o bug relatado). Isola o <script> final da view.
$scriptBlocks = [];
if (!preg_match_all('/<script>(.*?)<\/script>/s', $htmlSemCliente, $scriptBlocks)) {
    failFast('Cenário 3: não foi possível localizar nenhum bloco <script> na tela.');
}
$wiringScript = null;
foreach ($scriptBlocks[1] as $block) {
    if (strpos($block, 'indicadoresChartsClienteSelect') !== false) {
        $wiringScript = $block;
        break;
    }
}
if ($wiringScript === null) {
    failFast('Cenário 3: não encontrou o bloco <script> responsável pela cascata Cliente/Departamento/Indicador/Período.');
}

if (!preg_match('/clienteSelect\?\.addEventListener\([\'"]change[\'"],\s*\(\)\s*=>\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}\s*\)\s*;/s', $wiringScript, $handlerMatch)) {
    failFast('Cenário 3: não encontrou o listener de "change" do select de Cliente.');
}
$handlerBody = $handlerMatch[1];

if (strpos($handlerBody, 'syncPeriodoFields()') === false) {
    failFast('Cenário 3 (BUG): o listener de troca de Cliente não chama nenhuma rotina para reabilitar o período - os campos de período ficam presos em "disabled" até um reload completo da página.');
}
ok('Cenário 3: troca de Cliente sem reload aciona a reabilitação do período (syncPeriodoFields)');

// Cenário 4: a rotina de sincronização realmente alterna o `disabled` dos
// dois campos de período com base em o Cliente estar selecionado ou não,
// e limpa os valores quando o Cliente é removido - não é só uma chamada
// "solta" sem efeito real.
if (!preg_match('/function\s+syncPeriodoFields\s*\(\)\s*\{(.*?)\n\s*\}/s', $wiringScript, $fnMatch)) {
    failFast('Cenário 4: não encontrou a definição da função syncPeriodoFields no script entregue.');
}
$fnBody = $fnMatch[1];
if (strpos($fnBody, 'periodoInicioInput.disabled') === false || strpos($fnBody, 'periodoFimInput.disabled') === false) {
    failFast('Cenário 4 (BUG): syncPeriodoFields não altera o atributo disabled dos dois campos de período.');
}
if (strpos($fnBody, "clienteSelect") === false) {
    failFast('Cenário 4: syncPeriodoFields não considera o valor atual do select de Cliente.');
}
ok('Cenário 4: syncPeriodoFields alterna disabled dos dois campos de período conforme o Cliente selecionado');

echo "Indicadores charts periodo habilitacao regression tests passed.\n";
