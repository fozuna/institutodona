# Sincronização de Schema Dev x Produção

## Escopo
- Comparação entre o schema de desenvolvimento e o dump `institutodona.sql` na raiz do projeto.
- Geração de migration de sincronização sem destruição de dados.
- Procedimento operacional para execução segura em produção.

## Artefatos gerados
- Migration de sincronização: `app/database/migrations/20260506_164117_schema_sync_from_dump_apply.sql`
- Verificação pós-migração: `app/database/migrations/20260506_164117_schema_sync_from_dump_verify.sql`
- Script de comparação automática: `app/database/schema_diff_from_dump.php`
- Relatório JSON de diff (última execução): `storage/logs/schema_diff_report_20260506_164117.json`

## Resumo das divergências encontradas
- Tabelas ausentes no dump: `3`
- Colunas ausentes no dump: `60`
- Índices ausentes no dump: `33`
- Foreign keys ausentes no dump: `14`

Tabelas ausentes principais:
- `indicador_eventos`
- `indicador_responsavel`
- `unidades_medida`

## Backup obrigatório
Antes de qualquer alteração em produção:

```bash
mysqldump -h <HOST> -P <PORT> -u <USER> -p --databases <DB> --routines --triggers --events --single-transaction > backup_pre_schema_sync_YYYYMMDD_HHMM.sql
```

Após aplicação:

```bash
mysqldump -h <HOST> -P <PORT> -u <USER> -p --databases <DB> --routines --triggers --events --single-transaction > backup_pos_schema_sync_YYYYMMDD_HHMM.sql
```

## Execução em staging (obrigatório antes de produção)
1. Aplicar migration:
```bash
php app/database/run_sql_file.php app/database/migrations/20260506_164117_schema_sync_from_dump_apply.sql
```
2. Validar estrutura:
```bash
php app/database/run_sql_file.php app/database/migrations/20260506_164117_schema_sync_from_dump_verify.sql
```
3. Validar status de migrations:
```bash
php app/database/migrate_status.php
```
4. Rodar testes críticos:
```bash
php app/tests/migration_schema_validation_smoke.php
php app/tests/migration_runner_status_smoke.php
php app/tests/indicadores_validation_unit_test.php
php app/tests/treinamento_module_integration_test.php
```

## Execução em produção
Repetir a sequência de staging, na mesma ordem.

## Rollback
Observação técnica: DDL em MySQL pode realizar commit implícito; `START TRANSACTION` não garante reversão integral de todas as alterações de schema.

Rollback recomendado:
- restaurar o backup `backup_pre_schema_sync_*.sql`.

```bash
mysql -h <HOST> -P <PORT> -u <USER> -p < backup_pre_schema_sync_YYYYMMDD_HHMM.sql
```

## Versionamento para evitar novas divergências
- Toda mudança de schema deve entrar com `*_apply.sql` + `*_verify.sql`.
- Executar `php app/database/schema_diff_from_dump.php institutodona.sql` no pipeline de release.
- Atualizar periodicamente o dump de referência e armazenar o relatório de diff nos artefatos do deploy.
