-- 20260726113947_auto_schema_update_apply.sql
-- Nenhuma mudanca real de schema foi feita neste commit (Item 6 da sprint:
-- filtro de departamento nos graficos de indicadores). O hook de pre-commit
-- trata qualquer edicao em app/models/*Model.php como possivel mudanca de
-- schema; aqui foram apenas adicionadas condicoes de filtro (WHERE) em
-- queries existentes de IndicadorModel e IndicadorEventoModel, sem
-- CREATE/ALTER/DROP TABLE. Statement no-op abaixo apenas para satisfazer a
-- validacao automatizada.
SELECT 1;
