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

if (str_contains($content, '>Manual</span>') || str_contains($content, '>Sobre</span>')) {
    failFast('Menu lateral não deve exibir itens separados "Manual" e "Sobre" após unificação.');
}
if (!str_contains($content, 'Sobre e Manual de Uso')) {
    failFast('Menu lateral deve exibir o item "Sobre e Manual de Uso".');
}
if (!str_contains($content, 'index.php?route=about/index')) {
    failFast('Menu lateral deve apontar para route=about/index.');
}
if (str_contains($content, 'route=about/index&tab=')) {
    failFast('Menu lateral não deve depender do parâmetro tab para a página unificada.');
}

ok('Menu lateral unificado em "Sobre e Manual de Uso" sem itens redundantes');
echo "menu_ozuna_only_items_smoke passed.\n";
