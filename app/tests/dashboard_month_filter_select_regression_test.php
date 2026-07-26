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

function render_dashboard_kanban(string $monthStart, string $monthEnd): string
{
    $clientes = [];
    $filters = ['month_start' => $monthStart, 'month_end' => $monthEnd, 'cliente_ids' => []];
    $kanbanData = [];
    $stats = [];
    $totalsByStatus = [];
    ob_start();
    require __DIR__ . '/../views/dashboard/kanban.php';
    return (string)ob_get_clean();
}

$html = render_dashboard_kanban('2026-07', '2026-09');

assert_true(strpos($html, 'type="month"') === false, 'Não usa mais <input type="month"> nativo (fonte de inconsistência entre navegadores)');
assert_true(strpos($html, 'id="dashboardMonthStartMonth"') !== false, 'Select de mês inicial presente');
assert_true(strpos($html, 'id="dashboardMonthStartYear"') !== false, 'Select de ano inicial presente');
assert_true(strpos($html, 'id="dashboardMonthEndMonth"') !== false, 'Select de mês final presente');
assert_true(strpos($html, 'id="dashboardMonthEndYear"') !== false, 'Select de ano final presente');
assert_true(strpos($html, '<input type="hidden" id="dashboardMonthStart" name="month_start" value="2026-07">') !== false, 'Campo oculto month_start preserva o valor combinado ano-mês');
assert_true(strpos($html, '<input type="hidden" id="dashboardMonthEnd" name="month_end" value="2026-09">') !== false, 'Campo oculto month_end preserva o valor combinado ano-mês');
assert_true(strpos($html, '>Julho<') !== false, 'Nomes de mês são exibidos em português');
assert_true(strpos($html, 'value="07" selected') !== false, 'Mês inicial vem pré-selecionado a partir do filtro atual');
assert_true(strpos($html, 'value="09" selected') !== false, 'Mês final vem pré-selecionado a partir do filtro atual');
assert_true(strpos($html, 'value="2026" selected') !== false, 'Ano vem pré-selecionado a partir do filtro atual');
assert_true(strpos($html, 'function syncMonthField(') !== false, 'JS sincroniza os selects com o campo oculto enviado ao backend');

$htmlEmpty = render_dashboard_kanban('', '');
assert_true(strpos($htmlEmpty, 'id="dashboardMonthStartMonth"') !== false, 'Sem filtro salvo, ainda renderiza os selects com o mês corrente como padrão');

echo "Dashboard month filter select regression tests passed.\n";
