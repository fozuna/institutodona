<?php
require_once __DIR__ . '/../autoload.php';

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

$formData = [
    'cliente_id' => 10,
    'cliente_nome' => 'Cliente Exemplo',
    'indicador' => 'Indicador Exemplo',
    'departamento_id' => 1,
    'setor_id' => 2,
    'responsavel_ids' => [],
    'periodicidade_tipo' => 'mensal',
    'data_inicial' => '2026-06-01',
    'data_final' => '2026-12-31',
    'valor' => '10',
    'tipo_meta' => 'minimo',
    'unidade_medida_id' => 3,
    'valor_minimo' => '0',
    'valor_maximo' => '100',
];
$errors = [];
$departamentos = [['id' => 1, 'nome' => 'Departamento X']];
$setores = [['id' => 2, 'nome' => 'Setor Y']];
$unidades = [['id' => 3, 'nome' => 'Percentual', 'simbolo' => '%', 'tipo' => 'percentual']];
$responsaveisSelecionados = [];
$periodicidades = ['mensal' => 'Mensal'];
$metaTipos = [
    'minimo' => 'Mínimo a ser atingido',
    'maximo' => 'Teto máximo permitido',
];
$submitRoute = 'indicadores/store';
$title = 'Novo indicador';
$backUrl = 'index.php?route=indicadores/index';
$submitLabel = 'Salvar';
$item = null;

ob_start();
require __DIR__ . '/../views/indicadores/_form.php';
$html = (string)ob_get_clean();

assert_true(strpos($html, 'name="tipo_meta"') !== false, 'Formulário renderiza o select de tipo de meta');
assert_true(strpos($html, 'Mínimo a ser atingido') !== false, 'Formulário exibe a opção de mínimo');
assert_true(strpos($html, 'Teto máximo permitido') !== false, 'Formulário exibe a opção de teto máximo');
assert_true(strpos($html, 'grid grid-cols-1 md:grid-cols-2 gap-5') !== false, 'Formulário mantém grid responsivo do módulo');
assert_true(strpos($html, 'border rounded-lg p-3 w-full') !== false, 'Novo campo reutiliza a mesma linguagem visual dos demais inputs');

echo "Indicadores form tipo_meta smoke tests passed.\n";
