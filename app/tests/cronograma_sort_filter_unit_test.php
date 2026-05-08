<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\CronogramaController;

function assert_true($cond, $msg) {
  if (!$cond) { echo "FAIL: $msg\n"; exit(1); }
  echo "OK: $msg\n";
}

/**
 * Executa um método privado usando Reflection.
 * @param object $obj
 * @param string $method
 * @param array $args
 * @return mixed
 */
function invoke_private($obj, $method, array $args = []) {
  $ref = new ReflectionClass($obj);
  $m = $ref->getMethod($method);
  $m->setAccessible(true);
  return $m->invokeArgs($obj, $args);
}

$controller = new CronogramaController();

$events = [
  [
    'id' => 1,
    'data' => '2026-05-10',
    'topico' => 'Árvore',
    'atividade' => 'Atividade A',
    'responsavel' => 'João',
    'periodicidade' => 'mensal',
    'unidade' => 'São Paulo',
    'status' => 'Planejado',
    'traffic' => ['label' => 'Pendente'],
  ],
  [
    'id' => 2,
    'data' => '2026-04-15',
    'topico' => 'Banana',
    'atividade' => 'Atividade B',
    'responsavel' => 'Maria',
    'periodicidade' => 'unico',
    'unidade' => 'Curitiba',
    'status' => 'Finalizado',
    'traffic' => ['label' => 'Finalizado'],
  ],
  [
    'id' => 3,
    'data' => '2026-06-20',
    'topico' => 'Coração',
    'atividade' => 'Atividade C',
    'responsavel' => 'João',
    'periodicidade' => 'trimestral',
    'unidade' => 'São Paulo',
    'status' => 'Planejado',
    'traffic' => ['label' => 'Atrasado'],
  ],
];

$filters = [
  'date_start' => '2026-04-01',
  'date_end' => '2026-05-31',
  'tipo' => ['Árvore', 'Banana'],
  'status' => ['Pendente', 'Finalizado'],
  'responsavel' => 'jo',
  'local' => 'são',
];

$filtered = invoke_private($controller, 'filterEventsByCriteria', [$events, $filters]);
assert_true(count($filtered) === 1, 'Filtro AND retorna apenas a ocorrência que atende todos os critérios');
assert_true($filtered[0]['id'] === 1, 'Filtro AND mantém a ocorrência esperada');

$filters['responsavel'] = '';
$filters['local'] = '';
$filtered = invoke_private($controller, 'filterEventsByCriteria', [$events, $filters]);
assert_true(count($filtered) === 2, 'Filtro por data, tipo e status funciona sem responsável/local');

$sortedByDate = invoke_private($controller, 'sortEvents', [$events, ['column' => 'data', 'direction' => 'asc']]);
assert_true($sortedByDate[0]['id'] === 2, 'Ordenação por data crescente funciona');

$sortedDesc = invoke_private($controller, 'sortEvents', [$events, ['column' => 'data', 'direction' => 'desc']]);
assert_true($sortedDesc[0]['id'] === 3, 'Ordenação por data decrescente funciona');

$sortedByTopico = invoke_private($controller, 'sortEvents', [$events, ['column' => 'topico', 'direction' => 'asc']]);
if (class_exists('Collator')) {
  assert_true($sortedByTopico[0]['topico'] === 'Árvore', 'Ordenação com acentos usa Collator pt_BR');
}
assert_true(in_array($sortedByTopico[0]['topico'], ['Árvore', 'Banana'], true), 'Ordenação alfabética respeita acentos com fallback seguro');

echo "Cronograma sort/filter unit tests passed.\n";
