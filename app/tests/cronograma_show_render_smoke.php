<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\CronogramaTrafficLight;

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$crono = [
    'id' => 1,
    'id_cliente' => 1,
    'cliente' => 'Cliente Render',
    'ano' => 2026,
    'nome' => 'Cronograma Render',
];
$periodicidades = [
    'unico' => 'Unico (sem repeticao)',
    'mensal' => 'Mensal',
];
$statusFilter = 'todos';
$pilares = [];
$flashSuccess = null;
$flashError = null;
$grid = [
    [
        'serie_id' => 10,
        'topico' => 'Pilar Render',
        'unidade' => 'Departamento Render',
        'atividade' => 'Atividade Render',
        'responsavel' => 'Responsavel Render',
        'status' => 'Pendente',
        'traffic' => CronogramaTrafficLight::series([
            1 => ['events' => [['status' => 'Planejado', 'data' => '2026-01-15']]],
            2 => ['events' => [['status' => 'Planejado', 'data' => '2026-02-20']]],
        ], new DateTimeImmutable('2026-01-01')),
        'periodicidade' => 'mensal',
        'meses' => array_replace(array_fill(1, 12, ['marked' => false, 'count' => 0, 'events' => []]), [
            1 => ['marked' => true, 'count' => 1, 'events' => [['data' => '2026-01-15', 'status' => 'Planejado']], 'traffic' => CronogramaTrafficLight::monthCell([['data' => '2026-01-15', 'status' => 'Planejado']], new DateTimeImmutable('2026-01-01'))],
            2 => ['marked' => true, 'count' => 2, 'events' => [['data' => '2026-02-15', 'status' => 'Planejado'], ['data' => '2026-02-20', 'status' => 'Planejado']], 'traffic' => CronogramaTrafficLight::monthCell([['data' => '2026-02-15', 'status' => 'Planejado'], ['data' => '2026-02-20', 'status' => 'Planejado']], new DateTimeImmutable('2026-01-01'))],
        ]),
    ],
];
$events = [
    [
        'id' => 10,
        'id_cronograma' => 1,
        'evento_pai_id' => null,
        'serie_id' => 10,
        'data' => '2026-01-15',
        'periodicidade' => 'mensal',
        'tipo_evento' => 'Reunião',
        'topico' => 'Pilar Render',
        'unidade' => 'Departamento Render',
        'atividade' => 'Atividade Render',
        'responsavel' => 'Responsavel Render',
        'modelo' => 'Online',
        'status' => 'Planejado',
        'traffic' => CronogramaTrafficLight::occurrence([
            'status' => 'Planejado',
            'data' => '2026-01-15',
        ], new DateTimeImmutable('2026-01-01')),
    ],
    [
        'id' => 11,
        'id_cronograma' => 1,
        'evento_pai_id' => null,
        'serie_id' => 11,
        'data' => '2026-02-20',
        'periodicidade' => 'unico',
        'tipo_evento' => 'Tarefa',
        'topico' => 'Pilar Render',
        'unidade' => 'Departamento Render',
        'atividade' => 'Atividade Render 2',
        'responsavel' => 'Responsavel Render',
        'modelo' => 'Presencial',
        'status' => 'Planejado',
        'traffic' => CronogramaTrafficLight::occurrence([
            'status' => 'Planejado',
            'data' => '2026-02-20',
        ], new DateTimeImmutable('2026-01-01')),
    ],
];

ob_start();
require __DIR__ . '/../views/cronograma/show.php';
$html = (string)ob_get_clean();

foreach ([
    'Grade anual estilo planilha',
    'Pilar',
    'Departamento',
    'JAN',
    'DEZ',
    'Filtro do farol',
    'Apenas finalizados',
    'Apenas pendentes',
    'Tipo',
    'Editar',
    'Encerrar',
    'Documentos',
    'Editar série',
    'Ocorrências materializadas',
    'cronogramaDrawer',
    'cronogramaDrawerBackdrop',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('View do cronograma deveria conter: ' . $needle);
    }
}
ok('Render da grade anual e acoes de serie');

echo "Cronograma show render smoke passed.\n";
