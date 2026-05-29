<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\ManualModel;

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$empresaId = (int)$pdo->query('SELECT id FROM clientes ORDER BY id ASC LIMIT 1')->fetchColumn();
$stmt = $pdo->prepare('SELECT id FROM departamentos WHERE cliente_id = :cid ORDER BY id ASC LIMIT 1');
$stmt->execute(['cid' => $empresaId]);
$departamentoId = (int)$stmt->fetchColumn();
$createdEmpresa = false;
$createdDepartamento = false;

if ($empresaId <= 0 || $departamentoId <= 0) {
    if ($empresaId <= 0) {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
        $stmt->execute([
            'nome' => 'Cliente Smoke Manual ' . $suffix,
            'cnpj' => '99.999.999/0001-' . substr($suffix, 0, 2),
            'contato' => 'Smoke',
        ]);
        $empresaId = (int)$pdo->lastInsertId();
        $createdEmpresa = true;
    }
    if ($departamentoId <= 0) {
        $stmt = $pdo->prepare('INSERT INTO departamentos (nome, cliente_id) VALUES (:nome, :cid)');
        $stmt->execute([
            'nome' => 'Departamento Smoke',
            'cid' => $empresaId,
        ]);
        $departamentoId = (int)$pdo->lastInsertId();
        $createdDepartamento = true;
    }
}

$model = new ManualModel();
$prefix = 'Manual Smoke ' . uniqid('', true);
$id = $model->create([
    'empresa_id' => $empresaId,
    'departamento_id' => $departamentoId,
    'nome' => $prefix,
    'descricao' => 'Descricao smoke',
    'arquivo' => 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/fake.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 123,
    'usuario_id' => 1,
]);

$all = $model->list(['empresa_id' => $empresaId, 'nome' => $prefix]);
$filtered = $model->list(['empresa_id' => $empresaId, 'departamento_id' => $departamentoId, 'nome' => $prefix]);

$clientes = new ClienteModel();
$deps = new DepartamentoModel();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);
$stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:nome, :cnpj, :contato, :is_matriz, :matriz_id)');
$stmt->execute([
    'nome' => 'Matriz Manual Link ' . $suffix,
    'cnpj' => '55.555.555/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$matrizId = (int)$pdo->lastInsertId();
$stmt->execute([
    'nome' => 'Filial Manual Link ' . $suffix,
    'cnpj' => '44.444.444/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 0,
    'matriz_id' => $matrizId,
]);
$filialId = (int)$pdo->lastInsertId();
$depMatrizId = $deps->create(['nome' => 'Dep Matriz Link ' . $suffix, 'cliente_id' => $matrizId]);
$depFilialId = $deps->create(['nome' => 'Dep Filial Link ' . $suffix, 'cliente_id' => $filialId]);
$manualMatriz = $model->create([
    'empresa_id' => $matrizId,
    'departamento_id' => $depMatrizId,
    'nome' => 'Manual Matriz Link ' . $suffix,
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $matrizId . '/' . $depMatrizId . '/m.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
    'usuario_id' => 1,
]);
$manualFilial = $model->create([
    'empresa_id' => $filialId,
    'departamento_id' => $depFilialId,
    'nome' => 'Manual Filial Link ' . $suffix,
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $filialId . '/' . $depFilialId . '/f.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
    'usuario_id' => 1,
]);
$model->replaceFilialLinks($manualMatriz, [$filialId]);
$updatedName = 'Manual Matriz Link Updated ' . $suffix;
$model->update($manualMatriz, [
    'empresa_id' => $matrizId,
    'departamento_id' => $depMatrizId,
    'nome' => $updatedName,
    'descricao' => 'Teste 2',
    'arquivo' => 'storage/manuais/' . $matrizId . '/' . $depMatrizId . '/m.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 1,
]);
$matrizResolved = $clientes->catalogRootIdFor($filialId);
$listFilial = $model->listBiblioteca($filialId, $matrizResolved, ['nome' => 'Manual']);
$seenLinked = false;
$seenLocal = false;
$syncedUpdate = false;
foreach ($listFilial as $row) {
    if ((int)$row['id'] === $manualMatriz && (int)($row['is_linked_from_matriz'] ?? 0) === 1) {
        $seenLinked = true;
        if ((string)($row['nome'] ?? '') === $updatedName) {
            $syncedUpdate = true;
        }
    }
    if ((int)$row['id'] === $manualFilial && (int)($row['is_linked_from_matriz'] ?? 0) === 0) {
        $seenLocal = true;
    }
}
$linkOk = $model->isLinkedToFilial($manualMatriz, $filialId);

$cleanup = $pdo->prepare('DELETE FROM manuais WHERE id = :id');
$cleanup->execute(['id' => $id]);
 $cleanup->execute(['id' => $manualMatriz]);
 $cleanup->execute(['id' => $manualFilial]);
if ($createdDepartamento) {
    $stmt = $pdo->prepare('DELETE FROM departamentos WHERE id = :id');
    $stmt->execute(['id' => $departamentoId]);
}
if ($createdEmpresa) {
    $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $empresaId]);
}
 $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $depMatrizId]);
 $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $depFilialId]);
 $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $filialId]);
 $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $matrizId]);

echo json_encode([
    'created_id_positive' => $id > 0,
    'list_by_empresa_found' => count($all) >= 1,
    'list_by_empresa_departamento_nome_found' => count($filtered) >= 1,
    'filial_sees_linked_manual' => $seenLinked,
    'filial_sees_local_manual' => $seenLocal,
    'link_exists' => $linkOk,
    'synced_update' => $syncedUpdate,
], JSON_UNESCAPED_UNICODE);

if ($id <= 0 || count($all) < 1 || count($filtered) < 1 || !$seenLinked || !$seenLocal || !$linkOk || !$syncedUpdate) {
    exit(1);
}
