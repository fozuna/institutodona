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
putenv('PUBLIC_AVALIACOES_DEFAULT_EMPRESA=Empresa Pública Fixa');

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
        'publico_token' => null,
        'publico_expiracao' => null,
        'publico_data_envio' => null,
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

$fixedActionPresent = str_contains($htmlWithItems, 'Abrir Formulário Público');
$fixedLinkPresent = str_contains($htmlWithItems, '/public/avaliacoes.php');
$potentialDoesNotBlock = strpos($htmlWithItems, 'Potencial cliente') !== false && $fixedActionPresent;
$fixedActionWithoutItems = str_contains($htmlWithoutItems, 'Abrir Formulário Público');
$copyFallbackPresent = strpos($htmlWithItems, 'copyTextRobust') !== false && strpos($htmlWithItems, 'fallbackCopyText') !== false;

echo json_encode([
    'fixed_action_present' => $fixedActionPresent,
    'fixed_link_present' => $fixedLinkPresent,
    'potential_cliente_does_not_block' => $potentialDoesNotBlock,
    'fixed_action_present_without_items' => $fixedActionWithoutItems,
    'copy_fallback_present' => $copyFallbackPresent,
], JSON_UNESCAPED_UNICODE);
