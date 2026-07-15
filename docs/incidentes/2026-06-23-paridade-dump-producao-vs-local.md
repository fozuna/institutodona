# Paridade Entre Dump de Producao e Banco Local

## Contexto

- Dump correto analisado: `public/institutodona_dump.sql`
- Data do dump: `2026-06-23 15:44:18`
- Schema local comparado: `u357871217_institutodona`
- Objetivo: validar o dump correto, comparar com o banco local atual, checar integridade dos dados e compatibilidade com a estrutura do sistema.

## Diagnostico Atualizado

- O dump correto esta praticamente alinhado com o banco local atual.
- A comparacao estrutural atualizada apontou:
  - `0` tabelas ausentes no dump
  - `0` colunas ausentes no dump
  - `1` indice ausente no dump
  - `0` chaves estrangeiras ausentes no dump
- O unico diff estrutural restante e o indice:
  - `colaboradores.ux_colaboradores_email`

## Evidencias

### Paridade estrutural

- O utilitario de paridade confirmou `50` verificacoes com `0` faltantes.
- O dump contem corretamente os elementos esperados do sistema atual:
  - `schema_migrations`
  - auditoria expandida
  - `departamento_clientes`
  - `catalogo_grupo_sync_logs`
  - `manuais`
  - `manual_filial_links`
  - `manual_portal_tokens`
  - `indicador_eventos`
  - `indicador_responsavel`
  - `treinamentos`
  - `treinamentos_agenda`
  - `treinamento_setores`
  - `treinamento_funcoes`
  - `treinamento_colaboradores`
  - `treinamento_participantes`
  - `treinamento_auditoria_logs`
  - `treinamento_export_cache`

### Diferenca estrutural residual

- O comparador gerou apenas um item em `missing_indexes`:
  - `colaboradores|ux_colaboradores_email|0|BTREE|email`
- O SQL gerado automaticamente para sincronismo estrutural foi:

```sql
CREATE UNIQUE INDEX `ux_colaboradores_email` ON `colaboradores` (`email`);
```

### Compatibilidade do dump

- O dump foi lido e carregado em schema temporario sem erros de DDL.
- Estatisticas da carga em schema temporario:
  - `274` statements executados com sucesso
  - `1` statement com warning
- O warning ocorreu em `pdca_tasks.status`:
  - `SQLSTATE[01000]: Warning: 1265 Data truncated for column 'status' at row 111`
- O lote afetado inicia com:
  - `INSERT INTO pdca_tasks ... (373, 2, 'Baixa utilização da perfuratriz', ...)`

## Integridade dos Dados do Dump

### Checagens aprovadas

- `0` setores orfaos de departamentos
- `0` colaboradores orfaos de funcoes
- `0` orfaos em `departamento_clientes.departamento_id`
- `0` orfaos em `departamento_clientes.cliente_id`
- `0` duplicidades em `departamento_clientes`
- `0` treinamentos com `cliente_id` nulo
- `0` treinamentos com `cliente_id` orfao
- `0` treinamentos com `departamento_id` orfao
- `0` emails duplicados em `colaboradores` no dump, o que indica compatibilidade pratica para adicionar o indice unico ausente

### Anomalias encontradas no dump

- `1` funcao orfa de setor:
  - `funcoes.id = 29`
  - `nome = DIRETOR`
  - `setor_id = 12`
- `1` warning de compatibilidade em `pdca_tasks.status`

## Comparacao de Dados Com o Banco Local

- As contagens de dados entre dump e banco local nao sao identicas, indicando ambientes com bases operacionais diferentes.
- Exemplos:
  - `clientes`: dump `9`, local `285`
  - `departamentos`: dump `29`, local `238`
  - `setores`: dump `79`, local `232`
  - `funcoes`: dump `200`, local `216`
  - `colaboradores`: dump `995`, local `207`
  - `auditorias`: dump `94`, local `19`
  - `indicadores`: dump `33`, local `203`
  - `treinamentos`: dump `10`, local `16`
  - `treinamentos_agenda`: dump `11`, local `10`
  - `departamento_clientes`: dump `37`, local `248`
- Essas diferencas nao representam, por si so, erro estrutural. Elas mostram apenas que o dump correto e o banco local atual nao refletem o mesmo volume operacional.

## Correcoes Aplicadas Nesta Avaliacao

- Reexecucao completa da avaliacao usando o dump correto inserido recentemente.
- Comparacao estrutural atualizada entre dump e banco local.
- Validacao de integridade do dump em schema temporario.
- Confirmacao de compatibilidade do indice unico ausente em `colaboradores.email`, sem duplicidade de dados no dump.
- Atualizacao do utilitario `app/tests/production_dump_parity_report.php` para refletir o schema atual do sistema.

## Correcoes Recomendadas Para o Dump

- Adicionar o indice ausente:

```sql
CREATE UNIQUE INDEX `ux_colaboradores_email` ON `colaboradores` (`email`);
```

- Corrigir a funcao orfa `DIRETOR`:
  - revisar se o `setor_id = 12` deveria existir no dump
  - se o setor foi removido indevidamente, restaurar o setor correto
  - se a funcao estiver sem setor valido, remapear para um setor existente antes de reativar FKs mais estritas

- Revisar o lote legado de `pdca_tasks` com warning em `status`, porque embora o dump seja quase totalmente compativel, esse statement ainda sinaliza problema de normalizacao ou encoding em pelo menos um bloco de dados.

## Conclusao

- O dump correto esta estruturalmente compativel com o sistema atual.
- A avaliacao completa usando o recurso correto confirmou que o problema anterior nao era o processo de analise, e sim o uso de um dump antigo.
- Restaram apenas tres pontos de atencao:
  - `1` indice ausente em `colaboradores.email`
  - `1` funcao orfa de setor
  - `1` warning de compatibilidade em `pdca_tasks.status`
- Fora isso, o dump atual esta aderente ao schema do sistema e pode ser usado como base valida para analise e reconciliacao.
