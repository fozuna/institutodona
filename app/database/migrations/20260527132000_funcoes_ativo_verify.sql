SELECT IF(
  EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'funcoes'
      AND COLUMN_NAME = 'ativo'
  ),
  'OK_COLUMN_EXISTS',
  'ERR_COLUMN_MISSING'
) AS verify_funcoes_ativo;
