<?php
require_once __DIR__ . '/../autoload.php';

function ok(string $msg): void { echo "OK: {$msg}\n"; }
function failFast(string $msg): void { echo "FAIL: {$msg}\n"; exit(1); }

$values = [
    'nome' => 'Treinamento X',
    'objetivo' => '',
    'publico' => '',
    'carga_horaria' => '',
    'cliente_id' => 1,
    'departamento_id' => 1,
    'periodicidade' => 'avulso',
    'fornecedor' => '',
    'tipo_treinamento' => '',
    'template_certificado' => '',
    'assinatura_responsavel' => 'Responsável',
    'setor_ids' => [10],
    'funcao_ids' => [20],
];
$errors = [];
$clientes = [
    ['id' => 1, 'nome_empresa' => 'Empresa A'],
    ['id' => 2, 'nome_empresa' => 'Empresa B'],
];
$departamentos = [
    ['id' => 1, 'nome' => 'Depto A', 'nome_empresa' => 'Empresa A', 'cliente_id' => 1],
    ['id' => 2, 'nome' => 'Depto B', 'nome_empresa' => 'Empresa B', 'cliente_id' => 2],
];
$setores = [
    ['id' => 10, 'nome' => 'Setor A', 'departamento_nome' => 'Depto A', 'cliente_id' => 1],
    ['id' => 11, 'nome' => 'Setor B', 'departamento_nome' => 'Depto B', 'cliente_id' => 2],
];
$funcoes = [
    ['id' => 20, 'nome' => 'Função A', 'setor_nome' => 'Setor A', 'cliente_id' => 1],
    ['id' => 21, 'nome' => 'Função B', 'setor_nome' => 'Setor B', 'cliente_id' => 2],
];
$periodicidades = ['avulso' => 'Avulso'];

ob_start();
require __DIR__ . '/../views/treinamentos/form_fields.php';
$html = (string)ob_get_clean();

foreach ([
    'Empresa',
    'name="cliente_id"',
    'data-cliente-id="1"',
    'id="treinamentosClienteId"',
    'id="treinamentosDepartamentoId"',
    'id="treinamentosSetores"',
    'id="treinamentosFuncoes"',
] as $needle) {
    if (!str_contains($html, $needle)) {
        failFast('Form de treinamentos deveria conter: ' . $needle);
    }
}
ok('Form de treinamentos inclui filtro por empresa e data attributes para escopo');
echo "treinamentos_filter_empresa_smoke passed.\n";

