<?php
require_once __DIR__ . '/../autoload.php';

use App\Models\ClienteModel;
use App\Models\CronogramaEventoModel;
use App\Models\CronogramaModel;

function assert_true($cond, $msg) {
  if (!$cond) { echo "FAIL: $msg\n"; exit(1); }
  echo "OK: $msg\n";
}

$_SESSION['user'] = [
  'id' => 1,
  'nome' => 'Instituto',
  'email' => 'instituto@example.com',
  'tipo_acesso' => 'instituto',
  'allowed_client_ids' => [],
];

$clientes = new ClienteModel();
$cronogramas = new CronogramaModel();
$eventos = new CronogramaEventoModel();

$clienteId = $clientes->create([
  'nome_empresa' => 'Teste Cronograma Eventos ' . uniqid(),
  'CNPJ' => '00.000.000/0001-00',
  'contato' => 'Teste',
]);
assert_true($clienteId > 0, 'Criou cliente para cronograma');

$cronogramaId = $cronogramas->create([
  'id_cliente' => $clienteId,
  'nome' => 'Cronograma Teste',
  'ano' => 2026,
]);
assert_true($cronogramaId > 0, 'Criou cronograma');

$roots = [];
$expectedCounts = [
  'unico' => 1,
  'mensal' => 12,
  'bimestral' => 6,
  'trimestral' => 4,
  'semestral' => 2,
  'anual' => 1,
];
$baseDates = [
  'unico' => '2026-01-10',
  'mensal' => '2026-01-15',
  'bimestral' => '2026-02-15',
  'trimestral' => '2026-03-15',
  'semestral' => '2026-01-20',
  'anual' => '2026-12-01',
];

foreach ($expectedCounts as $periodicidade => $expectedCount) {
  $rootId = $eventos->create($cronogramaId, [
    'data' => $baseDates[$periodicidade],
    'periodicidade' => $periodicidade,
    'topico' => 'Pilar ' . strtoupper($periodicidade),
    'unidade' => 'Departamento ' . strtoupper($periodicidade),
    'atividade' => 'Atividade ' . strtoupper($periodicidade),
    'responsavel' => 'Responsavel ' . strtoupper($periodicidade),
    'modelo' => 'Online',
    'status' => 'Planejado',
  ]);
  assert_true($rootId > 0, 'Criou serie com periodicidade ' . $periodicidade);
  $roots[$periodicidade] = $rootId;
  $members = $eventos->seriesMembers($rootId);
  assert_true(count($members) === $expectedCount, 'Gerou a quantidade esperada de ocorrencias para ' . $periodicidade);
}

$lista = $eventos->byCronograma($cronogramaId);
assert_true(count($lista) === array_sum($expectedCounts), 'Listou todas as ocorrencias do cronograma');

$grid = [];
foreach ($lista as $ev) {
  $serieId = (int)($ev['serie_id'] ?? $ev['id']);
  if (!isset($grid[$serieId])) {
    $grid[$serieId] = array_fill(1, 12, false);
  }
  $month = (int)date('n', strtotime($ev['data']));
  $grid[$serieId][$month] = true;
}
assert_true(count(array_filter($grid[$roots['mensal']])) === 12, 'Grid marca um mes por ocorrencia mensal');
assert_true(count(array_filter($grid[$roots['trimestral']])) === 4, 'Grid marca corretamente a serie trimestral');

$seriesMensal = $eventos->seriesMembers($roots['mensal']);
$firstChild = null;
foreach ($seriesMensal as $member) {
  if ((int)$member['id'] !== (int)$roots['mensal']) {
    $firstChild = $member;
    break;
  }
}
assert_true((bool)$firstChild, 'Encontrou uma ocorrencia filha para testar edicao individual');

$updatedSingle = $eventos->update((int)$firstChild['id'], [
  'data' => '2026-11-20',
  'periodicidade' => 'mensal',
  'topico' => $firstChild['topico'],
  'unidade' => $firstChild['unidade'],
  'atividade' => $firstChild['atividade'],
  'responsavel' => $firstChild['responsavel'],
  'modelo' => $firstChild['modelo'],
  'status' => 'Realizado',
], 'evento');
assert_true($updatedSingle, 'Atualizou apenas uma ocorrencia da serie');
$updatedSingleRow = $eventos->find((int)$firstChild['id']);
assert_true(($updatedSingleRow['status'] ?? '') === 'Realizado', 'Edicao individual altera apenas o registro selecionado');
$toggledBack = $eventos->setStatus((int)$firstChild['id'], 'Planejado');
assert_true($toggledBack, 'Toggle de status persiste no banco');
assert_true(($eventos->find((int)$firstChild['id'])['status'] ?? '') === 'Planejado', 'Toggle de status atualiza o registro corretamente');

$updatedSeries = $eventos->update((int)$roots['mensal'], [
  'data' => '2026-02-15',
  'periodicidade' => 'trimestral',
  'topico' => 'Pilar SERIE',
  'unidade' => 'Departamento SERIE',
  'atividade' => 'Atividade SERIE',
  'responsavel' => 'Responsavel SERIE',
  'modelo' => 'Presencial',
  'status' => 'Planejado',
], 'serie');
assert_true($updatedSeries, 'Atualizou a serie completa');
$updatedSeriesMembers = $eventos->seriesMembers($roots['mensal']);
assert_true(count($updatedSeriesMembers) === 4, 'Atualizacao em serie regenerou ocorrencias conforme nova periodicidade');
assert_true(($updatedSeriesMembers[0]['atividade'] ?? '') === 'Atividade SERIE', 'Atualizacao em serie propagou atividade');

$deleteSingle = $eventos->delete((int)$updatedSeriesMembers[1]['id'], 'evento');
assert_true($deleteSingle, 'Excluiu apenas uma ocorrencia');
assert_true(count($eventos->seriesMembers($roots['mensal'])) === 3, 'Exclusao individual manteve a serie integra');

$deleteSeries = $eventos->delete((int)$roots['mensal'], 'serie');
assert_true($deleteSeries, 'Excluiu toda a serie');
assert_true(count($eventos->seriesMembers($roots['mensal'])) === 0, 'Exclusao em serie removeu todas as ocorrencias');

echo "All Cronograma eventos tests passed.\n";
