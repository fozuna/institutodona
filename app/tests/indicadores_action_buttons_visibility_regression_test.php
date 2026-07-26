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

$t = static fn(string $key, array $replace = []): string => $key;
$formatValue = static fn($value, array $item): string => (string)$value;
$items = [];

function render_indicadores_index(int $cliente, array $clientes): string
{
    global $t, $formatValue, $items;
    ob_start();
    $viewMode = 'cards';
    $q = '';
    $dateStart = '';
    $dateEnd = '';
    require __DIR__ . '/../views/indicadores/index.php';
    return (string)ob_get_clean();
}

$htmlNoCliente = render_indicadores_index(0, []);
assert_true(strpos($htmlNoCliente, 'route=indicadores/realizado') !== false, 'Sem cliente: botão "Lançar valor" aparece no primeiro acesso');
assert_true(strpos($htmlNoCliente, 'route=indicadores/painel') !== false, 'Sem cliente: botão "Painel" aparece no primeiro acesso');
assert_true(strpos($htmlNoCliente, 'route=indicadores/charts') !== false, 'Sem cliente: botão "Gráficos" aparece no primeiro acesso');
assert_true(strpos($htmlNoCliente, 'indicadores/realizado&cliente=') === false, 'Sem cliente: link "Lançar valor" não envia cliente=0');

$htmlComCliente = render_indicadores_index(42, [['id' => 42, 'nome_empresa' => 'Cliente Teste']]);
assert_true(strpos($htmlComCliente, 'indicadores/realizado&cliente=42') !== false, 'Com cliente: link "Lançar valor" inclui cliente=42');
assert_true(strpos($htmlComCliente, 'indicadores/painel&cliente=42') !== false, 'Com cliente: link "Painel" inclui cliente=42');
assert_true(strpos($htmlComCliente, 'indicadores/charts&cliente=42') !== false, 'Com cliente: link "Gráficos" inclui cliente=42');

echo "Indicadores action buttons visibility regression tests passed.\n";
