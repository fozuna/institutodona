-- 20260814125152_auto_schema_update_apply.sql
-- Item 15b: ColaboradorModel.php ganhou whitelist de ordenacao (SORT_COLUMNS)
-- e um parametro sort/dir opcional em paginatedByClientesWithFilters().
-- Nenhuma alteracao de DDL - ensureTable() permanece exatamente igual.
-- Placeholder no-op gerado pela heuristica do pre-commit (qualquer
-- *Model.php alterado sem *_apply.sql novo), mesmo padrao ja usado antes
-- neste repositorio (ver 20260507204551_auto_schema_update_apply.sql e
-- 20260814112250_auto_schema_update_apply.sql, ambos para o mesmo tipo de
-- mudanca: filtros/ordenacao sem DDL).
SELECT 1;
