-- 20260908114347_auto_schema_update_apply.sql
-- Item 03: ManualModel.php mudou (correcao do criterio de sucesso de update(),
-- novo metodo updateWithFilialLinks() e memoizacao de ensureTable()/
-- ensureLinkTable()). Nenhuma alteracao de DDL - as definicoes de
-- CREATE TABLE IF NOT EXISTS em ensureTable()/ensureLinkTable() permanecem
-- byte a byte as mesmas de antes, so passaram a rodar no maximo uma vez por
-- processo. Placeholder no-op gerado pela heuristica do pre-commit (qualquer
-- *Model.php alterado sem *_apply.sql novo), mesmo padrao ja usado nos itens
-- anteriores desta sprint (Tarefas, Colaboradores, Dashboard).
SELECT 1;
