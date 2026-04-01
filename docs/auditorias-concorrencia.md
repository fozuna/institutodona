# Correção de Concorrência no Módulo de Auditorias

## Problema identificado

Em cenários de edição simultânea, o usuário recebia a mensagem:

`Erro! Não foi possível atualizar. Verifique se os dados não foram alterados por outro usuário.`

A causa raiz era a ausência de um controle otimista consistente entre fluxos de atualização completa e parcial. Em alguns caminhos, o sistema dependia apenas de `updated_at`, e em outros não validava versão antes de gravar.

## Solução implementada

1. Controle otimista por versão (`lock_version`) na tabela `auditorias`.
2. Validação de versão no backend em `updateAgendada` e `updatePartial`.
3. Incremento de versão a cada atualização bem-sucedida.
4. Bloqueio explícito com erro `concurrency_conflict` quando a versão enviada está desatualizada.
5. Inclusão do `prev_lock_version` no formulário de edição.
6. Mensagens de erro orientativas para o usuário recarregar dados antes de salvar.

## Fluxo de atualização esperado

1. Usuário abre a edição e recebe o snapshot com `updated_at` e `lock_version`.
2. Ao salvar, o frontend envia `prev_lock_version`.
3. O backend executa `UPDATE ... WHERE id = :id AND lock_version = :prev_lock_version`.
4. Se nenhum registro for afetado:
   - backend verifica versão atual;
   - retorna conflito de concorrência quando houver divergência.

## Testes de validação

- `app/tests/auditoria_concurrency_unit_test.php`
  - garante incremento de versão;
  - bloqueia update com versão stale;
  - valida retorno de `concurrency_conflict`.

- `app/tests/auditoria_flow_integration_test.php`
  - valida cenário de conflito de versão no fluxo real de auditoria.

## Procedimentos de prevenção para edição multiusuário

1. Sempre transportar `lock_version` no payload de update.
2. Nunca atualizar registros críticos sem cláusula de versão otimista.
3. Padronizar respostas de conflito com código semântico (`concurrency_conflict`).
4. Exibir mensagem amigável orientando reload dos dados.
5. Incluir cenários de concorrência em testes de regressão.
6. Registrar eventos de conflito para monitoramento operacional.
