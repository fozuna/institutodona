<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
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

$cleanup = $pdo->prepare('DELETE FROM manuais WHERE id = :id');
$cleanup->execute(['id' => $id]);
if ($createdDepartamento) {
    $stmt = $pdo->prepare('DELETE FROM departamentos WHERE id = :id');
    $stmt->execute(['id' => $departamentoId]);
}
if ($createdEmpresa) {
    $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $empresaId]);
}

echo json_encode([
    'created_id_positive' => $id > 0,
    'list_by_empresa_found' => count($all) >= 1,
    'list_by_empresa_departamento_nome_found' => count($filtered) >= 1,
], JSON_UNESCAPED_UNICODE);

if ($id <= 0 || count($all) < 1 || count($filtered) < 1) {
    exit(1);
}
