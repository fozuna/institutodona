<?php
require_once __DIR__ . '/../autoload.php';

use App\Core\Security;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Cliente Admin',
    'email' => 'cliente@example.com',
    'tipo_acesso' => 'cliente_admin',
    'allowed_client_ids' => [1],
];

$clientes = [];
$cliente = 1;
$indicadores = [];
$indicadorId = 0;
$periodoInicio = '2026-04-01';
$periodoFim = '2026-04-30';
$items = [[
    'id' => 10,
    'indicador' => 'Faturamento de clínica',
    'departamento_nome' => 'CLINICA',
    'setor_nome' => 'INDIVIDUAIS',
    'data_evento' => '2026-04-01',
    'periodo_inicio' => '2026-04-01',
    'periodo_fim' => '2026-04-30',
    'valor_meta' => 5250,
    'valor_atingido' => null,
    'percentual_cumprimento' => null,
    'meta_status_class' => 'bg-gray-100 text-gray-700',
    'meta_status_label' => 'Pendente',
    'unidade_tipo' => 'monetaria',
    'unidade_simbolo' => 'R$',
]];

ob_start();
require __DIR__ . '/../views/indicadores/realizado.php';
$html = (string)ob_get_clean();

assert_true(strpos($html, 'data-indicador-decimal') !== false, 'Campo de lançamento presente');
assert_true(strpos($html, 'pattern="^-?(?:\\\\d') === false, 'Não usa padrão com \\\\d (quebra validação)');
assert_true(strpos($html, 'pattern="^-?(?:\\d') !== false, 'Usa padrão com \\d (regex válido no browser)');
assert_true(strpos($html, 'name="csrf"') !== false && strpos($html, Security::csrfToken()) !== false, 'Inclui CSRF no formulário');

ob_start();
$evento = [
    'id' => 10,
    'cliente_id' => 1,
    'cliente_nome' => 'Cliente X',
    'indicador_id' => 99,
    'indicador' => 'Faturamento de clínica',
    'departamento_nome' => 'CLINICA',
    'setor_nome' => 'INDIVIDUAIS',
    'data_evento' => '2026-04-01',
    'periodo_inicio' => '2026-04-01',
    'periodo_fim' => '2026-04-30',
    'meta_status_class' => 'bg-gray-100 text-gray-700',
    'meta_status_label' => 'Pendente',
    'valor_meta' => 5250,
    'valor_atingido' => null,
    'percentual_cumprimento' => null,
    'unidade_tipo' => 'monetaria',
    'unidade_simbolo' => 'R$',
];
$summary = ['total' => 1, 'media_cumprimento' => 0, 'trend' => 'estavel'];
$history = [[
    'id' => 10,
    'data_evento' => '2026-04-01',
    'periodo_inicio' => '2026-04-01',
    'periodo_fim' => '2026-04-30',
    'valor_meta' => 5250,
    'valor_atingido' => null,
    'percentual_cumprimento' => null,
    'meta_status_class' => 'bg-gray-100 text-gray-700',
    'meta_status_label' => 'Pendente',
]];
require __DIR__ . '/../views/indicadores/evento.php';
$htmlEvento = (string)ob_get_clean();
assert_true(strpos($htmlEvento, 'pattern="^-?(?:\\\\d') === false, 'Evento não usa padrão com \\\\d');
assert_true(strpos($htmlEvento, 'pattern="^-?(?:\\d') !== false, 'Evento usa padrão com \\d');

echo "Indicadores realizado/evento input pattern smoke tests passed.\n";
