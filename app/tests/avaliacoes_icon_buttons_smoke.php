<?php
require_once __DIR__ . '/../autoload.php';

function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }
function ok(string $msg): void { echo "OK: {$msg}\n"; }

$path = __DIR__ . '/../views/avaliacoes/index.php';
$content = @file_get_contents($path);
if ($content === false || $content === '') {
    failFast('Nao foi possivel ler avaliacoes/index.php');
}

foreach ([
    'icon-btn',
    'data-feather="plus"',
    'data-feather="external-link"',
    'data-feather="copy"',
    'aria-label=',
    'title="',
] as $needle) {
    if (!str_contains($content, $needle)) {
        failFast('Tela de avaliacoes deveria conter: ' . $needle);
    }
}

ok('Tela de avaliacoes usa botoes com icones + tooltips + aria-label');
echo "avaliacoes_icon_buttons_smoke passed.\n";

