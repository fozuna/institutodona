<?php
require __DIR__ . '/../autoload.php';

use App\Controllers\ManuaisController;
use App\Database\Database;
use App\Models\ManualModel;

$pdo = Database::getConnection();
$empresaId = (int)$pdo->query('SELECT id FROM clientes ORDER BY id ASC LIMIT 1')->fetchColumn();
// Reaproveita só departamento visível para a empresa (vínculo em departamento_clientes).
$stmt = $pdo->prepare('SELECT d.id FROM departamentos d JOIN departamento_clientes dc ON dc.departamento_id = d.id AND dc.cliente_id = d.cliente_id WHERE d.cliente_id = :cid ORDER BY d.id ASC LIMIT 1');
$stmt->execute(['cid' => $empresaId]);
$departamentoId = (int)$stmt->fetchColumn();
$createdEmpresa = false;
$createdDepartamento = false;

if ($empresaId <= 0 || $departamentoId <= 0) {
    if ($empresaId <= 0) {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $stmt = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato) VALUES (:nome, :cnpj, :contato)');
        $stmt->execute([
            'nome' => 'Cliente Smoke Manual DL ' . $suffix,
            'cnpj' => '88.888.888/0001-' . substr($suffix, 0, 2),
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
        // Vínculo dono->departamento (a aplicação cria em DepartamentoModel::syncClienteLinks;
        // sem ele o departamento não é visível para a empresa e o ManualModel recusa).
        $pdo->prepare('INSERT IGNORE INTO departamento_clientes (departamento_id, cliente_id) VALUES (?, ?)')->execute([$departamentoId, $empresaId]);
        $createdDepartamento = true;
    }
}

$model = new ManualModel();
$manualId = $model->create([
    'empresa_id' => $empresaId,
    'departamento_id' => $departamentoId,
    'nome' => 'Manual Permission Smoke',
    'descricao' => 'Teste',
    'arquivo' => 'storage/manuais/' . $empresaId . '/' . $departamentoId . '/fake.pdf',
    'tipo_arquivo' => 'pdf',
    'tamanho' => 5,
    'usuario_id' => 1,
]);

// Higiene (Sprint 04): download() pode dar exit()/lancar excecao antes do
// bloco de limpeza abaixo - o Manual (e Empresa/Departamento eventualmente
// criados) ficariam orfaos. A limpeza tambem roda no shutdown (idempotente).
register_shutdown_function(function () use ($pdo, $manualId, $createdDepartamento, $departamentoId, $createdEmpresa, $empresaId) {
    try {
        if ($manualId > 0) { $pdo->prepare('DELETE FROM manuais WHERE id = :id')->execute(['id' => $manualId]); }
        if ($createdDepartamento) { $pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $departamentoId]); }
        if ($createdEmpresa) { $pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $empresaId]); }
    } catch (\Throwable $e) {
    }
});

$_SESSION['user'] = [
    'id' => 2,
    'nome' => 'Consultor',
    'email' => 'consultor@example.com',
    'tipo_acesso' => 'consultor',
    'allowed_client_ids' => [],
];
$_GET = ['id' => $manualId];
http_response_code(200);
ob_start();
(new ManuaisController())->download();
$output = (string)ob_get_clean();

$cleanup = $pdo->prepare('DELETE FROM manuais WHERE id = :id');
$cleanup->execute(['id' => $manualId]);
if ($createdDepartamento) {
    $stmt = $pdo->prepare('DELETE FROM departamentos WHERE id = :id');
    $stmt->execute(['id' => $departamentoId]);
}
if ($createdEmpresa) {
    $stmt = $pdo->prepare('DELETE FROM clientes WHERE id = :id');
    $stmt->execute(['id' => $empresaId]);
}

echo json_encode([
    'returns_403' => http_response_code() === 403,
    'message_ok' => str_contains($output, 'Sem permissão.'),
], JSON_UNESCAPED_UNICODE);

if (http_response_code() !== 403) {
    exit(1);
}
