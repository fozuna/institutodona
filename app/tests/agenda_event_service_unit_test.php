<?php
require_once __DIR__ . '/../autoload.php';

use App\Services\AgendaEventService;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$ctx = AgendaEventService::buildMonthContext(2026, 4);
if (($ctx['start'] ?? '') !== '2026-03-29' || ($ctx['end'] ?? '') !== '2026-05-09') {
    failFast('Contexto mensal deveria gerar intervalo visível de 42 dias');
}
if (count($ctx['days'] ?? []) !== 42) {
    failFast('Calendário deveria conter 42 células');
}
ok('Contexto mensal do calendário');

if (AgendaEventService::normalizeTypeFilter('auditoria') !== 'auditoria') {
    failFast('Filtro auditoria deveria ser mantido');
}
if (AgendaEventService::normalizeTypeFilter('treinamento') !== 'treinamento') {
    failFast('Filtro treinamento deveria ser mantido');
}
if (AgendaEventService::normalizeTypeFilter('cronograma') !== 'cronograma') {
    failFast('Filtro cronograma deveria ser mantido');
}
if (AgendaEventService::normalizeTypeFilter('invalido') !== 'all') {
    failFast('Filtro inválido deveria cair para all');
}
ok('Normalização de filtros');

$grouped = AgendaEventService::groupByDate([
    ['date' => '2026-04-19', 'title' => 'A'],
    ['date' => '2026-04-19', 'title' => 'B'],
    ['date' => '2026-04-20', 'title' => 'C'],
]);
if (count($grouped['2026-04-19'] ?? []) !== 2 || count($grouped['2026-04-20'] ?? []) !== 1) {
    failFast('Agrupamento por data deveria preservar os eventos do dia');
}
ok('Agrupamento por data');

echo "Agenda event service unit test passed.\n";
