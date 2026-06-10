SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'cronograma_evento_tipos'
    ),
    'SELECT 1',
    'CREATE TABLE cronograma_evento_tipos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(50) NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_cronograma_evento_tipos_nome (nome),
        INDEX idx_cronograma_evento_tipos_ativo (ativo)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO cronograma_evento_tipos (nome, ativo) VALUES
  ('Gestão', 1),
  ('Auditoria', 1),
  ('Treinamento', 1),
  ('Reunião', 1),
  ('Consultoria', 1),
  ('Implementação', 1),
  ('Outros', 1),
  ('Tarefa', 1),
  ('Indicador', 1),
  ('Pessoas', 1),
  ('Processos', 1),
  ('Coaching', 1);
