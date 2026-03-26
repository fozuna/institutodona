# Manual do Usuário - Auditorias

## Acesso ao módulo

- No menu lateral, clique em **Auditorias**.
- A tela inicial exibe filtros, listagem paginada e ações por status.

## Cadastro de auditoria

1. Clique em **Cadastrar Nova Auditoria**.
2. Selecione a empresa.
3. Selecione o setor da empresa.
4. Selecione o responsável em dropdown dinâmico de colaboradores filtrado pelo setor.
5. Informe a data em `DD/MM/YYYY` (datas futuras são permitidas).
6. Preencha pergunta, objetivo e referência esperada.
7. Salve o registro.

## Execução da auditoria

- Apenas auditorias com status **Agendada** exibem o botão **Auditar**.
- Ao auditar:
  - informe **AVALIAÇÃO** (obrigatória, mínimo 50 caracteres);
  - informe **OBS** se necessário;
  - o sistema registra `Data de Realização` automaticamente;
  - o status muda para **Realizada**.

## Edição e exclusão

- **Editar**: disponível apenas para status **Agendada**.
- **Excluir**: usa confirmação modal e soft delete.
- Exclusão é bloqueada quando houver vínculo da auditoria com relatórios.

## Filtros e ordenação

- Filtros por empresa, setor, responsável, status e período.
- Busca global por pergunta, objetivo e nomes relacionados.
- Ordenação por data, empresa, setor, responsável e status.
- Paginação de 10 registros por página.

## Permissões

- `consultor`: pode criar, editar, excluir e auditar.
- `instituto`: pode criar, editar, excluir e auditar.
- `empresa` (`cliente` e `cliente_admin`): somente visualização das auditorias de suas empresas.
- `reader`: somente leitura.
- Em tentativa de escrita sem permissão, o sistema retorna mensagem de acesso negado.
- escopo por empresa é sempre aplicado para perfis não instituto.
