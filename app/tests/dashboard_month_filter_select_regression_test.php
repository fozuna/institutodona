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
assert_true(strpos($html, '<select id="dashboardMonthStart" name="month_start"') !== false, 'Mês inicial é um único select combinado (mês + ano), consistente em qualquer navegador');
assert_true(strpos($html, '<select id="dashboardMonthEnd" name="month_end"') !== false, 'Mês final é um único select combinado (mês + ano)');
assert_true(strpos($html, '<option value="2026-07" selected>Julho de 2026</option>') !== false, 'Mês inicial vem pré-selecionado com o ano logo após o nome do mês');
assert_true(strpos($html, '<option value="2026-09" selected>Setembro de 2026</option>') !== false, 'Mês final vem pré-selecionado com o ano logo após o nome do mês');
assert_true(strpos($html, 'Julho de 2026') !== false, 'Nomes de mês são exibidos em português, com o ano junto');

$htmlEmpty = render_dashboard_kanban('', '');
assert_true(strpos($htmlEmpty, '<select id="dashboardMonthStart" name="month_start"') !== false, 'Sem filtro salvo, ainda renderiza o select combinado com o mês corrente como padrão');

echo "Dashboard month filter select regression tests passed.\n";
