<?php
namespace App\Models;

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
            departamento_id INT NOT NULL,
            periodicidade VARCHAR(40) NULL,
            fornecedor VARCHAR(180) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_treinamentos_departamento (departamento_id),
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
            unidade_id INT NOT NULL,
            responsavel_id INT NULL,
            instrutor VARCHAR(180) NULL,
            local VARCHAR(180) NULL,
            observacoes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_treinamentos_agenda_data (data),
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
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_treinamento_participante (agenda_id, colaborador_id),
            INDEX idx_treinamento_participantes_colaborador (colaborador_id),
            CONSTRAINT fk_treinamento_participantes_agenda FOREIGN KEY (agenda_id) REFERENCES treinamentos_agenda(id) ON DELETE CASCADE,
            CONSTRAINT fk_treinamento_participantes_colaborador FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
