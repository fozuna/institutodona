-- 20260811203144_auto_schema_update_apply.sql
-- Nenhuma alteracao de schema real: o hook de pre-commit dispara sempre que um
-- arquivo app/models/*Model.php e alterado, mesmo sem DDL (heuristica ampla,
-- sem diff real de schema). Esta alteracao tocou ManualPortalTokenModel.php
-- apenas para (a) parar de desativar tokens anteriores da empresa em issue()
-- e (b) adicionar revoke()/listByEmpresa(), usando exclusivamente colunas ja
-- existentes (ativo, expira_em, ja NULL-able). Item 11 do backlog de auditoria.
-- Nenhuma tabela ou coluna nova, alterada ou removida.
SELECT 1;
