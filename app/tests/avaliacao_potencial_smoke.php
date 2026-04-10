<?php
require __DIR__ . '/../autoload.php';

use App\Database\Database;
use App\Models\AvaliacaoModel;

$_SESSION['user'] = [
    'id' => 1,
    'tipo_acesso' => 'instituto',
    'allowed_client_ids' => [],
];

$pdo = Database::getConnection();
$clienteId = (int)$pdo->query('SELECT id FROM clientes ORDER BY id ASC LIMIT 1')->fetchColumn();
if ($clienteId <= 0) {
    echo 'NO_CLIENTE';
    exit(0);
}

$model = new AvaliacaoModel();
$avaliacaoId = $model->create([
    'cliente_id' => null,
    'empresa_nome' => 'Potencial Cliente Smoke',
    'nome' => 'Contato Smoke',
    'email' => 'potencial@example.com',
    'whatsapp' => '11999999999',
    'numero_funcionarios' => 12,
    'numero_lideres' => 3,
    'faturamento_medio_anual' => 150000,
    'tomador_decisao' => 1,
    'origem_cadastro' => 'potencial_cliente',
    'contato' => 'Contato Smoke',
    'respostas_json' => json_encode(['financeiro' => [], 'mercado' => [], 'pessoas' => [], 'processo' => []]),
    'nota_financeiro' => 0,
    'nota_mercado' => 0,
    'nota_pessoas' => 0,
    'nota_processo' => 0,
    'realidade_financeiro' => 0,
    'realidade_mercado' => 0,
    'realidade_pessoas' => 0,
    'realidade_processo' => 0,
]);

if ($avaliacaoId <= 0) {
    echo 'NO_AVALIACAO';
    exit(0);
}

$antes = $model->find($avaliacaoId);
$associada = $model->associateCliente($avaliacaoId, $clienteId);
$depois = $model->find($avaliacaoId);

echo json_encode([
    'avaliacao_id' => $avaliacaoId,
    'origem' => $antes['origem_cadastro'] ?? null,
    'sem_cliente_inicial' => empty($antes['cliente_id']),
    'associada' => $associada,
    'cliente_final' => (int)($depois['cliente_id'] ?? 0),
    'associado_em' => !empty($depois['cliente_associado_em']),
], JSON_UNESCAPED_UNICODE);
