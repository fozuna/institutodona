SET @schema_name = DATABASE();

SET @sql = (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = @schema_name
        AND TABLE_NAME = 'clientes'
        AND COLUMN_NAME = 'ativo'
    ),
    'SELECT 1',
    'ALTER TABLE clientes ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1'
  )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS cliente_status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_id INT NOT NULL,
  old_ativo TINYINT(1) NULL,
  new_ativo TINYINT(1) NOT NULL,
  justificativa TEXT NOT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  changed_by INT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  KEY idx_cliente_status_logs_cliente (cliente_id),
  KEY idx_cliente_status_logs_changed_at (changed_at),
  CONSTRAINT fk_cliente_status_logs_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
