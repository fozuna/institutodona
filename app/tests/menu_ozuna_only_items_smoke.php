<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

$path = __DIR__ . '/../views/layouts/main.php';
$content = @file_get_contents($path);
if ($content === false || $content === '') {
    failFast('Nao foi possivel ler views/layouts/main.php');
}

foreach ([
    '$showOzunaOnlyMenu',
    '$ozunaOnlyAttr',
    'route=reunioes/index',
    'route=coaching/index',
    'route=processos/index',
] as $needle) {
    if (!str_contains($content, $needle)) {
        failFast('Layout principal deveria conter: ' . $needle);
    }
}

if (substr_count($content, '<?= $ozunaOnlyAttr ?>') < 3) {
    failFast('Ozuna-only deveria ser aplicado aos 3 itens de menu.');
}

ok('Itens Reuniões/Coaching/Processos estão condicionados a Ozuna-only no layout');
echo "menu_ozuna_only_items_smoke passed.\n";

