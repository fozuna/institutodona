<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

$appJs = __DIR__ . '/../../public_html/assets/js/app.js';
$router = __DIR__ . '/../../public_html/index.php';

$js = @file_get_contents($appJs);
if ($js === false || $js === '') {
    failFast('Nao foi possivel ler assets/js/app.js');
}

if (!str_contains($js, "return '';")) {
    failFast("app.js deveria retornar string vazia quando nao houver mapeamento de icone");
}
if (!str_contains($js, "txt.indexOf('ajuda')")) {
    failFast("app.js deveria mapear ajuda/help explicitamente (help-circle)");
}
foreach ([
    'data-icon-only-ignore',
    'feather_missing',
    'feather_replace_failed',
] as $needle) {
    if (!str_contains($js, $needle)) {
        failFast('app.js deveria conter: ' . $needle);
    }
}
ok('app.js possui guardas e monitoramento minimo para falhas de icone');

$rt = @file_get_contents($router);
if ($rt === false || $rt === '') {
    failFast('Nao foi possivel ler public_html/index.php');
}
if (!str_contains($rt, "case 'logs/icon_health':")) {
    failFast("Router deveria registrar rota logs/icon_health");
}
ok('Rota logs/icon_health registrada');

echo "icons_regression_smoke passed.\n";
