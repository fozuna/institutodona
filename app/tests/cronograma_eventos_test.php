<?php
require_once __DIR__ . '/../autoload.php';

use App\Models\ClienteModel;
use App\Models\CronogramaModel;
use App\Models\CronogramaEventoModel;

function assert_true($cond, $msg) {
  if (!$cond) { echo "FAIL: $msg\n"; exit(1); }
  echo "OK: $msg\n";
}

$clientes = new ClienteModel();
$cronogramas = new CronogramaModel();
$eventos = new CronogramaEventoModel();

$clienteId = $clientes->create([
  'nome_empresa' => 'Teste Cronograma Eventos ' . uniqid(),
  'CNPJ' => '00.000.000/0000-00',
  'contato' => 'Teste',
]);
assert_true($clienteId > 0, 'Criou cliente para cronograma');

$cronogramaId = $cronogramas->create([
  'id_cliente' => $clienteId,
  'nome' => 'Cronograma Teste',
  'ano' => (int)date('Y'),
]);
assert_true($cronogramaId > 0, 'Criou cronograma');

$dataEvento = date('Y-m-d', strtotime('+1 day'));
$eventoId = $eventos->create($cronogramaId, [
  'data' => $dataEvento,
  'topico' => 'Tópico Teste',
  'unidade' => 'Unidade X',
  'atividade' => 'Atividade Teste',
  'responsavel' => 'Responsável Teste',
  'modelo' => 'Online',
  'status' => 'Planejado',
]);
assert_true($eventoId > 0, 'Criou evento no cronograma');

$lista = $eventos->byCronograma($cronogramaId);
assert_true(count($lista) >= 1, 'Listou eventos do cronograma');

$encontrado = false;
foreach ($lista as $ev) {
  if ((int)$ev['id'] === $eventoId) {
    $encontrado = true;
    assert_true($ev['data'] === $dataEvento, 'Data do evento confere');
    assert_true($ev['topico'] === 'Tópico Teste', 'Tópico do evento confere');
    break;
  }
}
assert_true($encontrado, 'Evento recém-criado está presente na lista');

$grid = [];
foreach ($lista as $ev) {
  $key = implode('|', [
    $ev['topico'],
    $ev['unidade'] ?? '',
    $ev['atividade'],
    $ev['responsavel'] ?? '',
    $ev['modelo'] ?? '',
  ]);
  if (!isset($grid[$key])) {
    $grid[$key] = [
      'topico' => $ev['topico'],
      'unidade' => $ev['unidade'] ?? '',
      'atividade' => $ev['atividade'],
      'responsavel' => $ev['responsavel'] ?? '',
      'modelo' => $ev['modelo'] ?? '',
      'meses' => array_fill(1, 12, null),
    ];
  }
  $month = (int)date('n', strtotime($ev['data']));
  $grid[$key]['meses'][$month] = $ev;
}

$linhaEncontrada = false;
foreach ($grid as $row) {
  foreach ($row['meses'] as $m) {
    if ($m && (int)$m['id'] === $eventoId) {
      $linhaEncontrada = true;
      break 2;
    }
  }
}
assert_true($linhaEncontrada, 'Evento aparece na estrutura de grid mensal');

echo "All Cronograma eventos tests passed.\n";

