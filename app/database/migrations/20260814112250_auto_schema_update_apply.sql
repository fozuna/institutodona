-- 20260814112250_auto_schema_update_apply.sql
-- Item 17: TarefaModel.php ganhou metodos novos (count()/paginate()) e uma
-- constante extra (PRIORIDADE_VALUES) para filtros/ordenacao/paginacao da
-- listagem de Tarefas. Nenhuma alteracao de DDL - ensureTable() (CREATE
-- TABLE tarefas) permanece exatamente igual. Placeholder no-op gerado pela
-- heuristica do pre-commit (qualquer *Model.php alterado sem *_apply.sql
-- novo), mesmo padrao ja usado antes neste repositorio para o mesmo tipo de
-- mudanca (ver 20260507204551_auto_schema_update_apply.sql, commit
-- c42750c "feat(cronograma): filtros e ordenacao com testes").
SELECT 1;
