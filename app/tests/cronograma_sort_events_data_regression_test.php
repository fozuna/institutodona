<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\CronogramaController;

function assert_true($cond, $msg) {
  if (!$cond) { echo "FAIL: $msg\n"; exit(1); }
  echo "OK: $msg\n";
}

function invoke_private($obj, $method, array $args = []) {
  $ref = new ReflectionClass($obj);
  $m = $ref->getMethod($method);
  $m->setAccessible(true);
  return $m->invokeArgs($obj, $args);
}

$controller = new CronogramaController();

// Cenário 1: eventos sem data devem ficar sempre no final, tanto em ordem crescente quanto decrescente.
$eventsWithGaps = [
  ['id' => 1, 'data' => '', 'atividade' => 'Sem data 1'],
  ['id' => 2, 'data' => '2026-03-10', 'atividade' => 'Com data cedo'],
  ['id' => 3, 'data' => null, 'atividade' => 'Sem data 2'],
  ['id' => 4, 'data' => '2026-08-20', 'atividade' => 'Com data tarde'],
];

$asc = invoke_private($controller, 'sortEvents', [$eventsWithGaps, ['column' => 'data', 'direction' => 'asc']]);
assert_true($asc[0]['id'] === 2 && $asc[1]['id'] === 4, 'Ordenação crescente por data: eventos com data aparecem primeiro, na ordem cronológica');
assert_true(in_array($asc[2]['id'], [1, 3], true) && in_array($asc[3]['id'], [1, 3], true), 'Ordenação crescente por data: eventos sem data ficam no final');

$desc = invoke_private($controller, 'sortEvents', [$eventsWithGaps, ['column' => 'data', 'direction' => 'desc']]);
assert_true($desc[0]['id'] === 4 && $desc[1]['id'] === 2, 'Ordenação decrescente por data: eventos com data aparecem primeiro, na ordem cronológica invertida');
assert_true(in_array($desc[2]['id'], [1, 3], true) && in_array($desc[3]['id'], [1, 3], true), 'Ordenação decrescente por data: eventos sem data continuam no final, não vão para o início');

// Cenário 2: desempate estável quando as datas são iguais - por atividade, depois por id.
$sameDateEvents = [
  ['id' => 30, 'data' => '2026-05-01', 'atividade' => 'Zebra'],
  ['id' => 10, 'data' => '2026-05-01', 'atividade' => 'Abacaxi'],
  ['id' => 20, 'data' => '2026-05-01', 'atividade' => 'Abacaxi'],
];
$sortedTie = invoke_private($controller, 'sortEvents', [$sameDateEvents, ['column' => 'data', 'direction' => 'asc']]);
assert_true($sortedTie[0]['atividade'] === 'Abacaxi' && $sortedTie[1]['atividade'] === 'Abacaxi', 'Desempate por data igual usa atividade em ordem alfabética');
assert_true($sortedTie[0]['id'] === 10 && $sortedTie[1]['id'] === 20, 'Desempate por data e atividade iguais usa o id como critério final estável');
assert_true($sortedTie[2]['id'] === 30, 'Evento com atividade posterior alfabeticamente aparece depois, mesma data');

echo "Cronograma sortEvents data/tiebreak regression tests passed.\n";
