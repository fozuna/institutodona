<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\AuditoriaModel;
use App\Models\ClienteModel;
use App\Models\DepartamentoModel;
use App\Models\SetorModel;

$_SESSION['user'] = [
    'id' => 1,
    'nome' => 'Instituto',
    'email' => 'instituto@example.com',
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$clientes = new ClienteModel();
$departamentos = new DepartamentoModel();
$setores = new SetorModel();
$auditorias = new AuditoriaModel();
$pdo = Database::getConnection();
$suffix = substr(bin2hex(random_bytes(4)), 0, 8);

$clienteId = $clientes->create([
    'nome_empresa' => 'Cliente Auditoria Media ' . $suffix,
    'CNPJ' => '33.333.333/0001-' . substr($suffix, 0, 2),
    'contato' => 'Smoke',
    'is_matriz' => 1,
    'matriz_id' => null,
]);
$departamentoId = $departamentos->create([
    'nome' => 'Departamento Media ' . $suffix,
    'cliente_id' => $clienteId,
]);
$setorId = $setores->create([
    'nome' => 'Setor Media ' . $suffix,
    'departamento_id' => $departamentoId,
]);

$insert = $pdo->prepare("INSERT INTO auditorias
    (cliente_id, setor_id, responsavel_id, data_auditoria, nome_auditoria, pergunta, objetivo, referencia_esperada, status, conformidade_pct, semaforo, created_by, updated_by)
    VALUES
    (:cliente_id, :setor_id, NULL, :data_auditoria, :nome_auditoria, :pergunta, :objetivo, :referencia_esperada, :status, :pct, :sem, 1, 1)");

$rows = [
    ['nome' => 'Auditoria Verde ' . $suffix, 'status' => 'Realizada', 'pct' => 100.00, 'sem' => 'verde'],
    ['nome' => 'Auditoria Vermelha ' . $suffix, 'status' => 'Realizada', 'pct' => 0.00, 'sem' => 'vermelho'],
    ['nome' => 'Auditoria Agenda ' . $suffix, 'status' => 'Agendada', 'pct' => null, 'sem' => null],
];
$auditIds = [];
foreach ($rows as $row) {
    $insert->execute([
        'cliente_id' => $clienteId,
        'setor_id' => $setorId,
        'data_auditoria' => '2026-04-25',
        'nome_auditoria' => $row['nome'],
        'pergunta' => 'Pergunta',
        'objetivo' => 'Objetivo',
        'referencia_esperada' => 'Referencia',
        'status' => $row['status'],
        'pct' => $row['pct'],
        'sem' => $row['sem'],
    ]);
    $auditIds[] = (int)$pdo->lastInsertId();
}

$metricsAll = $auditorias->summarizeMetrics(['cliente' => $clienteId]);
$metricsGreen = $auditorias->summarizeMetrics(['cliente' => $clienteId, 'farol' => 'verde']);
$metricsNoData = $auditorias->summarizeMetrics(['cliente' => $clienteId, 'farol' => 'amarelo']);

foreach ($auditIds as $auditId) {
    $pdo->prepare('DELETE FROM auditorias WHERE id = :id')->execute(['id' => $auditId]);
}
$pdo->prepare('DELETE FROM setores WHERE id = :id')->execute(['id' => $setorId]);
$pdo->prepare('DELETE FROM departamentos WHERE id = :id')->execute(['id' => $departamentoId]);
$pdo->prepare('DELETE FROM clientes WHERE id = :id')->execute(['id' => $clienteId]);

echo json_encode([
    'media_geral_ok' => round((float)($metricsAll['geral']['media'] ?? -1), 2) === 50.00,
    'count_geral_ok' => (int)($metricsAll['geral']['count'] ?? 0) === 2,
    'media_filtrada_verde_ok' => round((float)($metricsGreen['filtrada']['media'] ?? -1), 2) === 100.00,
    'count_filtrada_verde_ok' => (int)($metricsGreen['filtrada']['count'] ?? 0) === 1,
    'sem_dados_amarelo_ok' => (int)($metricsNoData['filtrada']['count'] ?? -1) === 0 && ($metricsNoData['filtrada']['media'] === null || (float)$metricsNoData['filtrada']['media'] === 0.0),
], JSON_UNESCAPED_UNICODE);

if (round((float)($metricsAll['geral']['media'] ?? -1), 2) !== 50.00
    || (int)($metricsAll['geral']['count'] ?? 0) !== 2
    || round((float)($metricsGreen['filtrada']['media'] ?? -1), 2) !== 100.00
    || (int)($metricsGreen['filtrada']['count'] ?? 0) !== 1
    || (int)($metricsNoData['filtrada']['count'] ?? -1) !== 0) {
    exit(1);
}
