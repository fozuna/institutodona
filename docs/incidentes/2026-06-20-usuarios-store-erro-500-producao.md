# Incidente: `usuarios/store` com erro 500 em producao

## Diagnostico

- O fluxo de `index.php?route=usuarios/store` envia o campo `platform_access` no formulario de cadastro.
- O controller [`UsuariosController`](file:///c:/laragon/www/institutodona/app/controllers/UsuariosController.php) repassa esse valor para [`UsuarioModel::create()`](file:///c:/laragon/www/institutodona/app/models/UsuarioModel.php).
- Antes da correcao, o model sempre executava:

```sql
INSERT INTO usuarios (nome, email, senha_hash, tipo_acesso, id_cliente, platform_access)
```

- O dump de referencia de producao em [`public/institutodona_dump.sql`](file:///c:/laragon/www/institutodona/public/institutodona_dump.sql) mostra que a tabela `usuarios` nao possui a coluna `platform_access`.
- A migration responsavel por essa coluna existe em [`20260610150000_usuarios_platform_access_apply.sql`](file:///c:/laragon/www/institutodona/app/database/migrations/20260610150000_usuarios_platform_access_apply.sql).
- O dump tambem nao contem a tabela `schema_migrations`, o que indica ausencia de rastreabilidade do runner de migrations no ambiente publicado.

## Causa raiz

- Divergencia entre o schema exigido pelo codigo atual e o schema representado pelo dump de producao.
- O codigo atualizado exige `usuarios.platform_access`, mas o dump de producao ainda possui:

```sql
CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `tipo_acesso` enum('instituto','cliente','cliente_admin','reader','consultor') NOT NULL DEFAULT 'cliente',
  `id_cliente` int DEFAULT NULL
)
```

## Objetos comparados

### Dump/producao

- `usuarios`: existe, sem `platform_access`
- `usuario_empresas`: existe
- `clientes`: existe com `is_matriz`, `matriz_id`, `ativo`, `acesso_restrito`
- `schema_migrations`: ausente no dump

### Desenvolvimento

- `usuarios`: possui `platform_access`
- `usuario_empresas`: existe com indices e FKs
- `clientes`: possui `is_matriz`, `matriz_id`, `ativo`, `acesso_restrito` e `dominio_publico`
- `schema_migrations`: existe e registra a migration `20260610150000_usuarios_platform_access_apply.sql`

## Correcao aplicada no codigo

- [`UsuarioModel`](file:///c:/laragon/www/institutodona/app/models/UsuarioModel.php) agora verifica a existencia da coluna `platform_access` antes de montar o `INSERT` e o `UPDATE`.
- [`UsuariosController`](file:///c:/laragon/www/institutodona/app/controllers/UsuariosController.php) passou a capturar excecoes do fluxo de cadastro/edicao e registrar erro explicito no log.
- [`public_html/index.php`](file:///c:/laragon/www/institutodona/public_html/index.php) passou a criar o diretorio de lock do auto-migrate ou usar `sys_get_temp_dir()` como fallback.
- [`docker-entrypoint.sh`](file:///c:/laragon/www/institutodona/docker-entrypoint.sh) agora executa `migrate.php`, valida `migrate_status.php` e aborta o start do container se ainda houver pendencias ou checksum mismatch.

## Correcao recomendada no banco de producao

Executar:

```bash
php app/database/migrate.php
php app/database/migrate_status.php
```

Se nao houver acesso ao runner, aplicar ao menos a migration:

```sql
ALTER TABLE usuarios
  ADD COLUMN platform_access ENUM('WEB','PWA','WEB_PWA') NOT NULL DEFAULT 'WEB_PWA' AFTER id_cliente;

UPDATE usuarios
SET platform_access = 'WEB_PWA'
WHERE platform_access IS NULL OR platform_access = '';
```

## Validacoes pos-correcao

### Banco

```sql
SHOW CREATE TABLE usuarios;
SHOW CREATE TABLE usuario_empresas;
SHOW CREATE TABLE clientes;
SELECT version FROM schema_migrations ORDER BY version;
```

### Aplicacao

- Criar usuario `cliente_admin` vinculado a uma empresa
- Confirmar redirecionamento sem erro 500
- Confirmar persistencia em `usuarios`
- Confirmar vinculos em `usuario_empresas`
- Validar login do usuario criado

## Logs para confirmar em producao

### PHP/Apache no servidor

```bash
tail -n 200 /var/log/apache2/error.log
tail -n 200 /var/log/php8.2-fpm.log
```

### Container/EasyPanel

```bash
docker logs --tail 200 <container>
php app/database/migrate_status.php
```

## Prevencao

- Nao promover codigo sem garantir `pending: []` em `schema_migrations`.
- Falhar o start da aplicacao quando houver migrations pendentes.
- Manter dump e base publicados com paridade validada antes do deploy funcional.
