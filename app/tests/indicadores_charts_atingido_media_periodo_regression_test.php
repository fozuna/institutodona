<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\IndicadoresController;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\IndicadorEventoModel;
use App\Models\IndicadorModel;
use App\Models\SetorModel;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

try {
    Database::getConnection();
} catch (\Throwable $e) {
    echo "SKIP: Sem conexão com DB no ambiente atual.\n";
    exit(0);
}

$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$cleanup = ['indicador_id' => 0, 'setor_id' => 0, 'departamento_id' => 0, 'unidade_id' => 0, 'cliente_id' => 0];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        if (!empty($cleanup['indicador_id'])) {
            $pdo->prepare('DELETE FROM indicador_eventos WHERE indicador_id = :id')->execute(['id' => $cleanup['indicador_id']]);
            $pdo->prepare('DELETE FROM indicadores WHERE id = :id')->execute(['id' => $cleanup['indicador_id']]);
        }
        if (!empty($cleanup['setor_id'])) { $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $cleanup['setor_id']]); }
        if (!empty($cleanup['departamento_id'])) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $cleanup['departamento_id']]); }
        if (!empty($cleanup['unidade_id'])) { $pdo->prepare('DELETE FROM unidades_medida WHERE id = :id')->execute(['id' => $cleanup['unidade_id']]); }
        if (!empty($cleanup['cliente_id'])) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $cleanup['cliente_id']]); }
    } catch (\Throwable $e) {}
});

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$model = new IndicadorModel();
$eventoModel = new IndicadorEventoModel();

$clienteId = $clientes->create(['nome_empresa' => 'Cliente Atingido ' . $suffix, 'CNPJ' => '55.444.3' . substr($suffix, 0, 2) . '/0001-55', 'contato' => 'Teste']);
if ($clienteId <= 0) failFast('Falha ao criar cliente');
$cleanup['cliente_id'] = $clienteId;

$departamentoId = $departamentos->create(['nome' => 'Dep Atingido ' . $suffix, 'cliente_id' => $clienteId, 'cliente_ids' => [$clienteId]]);
$setorId = $setores->create(['nome' => 'Setor Atingido ' . $suffix, 'departamento_id' => $departamentoId]);
$cleanup['departamento_id'] = $departamentoId;
$cleanup['setor_id'] = $setorId;

$stmt = $pdo->prepare('INSERT INTO unidades_medida (nome, simbolo, tipo, ativo) VALUES (:n, :s, :t, 1)');
$stmt->execute(['n' => 'Unidade Atingido ' . $suffix, 's' => 'un', 't' => 'decimal']);
$unidadeId = (int)$pdo->lastInsertId();
$cleanup['unidade_id'] = $unidadeId;

$payload = [
    'cliente_id' => $clienteId,
    'indicador' => 'Indicador Atingido ' . $suffix,
    'departamento_id' => $departamentoId,
    'setor_id' => $setorId,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-03-31',
    'valor' => '100',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => $unidadeId,
    'valor_minimo' => '0',
    'valor_maximo' => '1000',
];
$errors = $model->validate($payload);
if ($errors) failFast('Payload inválido: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
$indicadorId = $model->create($payload, 1);
if ($indicadorId <= 0) failFast('Falha ao criar indicador');
$cleanup['indicador_id'] = $indicadorId;

$eventos = $eventoModel->byIndicador($indicadorId);
if (count($eventos) < 3) failFast('Indicador não gerou os 3 eventos mensais esperados (Jan/Fev/Mar)');

$eventoJan = null;
$eventoFev = null;
$eventoMar = null;
foreach ($eventos as $evento) {
    if (strpos((string)$evento['data_evento'], '2026-01') === 0) $eventoJan = $evento;
    if (strpos((string)$evento['data_evento'], '2026-02') === 0) $eventoFev = $evento;
    if (strpos((string)$evento['data_evento'], '2026-03') === 0) $eventoMar = $evento;
}
if (!$eventoJan || !$eventoFev || !$eventoMar) failFast('Não foi possível localizar os eventos de Jan/Fev/Mar');

// Jan: atingido 80 -> 80% de cumprimento. Fev: sem lançamento (fica pendente). Mar: atingido 120 -> 120%.
if (!$eventoModel->updateAchievedValue((int)$eventoJan['id'], '80', 1)) failFast('Falha ao lançar valor de Janeiro');
if (!$eventoModel->updateAchievedValue((int)$eventoMar['id'], '120', 1)) failFast('Falha ao lançar valor de Março');
ok('Criou indicador com 3 eventos mensais; lançou Jan (80%) e Mar (120%), deixou Fev pendente');

function renderChartsHtml(array $get): string
{
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = $get;
    ob_start();
    (new IndicadoresController())->charts();
    return (string)ob_get_clean();
}

function extractAtingido(string $html, string $suffix): ?string
{
    if (!preg_match('/data-indicador-title="[^"]*' . preg_quote($suffix, '/') . '[^"]*"[^>]*data-indicador-achieved="([^"]*)"/', $html, $m)) {
        return null;
    }
    return html_entity_decode($m[1]);
}

// 1) Período cobrindo Jan-Mar: média deve ignorar Fev (sem lançamento) e não repetir o último valor (Mar=120%).
$html = renderChartsHtml([
    'route' => 'indicadores/charts',
    'cliente' => (string)$clienteId,
    'periodo_inicio' => '2026-01-01',
    'periodo_fim' => '2026-03-31',
]);
$atingido = extractAtingido($html, $suffix);
if ($atingido === null) failFast('Não encontrou o card do indicador na resposta de charts()');
if ($atingido === '120,00%') failFast('Regressão: o card "Atingido" voltou a mostrar o valor do último lançamento (120%) em vez da média do período');
if ($atingido !== '100,00%') failFast('Média esperada de 100,00% (ignorando Fev sem lançamento); obtido: ' . $atingido);
ok('Card "Atingido" mostra a média do percentual de cumprimento do período (100%), ignorando o mês sem lançamento, e não repete o último valor (120%)');

// 2) Período restrito a Jan-Fev: só Jan tem lançamento -> média deve ser exatamente 80%, sem considerar Março (fora do filtro).
$htmlJanFev = renderChartsHtml([
    'route' => 'indicadores/charts',
    'cliente' => (string)$clienteId,
    'periodo_inicio' => '2026-01-01',
    'periodo_fim' => '2026-02-28',
]);
$atingidoJanFev = extractAtingido($htmlJanFev, $suffix);
if ($atingidoJanFev !== '80,00%') failFast('Filtrando Jan-Fev, esperava 80,00% (só o lançamento de Janeiro, Março fora do filtro); obtido: ' . $atingidoJanFev);
ok('Período restrito não inclui lançamentos fora do filtro (Março ignorado ao filtrar só Jan-Fev)');

echo "Indicadores charts atingido media periodo regression tests passed.\n";
