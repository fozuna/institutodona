<?php
require __DIR__ . '/app/autoload.php';

use App\Models\PlanoAcaoTaskModel;

$model = new PlanoAcaoTaskModel();

$clienteId = isset($argv[1]) ? (int)$argv[1] : 0;

if ($clienteId > 0) {
    $rows = $model->byCliente($clienteId);
    echo "pdca_tasks for id_cliente={$clienteId}:\n";
} else {
    $pdo = \App\Database\Database::getConnection();
    $rows = $pdo->query('SELECT * FROM pdca_tasks ORDER BY id DESC LIMIT 50')->fetchAll();
    echo "Last 50 pdca_tasks rows:\n";
}

foreach ($rows as $r) {
    echo sprintf(
        "#%d cliente=%d titulo=%s status=%s prazo=%s\n",
        $r['id'],
        $r['id_cliente'],
        $r['titulo'],
        $r['status'],
        $r['prazo'] ?? ''
    );
}

