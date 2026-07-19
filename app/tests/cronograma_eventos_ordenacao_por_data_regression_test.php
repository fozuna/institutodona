<?php
require_once __DIR__ . '/../autoload.php';

use App\Models\ClienteModel;
use App\Models\CronogramaEventoModel;
use App\Models\CronogramaEventoTipoModel;
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
$tipos = new CronogramaEventoTipoModel();
$tipoEvento = 'Teste Ordenacao ' . uniqid();
$tipos->create($tipoEvento);

$clienteId = $clientes->create([
  'nome_empresa' => 'Teste Cronograma Ordenacao ' . uniqid(),
  'CNPJ' => '00.000.000/0001-00',
  'contato' => 'Teste',
]);
assert_true($clienteId > 0, 'Criou cliente para cronograma');

$cronogramaId = $cronogramas->create([
  'id_cliente' => $clienteId,
  'nome' => 'Cronograma Ordenacao',
  'ano' => 2026,
]);
assert_true($cronogramaId > 0, 'Criou cronograma');

// Série A é criada primeiro (ganha o menor id de série), mas com data mais tardia no ano.
// Série B é criada depois (id de série maior), mas com data mais cedo no ano.
// A ordenação antiga (por série) listaria A antes de B; a ordenação por data deve listar B antes de A.
$idSerieA = $eventos->create($cronogramaId, [
  'topico' => 'Serie A (id menor, data tardia)',
  'unidade' => 'Matriz',
  'atividade' => 'Atividade A',
  'responsavel' => 'Fulano',
  'periodicidade' => 'unico',
  'data' => '2026-11-01',
  'tipo_evento' => $tipoEvento,
]);
assert_true($idSerieA > 0, 'Criou série A (data tardia)');

$idSerieB = $eventos->create($cronogramaId, [
  'topico' => 'Serie B (id maior, data cedo)',
  'unidade' => 'Matriz',
  'atividade' => 'Atividade B',
  'responsavel' => 'Fulano',
  'periodicidade' => 'unico',
  'data' => '2026-02-01',
  'tipo_evento' => $tipoEvento,
]);
assert_true($idSerieB > 0, 'Criou série B (data cedo)');
assert_true($idSerieB > $idSerieA, 'Pré-condição do teste: série B tem id maior que série A');

$rows = $eventos->byCronograma($cronogramaId);
assert_true(count($rows) === 2, 'byCronograma retornou as duas ocorrências criadas');

// A causa raiz corrigida: o model ordenava por COALESCE(evento_pai_id, id) primeiro (id de série),
// não por data. Isso fazia a série A (id menor, data tardia) aparecer antes da série B
// (id maior, data cedo) — o oposto da ordem cronológica esperada.
assert_true((int)$rows[0]['id'] === $idSerieB, 'Primeira ocorrência retornada é a de data mais cedo (série B), não a de id menor (série A)');
assert_true((int)$rows[1]['id'] === $idSerieA, 'Segunda ocorrência retornada é a de data mais tardia (série A)');

$dates = array_column($rows, 'data');
$sortedDates = $dates;
sort($sortedDates);
assert_true($dates === $sortedDates, 'byCronograma() entrega os eventos já ordenados cronologicamente por data');

echo "Cronograma eventos ordenacao por data regression test passed.\n";
