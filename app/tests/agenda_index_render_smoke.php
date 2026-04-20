<?php
require_once __DIR__ . '/../autoload.php';

use App\Services\AgendaEventService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$calendar = AgendaEventService::buildMonthContext(2026, 4);
$events = [
    [
        'id' => 'plano-task-1',
        'source_id' => 1,
        'type' => 'planoacao',
        'subtype' => 'task',
        'type_label' => 'Plano de Acao',
        'date' => '2026-04-19',
        'time' => 'Dia todo',
        'time_sort' => '08:00',
        'title' => 'Plano Render',
        'status' => 'Planejado',
        'description' => 'Descricao do plano',
        'client' => 'Cliente Render',
        'link' => 'index.php?route=planoacao/show&id=1',
    ],
    [
        'id' => 'auditoria-1',
        'source_id' => 1,
        'type' => 'auditoria',
        'subtype' => 'auditoria',
        'type_label' => 'Auditoria',
        'date' => '2026-04-19',
        'time' => 'Dia todo',
        'time_sort' => '10:00',
        'title' => 'Auditoria Render',
        'status' => 'Agendada',
        'description' => 'Descricao da auditoria',
        'client' => 'Cliente Render',
        'link' => 'index.php?route=auditorias/show&id=1',
    ],
    [
        'id' => 'treinamento-agenda-1',
        'source_id' => 1,
        'type' => 'treinamento',
        'subtype' => 'agenda',
        'type_label' => 'Treinamento',
        'date' => '2026-04-19',
        'time' => '19/04/2026 09:00',
        'time_sort' => '09:00',
        'title' => 'Treinamento Render',
        'status' => '3/4 presentes',
        'description' => 'Descricao do treinamento',
        'client' => 'Cliente Render',
        'link' => 'index.php?route=treinamentos/presenca&agenda_id=1',
    ],
    [
        'id' => 'cronograma-evento-1',
        'source_id' => 1,
        'type' => 'cronograma',
        'subtype' => 'evento',
        'type_label' => 'Evento de Cronograma',
        'date' => '2026-04-19',
        'time' => 'Dia todo',
        'time_sort' => '07:30',
        'title' => 'Cronograma Render',
        'status' => 'Planejado',
        'description' => 'Descricao do cronograma',
        'client' => 'Cliente Render',
        'link' => 'index.php?route=cronograma/show&id=1',
    ],
];
$eventType = 'all';

ob_start();
require __DIR__ . '/../views/agenda/index.php';
$html = (string)ob_get_clean();

foreach ([
    'Agenda Integrada',
    'data-calendar-day="2026-04-19"',
    'agendaModalBackdrop',
    'agenda/api_events',
    'Planos de Acao',
    'Auditorias',
    'Treinamentos',
    'Cronograma',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('View da agenda deveria conter: ' . $needle);
    }
}
ok('Renderizacao da agenda com calendario, filtros e modal');

echo "Agenda index render smoke passed.\n";
