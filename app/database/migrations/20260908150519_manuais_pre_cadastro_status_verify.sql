SELECT 'col_status' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'status';

SELECT 'col_status_nao_nulo' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'status' AND is_nullable = 'NO';

SELECT 'col_status_default' AS item, column_default AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'status';

SELECT 'col_publicado_em' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'publicado_em' AND is_nullable = 'YES';

SELECT 'col_arquivo_nullable' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'arquivo' AND is_nullable = 'YES';

SELECT 'col_tipo_arquivo_nullable' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'tipo_arquivo' AND is_nullable = 'YES';

SELECT 'col_tamanho_not_null_default_0' AS item, COUNT(*) AS ok
FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND column_name = 'tamanho' AND is_nullable = 'NO' AND column_default = '0';

SELECT 'idx_status' AS item, COUNT(*) AS ok
FROM information_schema.statistics
WHERE table_schema = DATABASE() AND table_name = 'manuais' AND index_name = 'idx_manuais_status';

SELECT 'status_invalido' AS item, COUNT(*) AS deve_ser_zero
FROM manuais
WHERE status NOT IN ('pre_cadastro', 'publicado');

SELECT 'status_nulo_ou_vazio' AS item, COUNT(*) AS deve_ser_zero
FROM manuais
WHERE status IS NULL OR status = '';

SELECT 'registros_publicados' AS item, COUNT(*) AS total
FROM manuais
WHERE status = 'publicado';

SELECT 'registros_pre_cadastro' AS item, COUNT(*) AS total
FROM manuais
WHERE status = 'pre_cadastro';

SELECT 'legado_sem_arquivo_indevido' AS item, COUNT(*) AS deve_ser_zero
FROM manuais
WHERE status = 'publicado' AND (arquivo IS NULL OR arquivo = '' OR tipo_arquivo IS NULL OR tipo_arquivo = '');
