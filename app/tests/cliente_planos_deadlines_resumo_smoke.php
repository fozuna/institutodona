<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\PlanoAcaoTaskModel;

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$model = new PlanoAcaoTaskModel();
$pdo = Database::getConnection();
$stmtCliente = $pdo->prepare('INSERT INTO clientes (nome_empresa, CNPJ, contato, is_matriz, matriz_id) VALUES (:n,:c,:ct,1,NULL)');
$cnpjBase = str_pad((string)random_int(1, 99999999999999), 14, '0', STR_PAD_LEFT);
$cnpjFmt = substr($cnpjBase, 0, 2) . '.' . substr($cnpjBase, 2, 3) . '.' . substr($cnpjBase, 5, 3) . '/' . substr($cnpjBase, 8, 4) . '-' . substr($cnpjBase, 12, 2);
$stmtCliente->execute([
    'n' => 'Cliente Deadlines Smoke ' . uniqid('', true),
    'c' => $cnpjFmt,
    'ct' => 'Contato',
]);
$clienteId = (int)$pdo->lastInsertId();
$prefix = 'smoke deadlines ' . uniqid('', true);

$hoje = new DateTimeImmutable('today');
$ontem = $hoje->modify('-1 day')->format('Y-m-d');
$amanha = $hoje->modify('+1 day')->format('Y-m-d');

$model->create([
    'id_cliente' => $clienteId,
    'titulo' => $prefix . ' vencido',
    'status' => 'Pendente',
    'prazo' => $ontem,
    'progresso' => 10,
]);
$model->create([
    'id_cliente' => $clienteId,
    'titulo' => $prefix . ' pendente',
    'status' => 'Em Andamento',
    'prazo' => $amanha,
    'progresso' => 40,
]);
$model->create([
    'id_cliente' => $clienteId,
    'titulo' => $prefix . ' concluido passado',
    'status' => 'Concluído',
    'prazo' => $ontem,
    'progresso' => 100,
]);

$resumoPrazos = $model->summarizeDeadlineCountersByClientes([$clienteId], [], '');

$stmt = $pdo->prepare('DELETE FROM pdca_tasks WHERE id_cliente = :id AND titulo LIKE :prefix');
$stmt->execute([
    'id' => $clienteId,
    'prefix' => $prefix . '%',
]);
$pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);

echo json_encode([
    'pendentes_ok' => (int)$resumoPrazos['total_pendentes_prazo'] === 1,
    'vencidos_ok' => (int)$resumoPrazos['total_vencidos_prazo'] === 1,
], JSON_UNESCAPED_UNICODE);

if ((int)$resumoPrazos['total_pendentes_prazo'] !== 1
    || (int)$resumoPrazos['total_vencidos_prazo'] !== 1) {
    exit(1);
}
