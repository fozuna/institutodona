-- 20260908122001_auto_schema_update_apply.sql
-- Item 02: AuditoriaModel.php mudou (buildListFilterConditions() passou a
-- aceitar "setores" como array, via IN(...), em vez de "setor" unico).
-- Nenhuma alteracao de DDL - o filtro multi-setor e' inteiramente aplicado
-- em memoria/SQL de leitura, sem tocar no schema de `auditorias`/`setores`.
-- Placeholder no-op gerado pela heuristica do pre-commit (qualquer
-- *Model.php alterado sem *_apply.sql novo), mesmo padrao ja usado nos
-- itens anteriores desta sprint.
SELECT 1;
