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
    'data-app-shell',
    'data-sidebar-toggle',
    'data-sidebar-overlay',
    'aria-controls="appSidebar"',
    'aria-label="Recolher menu lateral"',
    'app-topbar',
    'sidebar-brand',
    'SIS+',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('Layout deveria conter: ' . $needle);
    }
}
$toggleCount = substr_count($html, 'data-sidebar-toggle');
if ($toggleCount !== 1) {
    failFast('Layout deveria renderizar apenas um toggle do menu lateral, mas encontrou: ' . $toggleCount);
}
ok('Layout renderiza um unico toggle do menu lateral');

$js = file_get_contents(__DIR__ . '/../../public_html/assets/js/app.js');
foreach ([
    "var sidebarStorageKey = 'sidebar:collapsed'",
    "localStorage.getItem(sidebarStorageKey)",
    "localStorage.setItem(sidebarStorageKey",
    "data-sidebar-open",
    "data-sidebar-collapsed",
] as $needle) {
    if ($js === false || !str_contains($js, $needle)) {
        failFast('Script deveria conter: ' . $needle);
    }
}
ok('Script persiste e sincroniza o estado do menu lateral');

echo "Layout sidebar toggle smoke passed.\n";

