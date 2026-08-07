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

$pageTitle = 'Editar Cliente';
$item = [
    'id' => 1,
    'nome_empresa' => 'Cliente Teste',
    'CNPJ' => '00.000.000/0001-00',
    'contato' => 'Contato',
    'logo_path' => null,
    'is_matriz' => 1,
    'matriz_id' => null,
    'ativo' => 1,
];

ob_start();
require __DIR__ . '/../views/clientes/edit.php';
$html = (string)ob_get_clean();

assert_true(strpos($html, 'name="ativo"') !== false, 'Campo de status (select ativo) presente na edição');
assert_true(strpos($html, 'data-initial="1"') !== false, 'Status inicial refletido no atributo data-initial');
assert_true(strpos($html, 'name="status_reason"') !== false, 'Campo de justificativa presente');
assert_true(strpos($html, "ativo.addEventListener('change'") !== false, 'JS de alternância de justificativa presente');
assert_true(strpos($html, 'name="csrf"') !== false && strpos($html, Security::csrfToken()) !== false, 'CSRF incluído no formulário');

$pageTitle = 'Clientes';
$items = [
    ['id' => 1, 'nome_empresa' => 'Empresa Ativa', 'CNPJ' => '11.111.111/0001-11', 'contato' => 'A', 'ativo' => 1],
    ['id' => 2, 'nome_empresa' => 'Empresa Inativa', 'CNPJ' => '22.222.222/0001-22', 'contato' => 'B', 'ativo' => 0],
];

ob_start();
require __DIR__ . '/../views/clientes/index.php';
$htmlIndex = (string)ob_get_clean();

assert_true(strpos($htmlIndex, '>Ativo<') !== false, 'Badge "Ativo" exibido na listagem');
assert_true(strpos($htmlIndex, '>Inativo<') !== false, 'Badge "Inativo" exibido na listagem');

echo "Clientes status UI smoke tests passed.\n";
