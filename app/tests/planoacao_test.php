<?php
// Simple assertions without external frameworks
require_once __DIR__ . '/../autoload.php';

use App\Models\PlanoAcaoTaskModel;
use App\Models\PlanoAcaoMetricModel;
use App\Models\PlanoAcaoCheckModel;
use App\Models\PlanoAcaoActionModel;

function assert_true($cond, $msg) {
  if (!$cond) { echo "FAIL: $msg\n"; exit(1); }
  echo "OK: $msg\n";
}

$tasks = new PlanoAcaoTaskModel();
$metrics = new PlanoAcaoMetricModel();
$checks = new PlanoAcaoCheckModel();
$actions = new PlanoAcaoActionModel();

// Create task
$taskId = $tasks->create([
  'id_cliente' => 1,
  'titulo' => 'Teste Plano de Ação',
  'descricao' => 'Desc',
  'meta_valor' => 10,
  'meta_unidade' => 'un',
  'prazo' => date('Y-m-d', strtotime('+7 days')),
  'responsavel' => 'Tester',
  'fase' => 'DO',
  'status' => 'A Fazer',
  'progresso' => 0,
]);
assert_true($taskId > 0, 'Criou tarefa Plano de Ação');

// Metric
$ok = $metrics->upsert($taskId, ['nome' => 'Indicador', 'planejado' => 10, 'realizado' => 5, 'unidade' => 'un']);
assert_true($ok, 'Inseriu métrica');

$list = $metrics->byTask($taskId);
assert_true(count($list) === 1, 'Listou métricas');

// Check
$cid = $checks->add($taskId, ['gap' => 5, 'analise' => 'Diferença']);
assert_true($cid > 0, 'Registrou check');

// Action
$aid = $actions->create($taskId, ['titulo' => 'Plano de melhoria', 'owner' => 'Tester', 'due_date' => date('Y-m-d', strtotime('+10 days')), 'status' => 'Planejado']);
assert_true($aid > 0, 'Criou ação');

echo "All Plano de Ação tests passed.\n";
