-- 20260719165457_auto_schema_update_apply.sql
-- Nenhuma alteracao de schema real: correcao encapsula a logica de
-- atualizacao de observacoes de auditoria finalizada dentro do model
-- (AuditoriaModel::updateObservacoesRealizada), corrigindo acesso
-- ilegal a propriedade protegida do PDO feito anteriormente pelo
-- controller. Nenhuma tabela/coluna nova ou alterada.
SELECT 1;
