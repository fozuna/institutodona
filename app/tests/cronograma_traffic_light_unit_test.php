<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\CronogramaTrafficLight;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$today = new DateTimeImmutable('2026-04-15');

$realizado = CronogramaTrafficLight::occurrence([
    'status' => 'Finalizado',
    'data' => '2026-04-10',
], $today);
if (($realizado['key'] ?? '') !== 'finalizado') {
    failFast('Evento finalizado deveria ser verde');
}
ok('Evento finalizado mapeado para verde');

$pendente = CronogramaTrafficLight::occurrence([
    'status' => 'Planejado',
    'data' => '2026-04-20',
], $today);
if (($pendente['key'] ?? '') !== 'pendente') {
    failFast('Evento futuro pendente deveria ser amarelo');
}
ok('Evento futuro pendente mapeado para amarelo');

$atrasado = CronogramaTrafficLight::occurrence([
    'status' => 'Planejado',
    'data' => '2026-04-01',
], $today);
if (($atrasado['key'] ?? '') !== 'atrasado') {
    failFast('Evento vencido deveria ser vermelho');
}
ok('Evento atrasado mapeado para vermelho');

$series = CronogramaTrafficLight::series([
    4 => ['events' => [
        ['status' => 'Planejado', 'data' => '2026-04-01'],
    ]],
    5 => ['events' => [
        ['status' => 'Planejado', 'data' => '2026-05-20'],
    ]],
], $today);
if (($series['key'] ?? '') !== 'atrasado') {
    failFast('Serie com pendencia vencida deveria ser vermelha');
}
ok('Serie com ocorrencia vencida herda status vermelho');

$month = CronogramaTrafficLight::monthCell([
    ['status' => 'Finalizado', 'data' => '2026-04-10'],
    ['status' => 'Planejado', 'data' => '2026-04-25'],
], $today);
if (($month['key'] ?? '') !== 'pendente') {
    failFast('Mes com evento futuro pendente deveria manter estado amarelo');
}
ok('Mes com multiplas ocorrencias retorna um unico estado visual');

if (CronogramaTrafficLight::normalizeFilter('invalido') !== 'todos') {
    failFast('Filtro inválido deveria cair para todos');
}
ok('Normalizacao de filtro do farol');

echo "Cronograma traffic light unit test passed.\n";
