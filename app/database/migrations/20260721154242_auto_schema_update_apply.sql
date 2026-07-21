-- 20260721154242_auto_schema_update_apply.sql
-- Nenhuma alteracao de schema real: TreinamentoModel.php passou a validar
-- setor_ids/funcao_ids pelo escopo da empresa (cliente_id), via
-- SetorModel/FuncaoModel::activeByCliente() ja existentes, em vez do
-- departamento_id principal do treinamento. Sem novas colunas/tabelas.
SELECT 1;
