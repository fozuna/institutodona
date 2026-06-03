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

$items = [[
    'id' => 123,
    'indicador' => 'Indicador Teste',
    'cliente_nome' => 'Cliente X',
    'departamento_nome' => 'Depto Y',
    'setor_nome' => 'Setor Z',
    'control_status_class' => 'bg-green-100 text-green-700',
    'control_status_label' => 'OK',
    'valor' => 10.5,
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-01-01',
    'data_final' => '2026-12-31',
    'unidade_nome' => 'Unidade',
    'unidade_tipo' => 'decimal',
    'unidade_simbolo' => '',
    'valor_minimo' => 0,
    'valor_maximo' => 100,
    'responsaveis' => ['Fulano', 'Ciclana'],
]];

$t = static fn(string $key, array $replace = []): string => $key;
$formatValue = static fn($value, array $item): string => (string)$value;

ob_start();
$viewMode = 'cards';
require __DIR__ . '/../views/indicadores/_cards.php';
$htmlCards = (string)ob_get_clean();
assert_true(strpos($htmlCards, 'data-indicadores-view="cards"') !== false, 'Renderiza cards com marcador de modo');
assert_true(strpos($htmlCards, 'data-indicador-id="123"') !== false, 'Cards contém item com data-indicador-id');
assert_true(strpos($htmlCards, 'data-indicador-edit') !== false, 'Cards contém ação de editar valor');

ob_start();
$viewMode = 'list';
require __DIR__ . '/../views/indicadores/_cards.php';
$htmlList = (string)ob_get_clean();
assert_true(strpos($htmlList, 'data-indicadores-view="list"') !== false, 'Renderiza listagem com marcador de modo');
assert_true(strpos($htmlList, 'data-indicador-id="123"') !== false, 'Listagem contém item com data-indicador-id');
assert_true(strpos($htmlList, 'data-indicador-save') !== false, 'Listagem contém ação de salvar valor');

ob_start();
$clientes = [];
$cliente = 0;
$q = '';
$dateStart = '';
$dateEnd = '';
$viewMode = 'list';
require __DIR__ . '/../views/indicadores/index.php';
$htmlIndex = (string)ob_get_clean();
assert_true(strpos($htmlIndex, 'id="indicadoresViewToggle"') !== false, 'Index contém botão de alternância');
assert_true(strpos($htmlIndex, 'name="view"') !== false, 'Index envia parâmetro de modo de visualização');

echo "Indicadores view toggle template smoke tests passed.\n";

