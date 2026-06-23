<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\TreinamentosController;
use App\Database\MigrationRunner;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\FuncaoModel;
use App\Models\TreinamentoModel;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

function invoke_private($obj, $method, array $args = []) {
    $ref = new ReflectionClass($obj);
    $m = $ref->getMethod($method);
    $m->setAccessible(true);
    return $m->invokeArgs($obj, $args);
}

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

(new MigrationRunner())->applyAll();

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$funcoes = new FuncaoModel();
$treinamentos = new TreinamentoModel();
$controller = new TreinamentosController();

$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$suffix = uniqid('treinamento_filial_', true);
$matrizId = $clientes->create([
    'nome_empresa' => 'Matriz Treinamento ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Teste',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$filialId = $clientes->create([
    'nome_empresa' => 'Filial Treinamento ' . $suffix,
    'CNPJ' => $makeCnpj(),
    'contato' => 'Teste',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
assert_true($matrizId > 0 && $filialId > 0, 'Criou matriz e filial para o cenário de treinamento');

$departamentoId = $departamentos->create([
    'nome' => 'Departamento Compartilhado ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId, $filialId],
]);
$setorId = $setores->create([
    'nome' => 'Setor Compartilhado ' . $suffix,
    'departamento_id' => $departamentoId,
]);
$funcaoId = $funcoes->create([
    'nome' => 'Funcao Compartilhada ' . $suffix,
    'setor_id' => $setorId,
]);
assert_true($departamentoId > 0 && $setorId > 0 && $funcaoId > 0, 'Criou catálogo compartilhado para a filial');

$catalogoFilial = invoke_private($controller, 'catalogOptionsForCliente', [$filialId]);
$departamentoIds = array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $catalogoFilial['departamentos'] ?? []);
$setorIds = array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $catalogoFilial['setores'] ?? []);
$funcaoIds = array_map(static fn(array $row): int => (int)($row['id'] ?? 0), $catalogoFilial['funcoes'] ?? []);

assert_true(in_array($departamentoId, $departamentoIds, true), 'Filial recebe departamento compartilhado no catálogo de treinamentos');
assert_true(in_array($setorId, $setorIds, true), 'Filial recebe setor herdado do departamento compartilhado');
assert_true(in_array($funcaoId, $funcaoIds, true), 'Filial recebe função herdada do departamento compartilhado');

$payload = [
    'nome' => 'Treinamento Filial ' . $suffix,
    'objetivo' => 'Objetivo',
    'publico' => 'Equipe',
    'carga_horaria' => '8',
    'cliente_id' => $filialId,
    'departamento_id' => $departamentoId,
    'periodicidade' => 'avulso',
    'fornecedor' => 'Fornecedor',
    'tipo_treinamento' => 'Operacional',
    'template_certificado' => '',
    'assinatura_responsavel' => 'Responsável',
    'setor_ids' => [$setorId],
    'funcao_ids' => [$funcaoId],
];

$errors = invoke_private($controller, 'validatePayload', [$payload]);
assert_true(empty($errors), 'Validação aceita departamento compartilhado para a filial');

$treinamentoId = $treinamentos->create($payload);
assert_true($treinamentoId > 0, 'Cria treinamento para a filial usando departamento compartilhado');

$treinamento = $treinamentos->find($treinamentoId);
assert_true((int)($treinamento['cliente_id'] ?? 0) === $filialId, 'Treinamento persiste a unidade operacional selecionada');
assert_true((int)($treinamento['departamento_id'] ?? 0) === $departamentoId, 'Treinamento mantém o departamento compartilhado selecionado');
assert_true((string)($treinamento['unidade_nome'] ?? '') !== '', 'Treinamento retorna o nome da unidade operacional');

echo "treinamentos_departamentos_compartilhados_filial_test passed.\n";
