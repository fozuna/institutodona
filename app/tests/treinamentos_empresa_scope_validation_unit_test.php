<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\TreinamentosController;
use App\Database\MigrationRunner;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;
use App\Models\FuncaoModel;

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
$deps = new DepartamentoModel();
$setores = new SetorModel();
$funcoes = new FuncaoModel();

$makeCnpj = static function (): string {
    $digits = '';
    for ($i = 0; $i < 14; $i++) {
        $digits .= (string)random_int(0, 9);
    }
    return $digits;
};

$c1 = $clientes->create(['nome_empresa' => 'Empresa Escopo A ' . uniqid(), 'CNPJ' => $makeCnpj(), 'contato' => 'Contato']);
$c2 = $clientes->create(['nome_empresa' => 'Empresa Escopo B ' . uniqid(), 'CNPJ' => $makeCnpj(), 'contato' => 'Contato']);
assert_true($c1 > 0 && $c2 > 0, 'Criou duas empresas para validar escopo');

$d1 = $deps->create(['nome' => 'Depto A', 'cliente_id' => $c1]);
$d1b = $deps->create(['nome' => 'Depto A2', 'cliente_id' => $c1]);
$d2 = $deps->create(['nome' => 'Depto B', 'cliente_id' => $c2]);
assert_true($d1 > 0 && $d1b > 0 && $d2 > 0, 'Criou departamentos');

$s1 = $setores->create(['nome' => 'Setor A', 'departamento_id' => $d1]);
$s1b = $setores->create(['nome' => 'Setor A2', 'departamento_id' => $d1b]);
$s2 = $setores->create(['nome' => 'Setor B', 'departamento_id' => $d2]);
assert_true($s1 > 0 && $s1b > 0 && $s2 > 0, 'Criou setores');

$f1 = $funcoes->create(['nome' => 'Função A', 'setor_id' => $s1]);
$f1b = $funcoes->create(['nome' => 'Função A2', 'setor_id' => $s1b]);
$f2 = $funcoes->create(['nome' => 'Função B', 'setor_id' => $s2]);
assert_true($f1 > 0 && $f1b > 0 && $f2 > 0, 'Criou funções');

$controller = new TreinamentosController();

$payload = [
    'nome' => 'Treinamento X',
    'objetivo' => '',
    'publico' => '',
    'carga_horaria' => '',
    'cliente_id' => $c1,
    'departamento_id' => $d1,
    'periodicidade' => 'avulso',
    'fornecedor' => '',
    'tipo_treinamento' => '',
    'template_certificado' => '',
    'assinatura_responsavel' => 'Responsável',
    'setor_ids' => [$s2],
    'funcao_ids' => [$f1],
];

$errors = invoke_private($controller, 'validatePayload', [$payload]);
assert_true(isset($errors['setor_ids']), 'Bloqueia setor de outra empresa');
assert_true(!isset($errors['funcao_ids']), 'Permite função da empresa correta quando válida');

$payload['setor_ids'] = [$s1];
$payload['funcao_ids'] = [$f2];
$errors = invoke_private($controller, 'validatePayload', [$payload]);
assert_true(isset($errors['funcao_ids']), 'Bloqueia função de outra empresa');

$payload['funcao_ids'] = [$f1];
$errors = invoke_private($controller, 'validatePayload', [$payload]);
assert_true(empty($errors), 'Aceita payload com empresa/departamento/setores/funções consistentes');

// Isolamento é por EMPRESA, não por departamento único: setores/funções de um segundo
// departamento da MESMA empresa (via "Departamentos (Filtro)" no formulário) devem ser
// aceitos mesmo quando o departamento_id principal do treinamento é outro.
$payload['departamento_id'] = $d1;
$payload['setor_ids'] = [$s1, $s1b];
$payload['funcao_ids'] = [$f1, $f1b];
$errors = invoke_private($controller, 'validatePayload', [$payload]);
assert_true(empty($errors), 'Aceita setores/funções de outro departamento da mesma empresa');

echo "treinamentos_empresa_scope_validation_unit_test passed.\n";

