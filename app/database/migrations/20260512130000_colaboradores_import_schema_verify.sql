SELECT 'table_colaboradores' AS item, COUNT(*) AS ok
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'colaboradores';

SELECT 'col_documento' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'documento';

SELECT 'col_data_nascimento' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'data_nascimento';

SELECT 'col_celular' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND column_name = 'celular';

SELECT 'idx_cliente' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND index_name = 'idx_colaboradores_cliente';

SELECT 'idx_funcao' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND index_name = 'idx_colaboradores_funcao';

SELECT 'ux_documento' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND index_name = 'ux_colaboradores_documento';

SELECT 'ux_email' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'colaboradores' AND index_name = 'ux_colaboradores_email';

SELECT 'duplicados_documento' AS item, COUNT(*) AS duplicados
FROM (
  SELECT documento
  FROM colaboradores
  WHERE documento IS NOT NULL AND documento <> ''
  GROUP BY documento
  HAVING COUNT(*) > 1
) t;

SELECT 'duplicados_email' AS item, COUNT(*) AS duplicados
FROM (
  SELECT email
  FROM colaboradores
  WHERE email IS NOT NULL AND email <> ''
  GROUP BY email
  HAVING COUNT(*) > 1
) t;

