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

function ensureAuditoriasResponsavelFk(\PDO $pdo): void
{
    if (!tableExists($pdo, 'auditorias')) {
        return;
    }
    $stmt = $pdo->prepare("SELECT referenced_table_name
                           FROM information_schema.key_column_usage
                           WHERE constraint_schema = DATABASE()
                             AND table_name = 'auditorias'
                             AND constraint_name = 'fk_auditorias_responsavel'
                           LIMIT 1");
    $stmt->execute();
    $refTable = strtolower((string)$stmt->fetchColumn());
    if ($refTable === 'consultores') {
        $pdo->exec("ALTER TABLE auditorias DROP FOREIGN KEY fk_auditorias_responsavel");
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
if (tableExists($pdo, 'auditorias') === false) {
    $pdo->exec("CREATE TABLE auditorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        cliente_id INT NOT NULL,
        setor_id INT NOT NULL,
        responsavel_id INT NOT NULL,
        data_auditoria DATE NOT NULL,
        pergunta VARCHAR(500) NOT NULL,
        objetivo TEXT NOT NULL,
        referencia_esperada VARCHAR(255) NOT NULL,
        status ENUM('Agendada','Realizada') NOT NULL DEFAULT 'Agendada',
        avaliacao TEXT NULL,
        obs TEXT NULL,
        realizada_at DATETIME NULL,
        created_by INT NULL,
        updated_by INT NULL,
        deleted_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
if (tableExists($pdo, 'auditoria_relatorios') === false) {
    $pdo->exec("CREATE TABLE auditoria_relatorios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        auditoria_id INT NOT NULL,
        relatorio_ref VARCHAR(120) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
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
ensureIndex($pdo, 'auditorias', 'idx_auditorias_cliente', 'ALTER TABLE auditorias ADD INDEX idx_auditorias_cliente (cliente_id)');
ensureIndex($pdo, 'auditorias', 'idx_auditorias_setor', 'ALTER TABLE auditorias ADD INDEX idx_auditorias_setor (setor_id)');
ensureIndex($pdo, 'auditorias', 'idx_auditorias_responsavel', 'ALTER TABLE auditorias ADD INDEX idx_auditorias_responsavel (responsavel_id)');
ensureIndex($pdo, 'auditorias', 'idx_auditorias_status_data', 'ALTER TABLE auditorias ADD INDEX idx_auditorias_status_data (status, data_auditoria)');
ensureIndex($pdo, 'auditoria_relatorios', 'idx_auditoria_relatorios_auditoria', 'ALTER TABLE auditoria_relatorios ADD INDEX idx_auditoria_relatorios_auditoria (auditoria_id)');
ensureForeignKey($pdo, 'clientes', 'fk_clientes_matriz', 'ALTER TABLE clientes ADD CONSTRAINT fk_clientes_matriz FOREIGN KEY (matriz_id) REFERENCES clientes(id) ON DELETE SET NULL');
ensureForeignKey($pdo, 'usuario_empresas', 'fk_usuario_empresas_usuario', 'ALTER TABLE usuario_empresas ADD CONSTRAINT fk_usuario_empresas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE');
ensureForeignKey($pdo, 'usuario_empresas', 'fk_usuario_empresas_cliente', 'ALTER TABLE usuario_empresas ADD CONSTRAINT fk_usuario_empresas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE');
ensureForeignKey($pdo, 'auditorias', 'fk_auditorias_cliente', 'ALTER TABLE auditorias ADD CONSTRAINT fk_auditorias_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE');
ensureForeignKey($pdo, 'auditorias', 'fk_auditorias_setor', 'ALTER TABLE auditorias ADD CONSTRAINT fk_auditorias_setor FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE RESTRICT');
ensureAuditoriasResponsavelFk($pdo);
ensureForeignKey($pdo, 'auditorias', 'fk_auditorias_responsavel', 'ALTER TABLE auditorias ADD CONSTRAINT fk_auditorias_responsavel FOREIGN KEY (responsavel_id) REFERENCES colaboradores(id) ON DELETE RESTRICT');
ensureForeignKey($pdo, 'auditoria_relatorios', 'fk_auditoria_relatorios_auditoria', 'ALTER TABLE auditoria_relatorios ADD CONSTRAINT fk_auditoria_relatorios_auditoria FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE');
ensureUsuariosRoles($pdo);

echo "MIGRATION_OK\n";
