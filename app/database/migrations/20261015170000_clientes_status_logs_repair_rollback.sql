-- Esta migration apenas repara um objeto que já deveria existir desde
-- 20260728120000_clientes_status_ativo_apply. Não há rollback: remover a
-- tabela aqui teria o mesmo efeito colateral já documentado naquela
-- migration (quebra a inativação de clientes). Para reverter de verdade,
-- use o rollback original: 20260728120000_clientes_status_ativo_rollback.sql
SELECT 1;
