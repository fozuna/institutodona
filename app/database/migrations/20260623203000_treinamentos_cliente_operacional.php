<?php
require_once __DIR__ . '/../../autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();

$columnStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM information_schema.columns
     WHERE table_schema = DATABASE()
       AND table_name = :table_name
       AND column_name = :column_name'
);

$constraintStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM information_schema.table_constraints
     WHERE table_schema = DATABASE()
       AND table_name = :table_name
       AND constraint_name = :constraint_name'
);

$indexStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM information_schema.statistics
     WHERE table_schema = DATABASE()
       AND table_name = :table_name
       AND index_name = :index_name'
);

$columnStmt->execute([
    'table_name' => 'treinamentos',
    'column_name' => 'cliente_id',
]);
if ((int)$columnStmt->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE treinamentos ADD COLUMN cliente_id INT NULL AFTER carga_horaria');
}

$pdo->exec(
    'UPDATE treinamentos t
     JOIN departamentos d ON d.id = t.departamento_id
     SET t.cliente_id = d.cliente_id
     WHERE t.cliente_id IS NULL'
);

$indexStmt->execute([
    'table_name' => 'treinamentos',
    'index_name' => 'idx_treinamentos_cliente',
]);
if ((int)$indexStmt->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE treinamentos ADD INDEX idx_treinamentos_cliente (cliente_id)');
}

$constraintStmt->execute([
    'table_name' => 'treinamentos',
    'constraint_name' => 'fk_treinamentos_cliente',
]);
if ((int)$constraintStmt->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE treinamentos ADD CONSTRAINT fk_treinamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE');
}

echo "MIGRATION_OK\n";
