<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

$jsPath = __DIR__ . '/../../public_html/assets/js/app.js';
$js = @file_get_contents($jsPath);
if ($js === false || $js === '') {
    failFast('Nao foi possivel ler app.js');
}

foreach ([
    "var stackKey = 'nav:stack:v1'",
    "var restoreParam = '__nav_restore'",
    "var restoreIdParam = '__nav_restore_id'",
    "installBackInterceptor",
    "window.AppNav = nav",
    "history.back()",
] as $needle) {
    if (!str_contains($js, $needle)) {
        failFast('Gerenciador de navegacao reversa deve conter: ' . $needle);
    }
}

ok('Gerenciador global de navegacao reversa presente no app.js');
echo "navigation_back_manager_smoke passed.\n";
