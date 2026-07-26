<?php
require_once __DIR__ . '/../autoload.php';

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$clientes = [];
$filters = ['month_start' => '2026-07', 'month_end' => '2026-07', 'cliente_ids' => []];
$kanbanData = [];
$stats = [];
$totalsByStatus = [];

ob_start();
require __DIR__ . '/../views/dashboard/kanban.php';
$html = (string)ob_get_clean();

assert_true(strpos($html, '<svg') === false, 'Dashboard não renderiza mais SVGs customizados hardcoded (biblioteca própria removida)');
assert_true(substr_count($html, 'data-feather=') >= 20, 'Dashboard usa Feather Icons (data-feather) em todos os ícones da tela, igual ao padrão do sistema');

$validFeatherNames = ['zap', 'calendar', 'briefcase', 'refresh-cw', 'filter', 'download', 'clock', 'book-open', 'check-circle', 'bar-chart-2', 'activity', 'award', 'columns', 'search', 'inbox'];
preg_match_all('/data-feather="([a-z0-9-]+)"/', $html, $matches);
$usedNames = array_unique($matches[1]);
foreach ($usedNames as $name) {
    assert_true(in_array($name, $validFeatherNames, true), "Ícone \"{$name}\" usado no Dashboard está no conjunto de nomes Feather mapeados");
}

assert_true(strpos($html, 'stroke-width="1.8"') === false, 'Não há mais espessura de traço customizada (1.8) divergente do padrão Feather (2) usado no resto do sistema');

preg_match_all('/<span data-feather="[a-z0-9-]+"([^>]*)><\/span>/', $html, $spanMatches);
assert_true(count($spanMatches[0]) > 0, 'Ícones são renderizados como <span data-feather> (mesma sintaxe da tela de Auditorias)');
foreach ($spanMatches[1] as $attrs) {
    assert_true(strpos($attrs, 'aria-hidden="true"') !== false, 'Ícone decorativo do Dashboard é marcado aria-hidden (evita duplicidade com o texto visível adjacente)');
}

echo "Dashboard icons Feather standardization regression tests passed.\n";
