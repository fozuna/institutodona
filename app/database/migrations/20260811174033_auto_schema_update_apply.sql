-- 20260811174033_auto_schema_update_apply.sql
-- Nenhuma alteracao de schema real: o hook de pre-commit dispara sempre que
-- um arquivo app/models/*Model.php e alterado, mesmo sem DDL (heuristica
-- ampla, sem diff real de schema). Esta alteracao tocou IndicadorModel.php e
-- IndicadorEventoModel.php apenas para remover a validacao fixa de
-- "percentual > 100" e delegar o parsing de decimais para
-- App\Core\DecimalParser::parse() (itens 06 e 15a do backlog de auditoria).
-- Nenhuma tabela ou coluna nova, alterada ou removida.
SELECT 1;
