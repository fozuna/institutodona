<?php
require __DIR__ . '/../autoload.php';

use App\Core\Security;

$_SESSION['user'] = [
    'id' => 1,
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';

$clientes = [];
Security::csrfToken();

$render = static function (array $items) use ($clientes): string {
    ob_start();
    include __DIR__ . '/../views/avaliacoes/index.php';
    return (string)ob_get_clean();
};

$itemsWithPotential = [
    [
        'id' => 101,
        'cliente_id' => null,
        'empresa_nome' => 'Lead Alfa',
        'cliente_nome' => null,
        'cliente_associado_em' => null,
        'created_at' => '2026-04-08 10:00:00',
        'publico_status' => null,
        'publico_token' => null,
        'publico_expiracao' => null,
        'publico_data_envio' => null,
        'publico_data_conclusao' => null,
        'publico_nome' => null,
        'nota_financeiro' => 0,
        'nota_mercado' => 0,
        'nota_pessoas' => 0,
        'nota_processo' => 0,
    ],
    [
        'id' => 202,
        'cliente_id' => 5,
        'empresa_nome' => 'Cliente Beta',
        'cliente_nome' => 'Cliente Beta',
        'cliente_associado_em' => '2026-04-07 09:00:00',
        'created_at' => '2026-04-07 09:00:00',
        'publico_status' => 'pendente',
        'publico_token' => '11111111-1111-4111-8111-111111111111',
        'publico_expiracao' => null,
        'publico_data_envio' => '2026-04-07 09:10:00',
        'publico_data_conclusao' => null,
        'publico_nome' => null,
        'nota_financeiro' => 1,
        'nota_mercado' => 1,
        'nota_pessoas' => 1,
        'nota_processo' => 1,
    ],
];

$htmlWithItems = $render($itemsWithPotential);
$htmlWithoutItems = $render([]);

$buttonEnabledWithItems = preg_match('/id="btn-gerar-link-publico"[^>]*>/m', $htmlWithItems, $buttonMatch)
    && preg_match('/\sdisabled(\s|>|=)/', $buttonMatch[0]) !== 1;
$firstSelected = strpos($htmlWithItems, 'id="avaliacao-publica-id" value="101"') !== false;
$potentialDoesNotBlock = strpos($htmlWithItems, 'Potencial cliente') !== false && $buttonEnabledWithItems;
$buttonEnabledWithoutItems = preg_match('/id="btn-gerar-link-publico"[^>]*>/m', $htmlWithoutItems, $buttonEmptyMatch)
    && preg_match('/\sdisabled(\s|>|=)/', $buttonEmptyMatch[0]) !== 1;
$emptyStateAllowsBlankId = strpos($htmlWithoutItems, 'id="avaliacao-publica-id" value=""') !== false;
$copyFallbackPresent = strpos($htmlWithItems, 'copyTextRobust') !== false && strpos($htmlWithItems, 'fallbackCopyText') !== false;

echo json_encode([
    'button_enabled_with_items' => $buttonEnabledWithItems,
    'first_item_preselected' => $firstSelected,
    'potential_cliente_does_not_block' => $potentialDoesNotBlock,
    'button_enabled_without_items' => $buttonEnabledWithoutItems,
    'empty_state_allows_blank_id' => $emptyStateAllowsBlankId,
    'copy_fallback_present' => $copyFallbackPresent,
], JSON_UNESCAPED_UNICODE);
