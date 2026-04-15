<?php
require __DIR__ . '/../autoload.php';

$item = [
    'id' => 123,
    'created_at' => '2026-04-15 10:00:00',
    'cliente_id' => 0,
    'cliente_nome' => null,
    'empresa_nome' => 'Empresa Teste',
    'nome' => 'Cliente Teste',
    'whatsapp' => '11999999999',
    'email' => 'cliente@example.com',
    'numero_funcionarios' => 10,
    'numero_lideres' => 2,
    'faturamento_medio_anual' => 100000,
    'tomador_decisao' => 1,
    'nota_financeiro' => 1,
    'nota_mercado' => 2,
    'nota_pessoas' => 3,
    'nota_processo' => 4,
    'realidade_financeiro' => 10,
    'realidade_mercado' => 20,
    'realidade_pessoas' => 30,
    'realidade_processo' => 40,
    'respostas_json' => '{}',
];
$clientesAssociacao = [];

ob_start();
require __DIR__ . '/../views/avaliacoes/show.php';
$html = (string)ob_get_clean();

echo json_encode([
    'has_export_button' => str_contains($html, 'Exportar PDF'),
    'has_download_button' => str_contains($html, 'Baixar PDF'),
    'has_no_public_open_button' => !str_contains($html, 'Abrir Formulário Público'),
    'has_no_plano_acao_button' => !str_contains($html, 'Plano de Ação'),
], JSON_UNESCAPED_UNICODE);
