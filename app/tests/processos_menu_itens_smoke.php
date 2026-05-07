<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: $msg\n"; }
function failFast(string $msg): void { echo "FAIL: $msg\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
];
$_GET['route'] = 'dashboard/index';
$content = '<div>Painel de teste</div>';

ob_start();
require __DIR__ . '/../views/layouts/main.php';
$html = (string)ob_get_clean();

foreach ([
    'Pilares',
    'Tarefas',
    'Reuniões',
    'Coaching',
    'Processos',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('Menu Processos deveria conter: ' . $needle);
    }
}

ok('Menu Processos renderiza itens de Pilares e novos CRUDes');
echo "processos_menu_itens_smoke passed.\n";

