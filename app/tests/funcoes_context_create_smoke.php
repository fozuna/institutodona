<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

$cliente = 1;
$selectedSetorId = 10;
$mapDepartamentos = [5 => 'Departamento X'];
$setores = [
    ['id' => 10, 'nome' => 'Setor Y', 'departamento_id' => 5, 'departamento' => 'Departamento X'],
];

ob_start();
require __DIR__ . '/../views/funcoes/create.php';
$html = (string)ob_get_clean();

foreach ([
    'Contexto organizacional',
    'Departamento vinculado',
    'Setor vinculado',
    'data-setor-select',
    'data-departamento-display',
    'data-setor-display',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('View de criação de função deveria conter: ' . $needle);
    }
}
ok('Contexto organizacional exibido no cadastro de função');
echo "funcoes_context_create_smoke passed.\n";

