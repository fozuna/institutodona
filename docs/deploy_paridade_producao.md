# Deploy De Paridade Entre Desenvolvimento E Produção

## Objetivo

Garantir que:

- o código publicado em produção seja exatamente o mesmo commit homologado em desenvolvimento;
- o banco de produção possua os mesmos objetos de schema exigidos pelo código;
- o menu de `Manuais` e os fluxos correlatos funcionem sem divergência;
- exista roteiro de backup, execução, validação e rollback.

## Premissas

- Produção é promovida a partir da branch `main` via workflow [`.github/workflows/easypanel-deploy.yml`](file:///c:/laragon/www/institutodona/.github/workflows/easypanel-deploy.yml).
- O dump de referência de produção fica em [`public/institutodona_dump.sql`](file:///c:/laragon/www/institutodona/public/institutodona_dump.sql).
- O runner de migrations é [`app/database/MigrationRunner.php`](file:///c:/laragon/www/institutodona/app/database/MigrationRunner.php).

## Critério De Paridade

Produção só pode ser considerada idêntica a desenvolvimento quando:

1. O commit publicado em produção for exatamente o mesmo commit homologado em desenvolvimento.
2. O comando `php app/database/migrate_status.php` retornar `pending: []`.
3. O relatório `php app/tests/production_dump_parity_report.php` retornar `exit code 0` com novo dump de produção.
4. Os testes focados de auditorias, manuais, clientes e relatórios passarem.
5. O menu `Manuais`, o portal e os downloads funcionarem em produção.

## Arquivos Que Precisam Estar Em Produção

### Código rastreado modificado

- `app/controllers/AuditoriasController.php`
- `app/controllers/ClientesController.php`
- `app/core/SimplePdfReport.php`
- `app/core/XlsxExport.php`
- `app/models/AuditoriaArquivoModel.php`
- `app/models/ClienteModel.php`
- `app/models/PlanoAcaoTaskModel.php`
- `app/services/AvaliacaoPdfService.php`
- `app/tests/auditoria_flow_integration_test.php`
- `app/views/auditorias/editar_realizada.php`
- `app/views/avaliacoes/show_pdf.php`
- `app/views/clientes/show.php`
- `app/views/layouts/main.php`
- `public_html/.htaccess`
- `public_html/index.php`

### Código novo

- `app/controllers/ManuaisController.php`
- `app/core/ReportBranding.php`
- `app/database/migrations/20260417_auditoria_historico_apply.sql`
- `app/database/migrations/20260417_manuais_apply.sql`
- `app/database/migrations/20260417_manual_portal_tokens_apply.sql`
- `app/models/ManualModel.php`
- `app/models/ManualPortalTokenModel.php`
- `app/tests/auditorias_metrics_smoke.php`
- `app/tests/cliente_planos_resumo_smoke.php`
- `app/tests/manuais_download_permission_smoke.php`
- `app/tests/manuais_model_smoke.php`
- `app/tests/manuais_portal_scope_smoke.php`
- `app/tests/production_dump_parity_report.php`
- `app/views/manuais/create.php`
- `app/views/manuais/index.php`
- `app/views/manuais/portal.php`
- `public_html/assets/img/logovivamais.png`
- `docs/deploy_paridade_producao.md`

## Objetos De Banco Que Precisam Existir Em Produção

### Tabelas

- `schema_migrations`
- `auditoria_responsaveis`
- `auditoria_questao_responsaveis`
- `auditoria_historico`
- `manuais`
- `manual_portal_tokens`
- `faturamento_faixas`

### Colunas

- `clientes.dominio_publico`
- `avaliacoes.faturamento_faixa_id`
- `avaliacoes_publicas.faturamento_faixa_id`
- `auditoria_arquivos.descricao`
- `auditorias.lock_version`
- `auditorias.updated_at`
- `auditorias.responsavel_id`

## Dependências Entre Objetos

- `auditoria_responsaveis.auditoria_id -> auditorias.id`
- `auditoria_responsaveis.colaborador_id -> colaboradores.id`
- `auditoria_questao_responsaveis.questao_id -> auditoria_questoes.id`
- `auditoria_questao_responsaveis.colaborador_id -> colaboradores.id`
- `auditoria_historico.auditoria_id -> auditorias.id`
- `manuais.empresa_id -> clientes.id`
- `manuais.departamento_id -> departamentos.id`
- `manual_portal_tokens.empresa_id -> clientes.id`

## Ordem Obrigatória De Execução

### 1. Backup

Executar dump completo imediatamente antes do deploy:

```bash
mysqldump -h <HOST> -P <PORTA> -u <USER> -p --databases <DB> --routines --triggers --events --single-transaction > backup_pre_deploy_YYYYMMDD_HHMM.sql
```

Guardar:

- arquivo `.sql`
- timestamp
- checksum do dump

### 2. Publicar Código

Promover todos os arquivos listados neste documento no mesmo deploy.

### 3. Aplicar Migrations

Executar:

```bash
php app/database/migrate.php
php app/database/migrate_status.php
```

Confirmar `pending: []`.

### 4. Gerar Novo Dump De Produção

Executar novo backup logo após a migração e salvar como dump pós-deploy.

### 5. Validar Paridade Do Dump

Executar:

```bash
php app/tests/production_dump_parity_report.php
```

Critério:

- `exit code 0` = schema alinhado
- `exit code 1` = ainda há gap entre produção e desenvolvimento

## Testes Pós-Deploy

Executar:

```bash
php app/tests/migration_runner_status_smoke.php
php app/tests/migration_schema_validation_smoke.php
php app/tests/auditoria_flow_integration_test.php
php app/tests/manuais_model_smoke.php
php app/tests/manuais_download_permission_smoke.php
php app/tests/manuais_portal_scope_smoke.php
php app/tests/cliente_planos_resumo_smoke.php
```

Validar manualmente:

- login funcionando
- item `Manuais` no menu lateral
- botão `Ver Manuais` na tela do cliente
- `index.php?route=manuais/index`
- geração do link do portal
- `/manuais/portal/{token}`
- `/manuais/download/{id}`
- PDF e Excel com branding padronizado

## Rollback

### Rollback De Banco

Se qualquer migration falhar ou se os testes estruturais falharem:

```bash
mysql -h <HOST> -P <PORTA> -u <USER> -p < backup_pre_deploy_YYYYMMDD_HHMM.sql
```

### Rollback De Código

Republicar o último commit estável já presente em produção.

## Garantia Operacional

Não existe garantia real de “produção idêntica a desenvolvimento” sem executar:

1. deploy do mesmo commit;
2. migrations;
3. dump pós-deploy;
4. relatório de paridade;
5. testes funcionais.

Este runbook define exatamente os passos necessários para comprovar essa igualdade.
