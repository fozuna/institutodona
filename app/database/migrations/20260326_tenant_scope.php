<?php
require_once __DIR__ . '/../../autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();

function tableExists(\PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t');
    $stmt->execute(['t' => $table]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureIndex(\PDO $pdo, string $table, string $indexName, string $ddl): void
{
    if (!tableExists($pdo, $table)) {
        return;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :t AND index_name = :i');
    $stmt->execute(['t' => $table, 'i' => $indexName]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec($ddl);
    }
}

function ensureForeignKey(\PDO $pdo, string $table, string $fkName, string $ddl): void
{
    if (!tableExists($pdo, $table)) {
        return;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema = DATABASE() AND table_name = :t AND constraint_name = :f');
    $stmt->execute(['t' => $table, 'f' => $fkName]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec($ddl);
    }
}

function ensureColumn(\PDO $pdo, string $table, string $column, string $ddl): void
{
    if (!tableExists($pdo, $table)) {
        return;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :t AND column_name = :c');
    $stmt->execute(['t' => $table, 'c' => $column]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->exec($ddl);
    }
}

function ensureUsuariosRoles(\PDO $pdo): void
{
    if (!tableExists($pdo, 'usuarios')) {
        return;
    }
    $stmt = $pdo->prepare("SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'tipo_acesso' LIMIT 1");
    $stmt->execute();
    $columnType = strtolower((string)$stmt->fetchColumn());
    $required = ["'instituto'", "'cliente'", "'cliente_admin'", "'reader'", "'consultor'"];
    foreach ($required as $token) {
        if (strpos($columnType, $token) === false) {
            $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN tipo_acesso ENUM('instituto','cliente','cliente_admin','reader','consultor') NOT NULL DEFAULT 'cliente'");
            break;
        }
    }
}

ensureColumn($pdo, 'clientes', 'ativo', 'ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1');
ensureColumn($pdo, 'clientes', 'acesso_restrito', 'ALTER TABLE clientes ADD COLUMN acesso_restrito TINYINT(1) NOT NULL DEFAULT 0');
if (tableExists($pdo, 'usuario_empresas') === false) {
    $pdo->exec("CREATE TABLE usuario_empresas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        cliente_id INT NOT NULL,
        origem ENUM('direto','herdado') NOT NULL DEFAULT 'direto',
        permitido TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_usuario_cliente (usuario_id, cliente_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
ensureIndex($pdo, 'clientes', 'idx_clientes_matriz_id', 'ALTER TABLE clientes ADD INDEX idx_clientes_matriz_id (matriz_id)');
ensureIndex($pdo, 'usuarios', 'idx_usuarios_id_cliente', 'ALTER TABLE usuarios ADD INDEX idx_usuarios_id_cliente (id_cliente)');
ensureIndex($pdo, 'aplicacoes', 'idx_aplicacoes_id_cliente', 'ALTER TABLE aplicacoes ADD INDEX idx_aplicacoes_id_cliente (id_cliente)');
ensureIndex($pdo, 'pdca_tasks', 'idx_pdca_tasks_id_cliente', 'ALTER TABLE pdca_tasks ADD INDEX idx_pdca_tasks_id_cliente (id_cliente)');
ensureIndex($pdo, 'avaliacoes', 'idx_avaliacoes_cliente_id', 'ALTER TABLE avaliacoes ADD INDEX idx_avaliacoes_cliente_id (cliente_id)');
ensureIndex($pdo, 'indicadores', 'idx_indicadores_cliente_id', 'ALTER TABLE indicadores ADD INDEX idx_indicadores_cliente_id (cliente_id)');
ensureIndex($pdo, 'usuario_empresas', 'idx_usuario_empresas_usuario', 'ALTER TABLE usuario_empresas ADD INDEX idx_usuario_empresas_usuario (usuario_id)');
ensureIndex($pdo, 'usuario_empresas', 'idx_usuario_empresas_cliente', 'ALTER TABLE usuario_empresas ADD INDEX idx_usuario_empresas_cliente (cliente_id)');
ensureForeignKey($pdo, 'clientes', 'fk_clientes_matriz', 'ALTER TABLE clientes ADD CONSTRAINT fk_clientes_matriz FOREIGN KEY (matriz_id) REFERENCES clientes(id) ON DELETE SET NULL');
ensureForeignKey($pdo, 'usuario_empresas', 'fk_usuario_empresas_usuario', 'ALTER TABLE usuario_empresas ADD CONSTRAINT fk_usuario_empresas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE');
ensureForeignKey($pdo, 'usuario_empresas', 'fk_usuario_empresas_cliente', 'ALTER TABLE usuario_empresas ADD CONSTRAINT fk_usuario_empresas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE');
ensureUsuariosRoles($pdo);

echo "MIGRATION_OK\n";
