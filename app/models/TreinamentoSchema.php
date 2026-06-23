<?php
namespace App\Models;

use App\Database\Database;
use PDO;

final class TreinamentoSchema
{
    public static function ensure(PDO $db): void
    {
        $db->exec("CREATE TABLE IF NOT EXISTS treinamentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(180) NOT NULL,
            objetivo TEXT NULL,
            publico VARCHAR(180) NULL,
            carga_horaria DECIMAL(10,2) NULL,
            cliente_id INT NULL,
            departamento_id INT NOT NULL,
            periodicidade VARCHAR(40) NULL,
            fornecedor VARCHAR(180) NULL,
            tipo_treinamento VARCHAR(80) NULL,
            template_certificado TEXT NULL,
            assinatura_responsavel VARCHAR(180) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_treinamentos_cliente (cliente_id),
            INDEX idx_treinamentos_departamento (departamento_id),
            INDEX idx_treinamentos_tipo (tipo_treinamento),
            CONSTRAINT fk_treinamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamentos_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamento_setores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            treinamento_id INT NOT NULL,
            setor_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_treinamento_setor (treinamento_id, setor_id),
            INDEX idx_treinamento_setores_setor (setor_id),
            CONSTRAINT fk_treinamento_setores_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamento_setores_setor FOREIGN KEY (setor_id) REFERENCES setores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamento_funcoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            treinamento_id INT NOT NULL,
            funcao_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_treinamento_funcao (treinamento_id, funcao_id),
            INDEX idx_treinamento_funcoes_funcao (funcao_id),
            CONSTRAINT fk_treinamento_funcoes_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamento_funcoes_funcao FOREIGN KEY (funcao_id) REFERENCES funcoes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamento_colaboradores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            treinamento_id INT NOT NULL,
            colaborador_id INT NOT NULL,
            status ENUM('pendente','concluido') NOT NULL DEFAULT 'pendente',
            status_detalhe VARCHAR(30) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_treinamento_colaborador (treinamento_id, colaborador_id),
            INDEX idx_treinamento_colaboradores_colaborador (colaborador_id),
            INDEX idx_treinamento_colaboradores_status (status),
            CONSTRAINT fk_treinamento_colaboradores_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamento_colaboradores_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamentos_agenda (
            id INT AUTO_INCREMENT PRIMARY KEY,
            treinamento_id INT NOT NULL,
            data DATETIME NOT NULL,
            data_fim DATETIME NULL,
            unidade_id INT NOT NULL,
            responsavel_id INT NULL,
            instrutor VARCHAR(180) NULL,
            local VARCHAR(180) NULL,
            observacoes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_treinamentos_agenda_data (data),
            INDEX idx_treinamentos_agenda_data_fim (data_fim),
            INDEX idx_treinamentos_agenda_unidade (unidade_id),
            CONSTRAINT fk_treinamentos_agenda_treinamento FOREIGN KEY (treinamento_id) REFERENCES treinamentos(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamentos_agenda_unidade FOREIGN KEY (unidade_id) REFERENCES clientes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamento_participantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            agenda_id INT NOT NULL,
            colaborador_id INT NOT NULL,
            presenca TINYINT(1) NOT NULL DEFAULT 0,
            certificado_emitido TINYINT(1) NOT NULL DEFAULT 0,
            certificado_numero VARCHAR(80) NULL,
            certificado_codigo VARCHAR(120) NULL,
            certificado_emitido_em DATETIME NULL,
            certificado_arquivo VARCHAR(255) NULL,
            presenca_confirmada_em DATETIME NULL,
            hora_entrada TIME NULL,
            hora_saida TIME NULL,
            observacao TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_treinamento_participante (agenda_id, colaborador_id),
            INDEX idx_treinamento_participantes_colaborador (colaborador_id),
            INDEX idx_treinamento_participantes_certificado (certificado_emitido),
            INDEX idx_treinamento_participantes_presenca (presenca),
            CONSTRAINT fk_treinamento_participantes_agenda FOREIGN KEY (agenda_id) REFERENCES treinamentos_agenda(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamento_participantes_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamento_auditoria_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            treinamento_id INT NULL,
            agenda_id INT NULL,
            participante_id INT NULL,
            colaborador_id INT NULL,
            acao VARCHAR(80) NOT NULL,
            detalhes_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by INT NULL,
            KEY idx_treinamento_auditoria_treinamento (treinamento_id),
            KEY idx_treinamento_auditoria_agenda (agenda_id),
            KEY idx_treinamento_auditoria_participante (participante_id),
            KEY idx_treinamento_auditoria_colaborador (colaborador_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $db->exec("CREATE TABLE IF NOT EXISTS treinamento_export_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cache_key VARCHAR(190) NOT NULL,
            payload_json LONGTEXT NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_treinamento_export_cache_key (cache_key),
            KEY idx_treinamento_export_cache_exp (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        self::ensureColumn($db, 'treinamentos', 'tipo_treinamento', "ALTER TABLE treinamentos ADD COLUMN tipo_treinamento VARCHAR(80) NULL AFTER publico");
        self::ensureColumn($db, 'treinamentos', 'template_certificado', "ALTER TABLE treinamentos ADD COLUMN template_certificado TEXT NULL AFTER fornecedor");
        self::ensureColumn($db, 'treinamentos', 'assinatura_responsavel', "ALTER TABLE treinamentos ADD COLUMN assinatura_responsavel VARCHAR(180) NULL AFTER template_certificado");
        self::ensureColumn($db, 'treinamentos', 'cliente_id', "ALTER TABLE treinamentos ADD COLUMN cliente_id INT NULL AFTER carga_horaria");
        $db->exec("UPDATE treinamentos t
                   JOIN departamentos d ON d.id = t.departamento_id
                   SET t.cliente_id = d.cliente_id
                   WHERE t.cliente_id IS NULL");
        self::ensureIndex($db, 'treinamentos', 'idx_treinamentos_cliente', "ALTER TABLE treinamentos ADD INDEX idx_treinamentos_cliente (cliente_id)");
        self::ensureForeignKey($db, 'treinamentos', 'fk_treinamentos_cliente', "ALTER TABLE treinamentos ADD CONSTRAINT fk_treinamentos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE");

        self::ensureColumn($db, 'colaboradores', 'matricula', "ALTER TABLE colaboradores ADD COLUMN matricula VARCHAR(60) NULL AFTER nome");
        self::ensureColumn($db, 'colaboradores', 'cpf', "ALTER TABLE colaboradores ADD COLUMN cpf VARCHAR(20) NULL AFTER matricula");
        self::ensureColumn($db, 'colaboradores', 'data_admissao', "ALTER TABLE colaboradores ADD COLUMN data_admissao DATE NULL AFTER cpf");
        self::ensureColumn($db, 'colaboradores', 'status_atual', "ALTER TABLE colaboradores ADD COLUMN status_atual VARCHAR(40) NOT NULL DEFAULT 'ativo' AFTER data_admissao");

        self::ensureColumn($db, 'treinamento_participantes', 'certificado_numero', "ALTER TABLE treinamento_participantes ADD COLUMN certificado_numero VARCHAR(80) NULL AFTER certificado_emitido");
        self::ensureColumn($db, 'treinamento_participantes', 'certificado_codigo', "ALTER TABLE treinamento_participantes ADD COLUMN certificado_codigo VARCHAR(120) NULL AFTER certificado_numero");
        self::ensureColumn($db, 'treinamento_participantes', 'certificado_emitido_em', "ALTER TABLE treinamento_participantes ADD COLUMN certificado_emitido_em DATETIME NULL AFTER certificado_codigo");
        self::ensureColumn($db, 'treinamento_participantes', 'certificado_arquivo', "ALTER TABLE treinamento_participantes ADD COLUMN certificado_arquivo VARCHAR(255) NULL AFTER certificado_emitido_em");
        self::ensureColumn($db, 'treinamento_participantes', 'presenca_confirmada_em', "ALTER TABLE treinamento_participantes ADD COLUMN presenca_confirmada_em DATETIME NULL AFTER certificado_arquivo");
        self::ensureColumn($db, 'treinamento_participantes', 'hora_entrada', "ALTER TABLE treinamento_participantes ADD COLUMN hora_entrada TIME NULL AFTER presenca_confirmada_em");
        self::ensureColumn($db, 'treinamento_participantes', 'hora_saida', "ALTER TABLE treinamento_participantes ADD COLUMN hora_saida TIME NULL AFTER hora_entrada");
        self::ensureColumn($db, 'treinamento_participantes', 'observacao', "ALTER TABLE treinamento_participantes ADD COLUMN observacao TEXT NULL AFTER hora_saida");
        self::ensureColumn($db, 'treinamentos_agenda', 'data_fim', "ALTER TABLE treinamentos_agenda ADD COLUMN data_fim DATETIME NULL AFTER data");
        self::ensureColumn($db, 'treinamento_colaboradores', 'status_detalhe', "ALTER TABLE treinamento_colaboradores ADD COLUMN status_detalhe VARCHAR(30) NULL AFTER status");
        self::ensureIndex($db, 'treinamentos_agenda', 'idx_treinamentos_agenda_data_fim', "ALTER TABLE treinamentos_agenda ADD INDEX idx_treinamentos_agenda_data_fim (data_fim)");

        self::ensureIndex($db, 'colaboradores', 'idx_colaboradores_matricula', "ALTER TABLE colaboradores ADD INDEX idx_colaboradores_matricula (matricula)");
        self::ensureIndex($db, 'colaboradores', 'idx_colaboradores_status_atual', "ALTER TABLE colaboradores ADD INDEX idx_colaboradores_status_atual (status_atual)");
        self::ensureIndex($db, 'colaboradores', 'idx_colaboradores_data_admissao', "ALTER TABLE colaboradores ADD INDEX idx_colaboradores_data_admissao (data_admissao)");
        self::ensureIndex($db, 'treinamentos', 'idx_treinamentos_tipo', "ALTER TABLE treinamentos ADD INDEX idx_treinamentos_tipo (tipo_treinamento)");
    }

    private static function ensureColumn(PDO $db, string $table, string $column, string $sql): void
    {
        if (!Database::columnExists($table, $column)) {
            $db->exec($sql);
        }
    }

    private static function ensureIndex(PDO $db, string $table, string $index, string $sql): void
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = :table_name AND index_name = :index_name');
        $stmt->execute([
            'table_name' => $table,
            'index_name' => $index,
        ]);
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec($sql);
        }
    }

    private static function ensureForeignKey(PDO $db, string $table, string $constraint, string $sql): void
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = :table_name AND constraint_name = :constraint_name');
        $stmt->execute([
            'table_name' => $table,
            'constraint_name' => $constraint,
        ]);
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec($sql);
        }
    }
}
