## Migração SQL

Arquivos:

- `20260413_avaliacoes_publicas_e_potenciais_apply.sql`
- `20260413_avaliacoes_publicas_e_potenciais_verify.sql`
- `20260413_avaliacoes_publicas_e_potenciais_rollback.sql`

## Ordem de execução

1. Execute o script de aplicação.
2. Execute o script de verificação.
3. Revise os resultados esperados:
   - contagens de colunas e índices iguais ao esperado
   - nenhuma linha inválida nos checks de dados
   - `avaliacoes_publicas.expiracao` nula para links permanentes
   - `avaliacoes_publicas.slug` preenchido e único para links permanentes
4. Em caso de necessidade controlada de reversão, execute o rollback.

## Observações

- Os scripts são idempotentes para colunas, índices e FKs.
- Em MySQL, `ALTER TABLE` e outras DDLs fazem commit implícito.
- As correções de dados usam `START TRANSACTION` / `COMMIT`.
- O rollback remove a tabela `avaliacoes_publicas` e os novos campos de rastreabilidade em `avaliacoes`.
- O índice `idx_avaliacoes_cliente_id` não é removido no rollback porque já é útil e pode vir de migração anterior.
- O link público permanente atual usa `slug` e rota estável `/avaliar/{slug}`.
