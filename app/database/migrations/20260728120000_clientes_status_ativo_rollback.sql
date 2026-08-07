DROP TABLE IF EXISTS cliente_status_logs;

-- A coluna clientes.ativo NÃO é removida no rollback: ela já existia antes desta
-- migration via auto-heal (ClienteModel::ensureColumns()) e é lida por outras
-- funcionalidades já em produção (ex.: ColaboradorModel ao validar ativação de
-- colaborador em cliente inativo). Removê-la quebraria esses fluxos existentes.
