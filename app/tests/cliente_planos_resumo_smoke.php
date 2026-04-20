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
    'n' => 'Cliente Resumo Smoke ' . uniqid('', true),
    'c' => $cnpjFmt,
    'ct' => 'Contato',
]);
$clienteId = (int)$pdo->lastInsertId();
$prefix = 'smoke resumo ' . uniqid('', true);

$model->create([
    'id_cliente' => $clienteId,
    'titulo' => $prefix . ' concluido',
    'status' => 'Concluído',
    'progresso' => 100,
]);
$model->create([
    'id_cliente' => $clienteId,
    'titulo' => $prefix . ' andamento',
    'status' => 'Em Andamento',
    'progresso' => 30,
]);
$model->create([
    'id_cliente' => $clienteId,
    'titulo' => $prefix . ' pendente',
    'status' => 'Pendente',
    'progresso' => 0,
]);

$resumoTodos = $model->summarizeByClienteMulti($clienteId, []);
$resumoConcluidos = $model->summarizeByClienteMulti($clienteId, ['Concluído']);
$resumoNaoConcluidos = $model->summarizeByClienteMulti($clienteId, ['Em Andamento', 'Pendente']);
$listagemNaoConcluidos = $model->paginateByClienteMulti($clienteId, 1, 50, ['Em Andamento', 'Pendente']);

$stmt = $pdo->prepare('DELETE FROM pdca_tasks WHERE id_cliente = :id AND titulo LIKE :prefix');
$stmt->execute([
    'id' => $clienteId,
    'prefix' => $prefix . '%',
]);
$pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);

echo json_encode([
    'todos_total_ok' => (int)$resumoTodos['total_planos'] === 3,
    'todos_concluidos_ok' => (int)$resumoTodos['total_concluidos'] === 1,
    'todos_nao_concluidos_ok' => (int)$resumoTodos['total_nao_concluidos'] === 2,
    'filtro_concluidos_ok' => (int)$resumoConcluidos['total_planos'] === 1 && (int)$resumoConcluidos['total_nao_concluidos'] === 0,
    'filtro_nao_concluidos_ok' => (int)$resumoNaoConcluidos['total_planos'] === 2 && (int)$resumoNaoConcluidos['total_concluidos'] === 0,
    'resumo_bate_listagem' => count($listagemNaoConcluidos) === (int)$resumoNaoConcluidos['total_planos'],
], JSON_UNESCAPED_UNICODE);

if ((int)$resumoTodos['total_planos'] !== 3
    || (int)$resumoTodos['total_concluidos'] !== 1
    || (int)$resumoTodos['total_nao_concluidos'] !== 2
    || count($listagemNaoConcluidos) !== (int)$resumoNaoConcluidos['total_planos']) {
    exit(1);
}
