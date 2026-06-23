<?php
require_once __DIR__ . '/../autoload.php';

use App\Controllers\FuncoesController;
use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;

function assert_true($condition, $message) {
    if (!$condition) {
        echo "FAIL: {$message}\n";
        exit(1);
    }
    echo "OK: {$message}\n";
}

class FuncoesControllerScopeProbe extends FuncoesController
{
    public array $renderArgs = [];

    protected function requireLogin(): void
    {
    }

    protected function render(string $view, array $params = []): void
    {
        $this->renderArgs = ['view' => $view, 'params' => $params];
    }
}

$pdo = Database::getConnection();
$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$suffix = uniqid('fun_scope_', true);

$cleanup = [
    'funcoes' => [],
    'setores' => [],
    'departamentos' => [],
    'clientes' => [],
];

register_shutdown_function(function () use ($pdo, &$cleanup) {
    try {
        foreach (array_reverse($cleanup['funcoes']) as $id) {
            $pdo->prepare('DELETE FROM funcoes WHERE id = :id')->execute(['id' => (int)$id]);
        }
        foreach (array_reverse($cleanup['setores']) as $id) {
            $pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => (int)$id]);
        }
        foreach (array_reverse($cleanup['departamentos']) as $id) {
            $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => (int)$id]);
        }
        foreach (array_reverse($cleanup['clientes']) as $id) {
            $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => (int)$id]);
        }
    } catch (\Throwable $e) {
    }
});

$matrizId = $clientes->create([
    'nome_empresa' => 'Matriz Funcoes ' . $suffix,
    'CNPJ' => '11.111.111/0001-11',
    'contato' => 'Teste',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
assert_true($matrizId > 0, 'Criou cliente matriz');
$cleanup['clientes'][] = $matrizId;

$filialId = $clientes->create([
    'nome_empresa' => 'Filial Funcoes ' . $suffix,
    'CNPJ' => '22.222.222/0001-22',
    'contato' => 'Teste',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
assert_true($filialId > 0, 'Criou cliente filial');
$cleanup['clientes'][] = $filialId;

$outroClienteId = $clientes->create([
    'nome_empresa' => 'Outro Cliente Funcoes ' . $suffix,
    'CNPJ' => '33.333.333/0001-33',
    'contato' => 'Teste',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
assert_true($outroClienteId > 0, 'Criou cliente externo');
$cleanup['clientes'][] = $outroClienteId;

$departamentoMatrizId = $departamentos->create([
    'nome' => 'Departamento Matriz ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId, $filialId],
]);
$departamentoOutroId = $departamentos->create([
    'nome' => 'Departamento Externo ' . $suffix,
    'cliente_id' => $outroClienteId,
]);
assert_true($departamentoMatrizId > 0 && $departamentoOutroId > 0, 'Criou departamentos por cliente');
$cleanup['departamentos'][] = $departamentoMatrizId;
$cleanup['departamentos'][] = $departamentoOutroId;

$setorMatrizId = $setores->create([
    'nome' => 'Setor Matriz ' . $suffix,
    'departamento_id' => $departamentoMatrizId,
]);
$setorOutroId = $setores->create([
    'nome' => 'Setor Externo ' . $suffix,
    'departamento_id' => $departamentoOutroId,
]);
assert_true($setorMatrizId > 0 && $setorOutroId > 0, 'Criou setores por cliente');
$cleanup['setores'][] = $setorMatrizId;
$cleanup['setores'][] = $setorOutroId;

$stmt = $pdo->prepare('INSERT INTO funcoes (nome, setor_id) VALUES (:nome, :setor_id)');
$stmt->execute(['nome' => 'Funcao Matriz ' . $suffix, 'setor_id' => $setorMatrizId]);
$funcaoMatrizId = (int)$pdo->lastInsertId();
$stmt->execute(['nome' => 'Funcao Externa ' . $suffix, 'setor_id' => $setorOutroId]);
$funcaoOutroId = (int)$pdo->lastInsertId();
assert_true($funcaoMatrizId > 0 && $funcaoOutroId > 0, 'Criou funções em clientes distintos');
$cleanup['funcoes'][] = $funcaoMatrizId;
$cleanup['funcoes'][] = $funcaoOutroId;

$_SESSION['user'] = [
    'id' => 99,
    'nome' => 'Cliente Admin',
    'email' => 'cliente-admin@example.com',
    'tipo_acesso' => 'cliente_admin',
    'allowed_client_ids' => [$filialId],
];

$_GET = ['route' => 'funcoes/index'];
$_POST = [];

$controller = new FuncoesControllerScopeProbe();
$controller->index();

$params = $controller->renderArgs['params'] ?? [];
$items = $params['items'] ?? [];
$departamentosList = $params['departamentos'] ?? [];
$setoresList = $params['setores'] ?? [];

assert_true(($params['cliente'] ?? 0) === $filialId, 'Resolve cliente autenticado quando a query não informa cliente');
assert_true(count($items) === 1, 'Lista apenas funções do cliente autenticado');
assert_true((int)($items[0]['id'] ?? 0) === $funcaoMatrizId, 'Função retornada pertence a departamento compartilhado com a filial autenticada');
assert_true(count($departamentosList) === 1 && (int)($departamentosList[0]['cliente_id'] ?? 0) === $matrizId, 'Departamentos respeitam compartilhamento seletivo por empresa');
assert_true(count($setoresList) === 1 && (int)($setoresList[0]['departamento_id'] ?? 0) === $departamentoMatrizId, 'Setores herdam visibilidade do departamento compartilhado');

echo "Funcoes controller scope unit test passed.\n";
