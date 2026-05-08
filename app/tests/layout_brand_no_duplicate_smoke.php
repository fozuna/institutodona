<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\AppBrand;

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];
$_GET['route'] = 'dashboard/index';

$content = '';

ob_start();
require __DIR__ . '/../views/layouts/main.php';
$html = (string)ob_get_clean();

if (!preg_match('/<div class="sidebar-brand">([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>/m', $html, $m)) {
    failFast('Bloco sidebar-brand não encontrado no layout');
}
$brandBlock = $m[1];
$count = substr_count($brandBlock, AppBrand::displayName());
if ($count !== 1) {
    failFast('Nome do sistema deveria aparecer apenas uma vez no bloco de marca (atual=' . $count . ')');
}
ok('Nome do sistema não está duplicado no cabeçalho do menu lateral');
echo "layout_brand_no_duplicate_smoke passed.\n";

