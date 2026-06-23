<?php
require_once __DIR__ . '/../autoload.php';

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
$suffix = uniqid('dep_share_', true);

$matrizId = $clientes->create([
    'nome_empresa' => 'Matriz Share ' . $suffix,
    'CNPJ' => substr(preg_replace('/\D+/', '', uniqid('', true)), 0, 14),
    'contato' => 'Teste',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$filialAId = $clientes->create([
    'nome_empresa' => 'Filial A Share ' . $suffix,
    'CNPJ' => substr(preg_replace('/\D+/', '', uniqid('', true)), 0, 14),
    'contato' => 'Teste',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
$filialBId = $clientes->create([
    'nome_empresa' => 'Filial B Share ' . $suffix,
    'CNPJ' => substr(preg_replace('/\D+/', '', uniqid('', true)), 0, 14),
    'contato' => 'Teste',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
assert_true($matrizId > 0 && $filialAId > 0 && $filialBId > 0, 'Criou grupo empresarial base');

$departamentoExclusivoId = $departamentos->create([
    'nome' => 'Exclusivo Matriz ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId],
]);
$departamentoCompartilhadoId = $departamentos->create([
    'nome' => 'Compartilhado Filial A ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId, $filialAId],
]);
$departamentoGrupoTodoId = $departamentos->create([
    'nome' => 'Grupo Todo ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId],
    'compartilhar_todas_filiais' => 1,
]);
assert_true(
    $departamentoExclusivoId > 0 && $departamentoCompartilhadoId > 0 && $departamentoGrupoTodoId > 0,
    'Criou departamentos com visibilidades distintas'
);

$setorCompartilhadoId = $setores->create([
    'nome' => 'Setor Compartilhado ' . $suffix,
    'departamento_id' => $departamentoCompartilhadoId,
]);
$funcaoCompartilhadaId = $funcoes->create([
    'nome' => 'Funcao Compartilhada ' . $suffix,
    'setor_id' => $setorCompartilhadoId,
]);
assert_true($setorCompartilhadoId > 0 && $funcaoCompartilhadaId > 0, 'Criou setor e função herdando visibilidade');

$depsFilialA = $departamentos->allByCliente($filialAId);
$depsFilialB = $departamentos->allByCliente($filialBId);
$depsMatriz = $departamentos->allByCliente($matrizId);

$idsFilialA = array_map(static fn(array $row): int => (int)$row['id'], $depsFilialA);
$idsFilialB = array_map(static fn(array $row): int => (int)$row['id'], $depsFilialB);
$idsMatriz = array_map(static fn(array $row): int => (int)$row['id'], $depsMatriz);

assert_true(in_array($departamentoExclusivoId, $idsMatriz, true), 'Matriz visualiza departamento exclusivo');
assert_true(!in_array($departamentoExclusivoId, $idsFilialA, true), 'Filial A não visualiza departamento exclusivo da matriz');
assert_true(in_array($departamentoCompartilhadoId, $idsFilialA, true), 'Filial A visualiza departamento compartilhado seletivamente');
assert_true(!in_array($departamentoCompartilhadoId, $idsFilialB, true), 'Filial B não visualiza departamento não compartilhado com ela');
assert_true(in_array($departamentoGrupoTodoId, $idsFilialA, true) && in_array($departamentoGrupoTodoId, $idsFilialB, true), 'Compartilhamento com todo o grupo alcança as filiais');

$funcoesFilialA = $funcoes->allByCliente($filialAId);
$funcoesFilialB = $funcoes->allByCliente($filialBId);
$funcoesFilialAIds = array_map(static fn(array $row): int => (int)$row['id'], $funcoesFilialA);
$funcoesFilialBIds = array_map(static fn(array $row): int => (int)$row['id'], $funcoesFilialB);

assert_true(in_array($funcaoCompartilhadaId, $funcoesFilialAIds, true), 'Função herda visibilidade do departamento compartilhado');
assert_true(!in_array($funcaoCompartilhadaId, $funcoesFilialBIds, true), 'Função não aparece para filial sem acesso ao departamento');

$ok = $departamentos->update($departamentoCompartilhadoId, [
    'nome' => 'Compartilhado Filial A Ajustado ' . $suffix,
    'cliente_id' => $matrizId,
    'cliente_ids' => [$matrizId],
    'compartilhar_todas_filiais' => 0,
]);
assert_true($ok, 'Removeu associação mantendo o departamento');

$depsFilialAReload = $departamentos->allByCliente($filialAId);
$idsFilialAReload = array_map(static fn(array $row): int => (int)$row['id'], $depsFilialAReload);
assert_true(!in_array($departamentoCompartilhadoId, $idsFilialAReload, true), 'Remoção do vínculo oculta o departamento sem excluí-lo');
assert_true($departamentos->find($departamentoCompartilhadoId) !== null, 'Departamento permanece existente após remover associação');

echo "departamentos_compartilhamento_seletivo_test passed.\n";
