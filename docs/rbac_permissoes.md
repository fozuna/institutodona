# RBAC do Sistema

## Diagnostico da arquitetura existente

- A autenticacao continua baseada em `$_SESSION['user']`, `Auth` e `UsuarioModel`.
- O perfil continua sendo derivado de `usuarios.tipo_acesso`, sem duplicacao de tabela de roles.
- O escopo multiempresa reaproveita `usuario_empresas`, `TenantScopeResolver`, `Auth::allowedClientIds()` e `BaseModel::tenantInCondition()`.
- O frontend server-rendered continua centralizado em `app/views/layouts/main.php`.
- A auditoria continua centralizada em `AuditLogger`, com enriquecimento de contexto por usuario, perfil, IP, rota e resultado.

## Modelo aplicado

- `Role`: `instituto`, `consultor`, `cliente_admin`, `cliente` (cliente editor) e `reader` (cliente leitor).
- `Permission`: leitura, escrita e exclusao.
- `Scope`: empresa/filiais vinculadas por `usuario_empresas`, com atualizacao dinamica em toda requisicao via `Auth::refreshScope()`.

## Perfis e permissoes

### Instituto

- Acesso total a todos os modulos e rotas.
- Leitura, escrita e exclusao sem restricao de escopo.

### Consultor

- Modulos permitidos: `cadastros` operacionais e `avaliacoes`.
- Pode visualizar, criar, editar e excluir.
- Escopo restrito aos clientes vinculados.
- Modulos administrativos e corporativos permanecem bloqueados.

### Cliente Admin

- Modulos permitidos: `cadastros` operacionais, `avaliacoes` e modulos operacionais.
- Pode visualizar, criar, editar e excluir.
- Escopo restrito a empresa/matriz/filiais vinculadas.

### Cliente Editor

- Modulos permitidos: `cadastros` operacionais, `avaliacoes` e modulos operacionais.
- Pode visualizar, criar e editar.
- Exclusao negada no backend e ocultada na interface.

### Cliente Leitor

- Modulos permitidos: `cadastros` operacionais, `avaliacoes` e modulos operacionais.
- Pode apenas visualizar.
- Rotas `create`, `edit`, `store`, `update`, `delete` e equivalentes sao negadas.

## Modulos operacionais permitidos fora do Instituto

- `departamentos`
- `setores`
- `funcoes`
- `colaboradores`
- `avaliacoes`
- `indicadores`
- `agenda`
- `cronograma`
- `planoacao`
- `tarefas`
- `auditorias`
- `coaching`
- `processos`

## Modulos mantidos como administrativos

- `clientes`
- `usuarios`
- `consultores`
- `pilares`
- `metodologias`
- `aplicacoes`
- `dashboard`
- `manuais`
- `treinamentos`
- `reunioes`
- `logs`

## Modulos operacionais (permitidos conforme perfil)

- `indicadores`
- `agenda`
- `cronograma`
- `planoacao`
- `tarefas`
- `auditorias`
- `coaching`
- `processos`

## Protecoes implementadas

### Backend

- `AccessControl` centraliza:
  - mapeamento rota -> modulo;
  - classificacao rota -> leitura/escrita/exclusao;
  - matriz de capacidades por perfil;
  - mensagens padronizadas de negacao.
- `BaseController::requireLogin()` executa:
  - refresh dinamico de perfil e escopo;
  - autorizacao de rota;
  - validacao de escopo por cliente informado;
  - auditoria de acesso permitido.
- `BaseController::denyAccess()` padroniza respostas HTTP 403 e auditoria de acesso negado.

### Models e escopo

- O isolamento por empresa permanece nos models atraves de `tenantInCondition()`.
- Isso preserva bloqueio em consultas, updates e deletes mesmo sob manipulacao de URL/ID.

### Frontend

- O menu lateral usa `AccessControl::canAccessRoute()` para ocultar modulos nao permitidos.
- As listagens principais de `departamentos`, `setores`, `funcoes`, `colaboradores` e `avaliacoes` ocultam botoes de criar, editar e excluir conforme o perfil.
- O frontend continua apenas como camada de UX; a seguranca permanece no backend.

### APIs

- O RBAC foi mantido como criterio central para rotas protegidas e APIs autenticadas.
- Para endpoints autenticados por sessao ou bearer, a rota passa pelo mesmo classificador de permissao.

## Auditoria

- Login: registra usuario, perfil, cliente principal e escopo calculado.
- Logout: registra usuario, perfil, cliente principal, escopo e resultado permitido.
- Acesso permitido: registra rota, cliente resolvido e escopo vigente.
- Acesso negado: registra rota, perfil, cliente solicitado, IP, mensagem e resultado negado.
- O arquivo de auditoria continua em `storage/logs/audit.log`.

## Atualizacao dinamica

- `Auth::refreshScope()` recarrega `usuarios.tipo_acesso`, `usuarios.id_cliente` e o escopo de `usuario_empresas` a cada requisicao autenticada.
- Mudancas de perfil, empresa vinculada ou clientes herdados passam a refletir sem restart, sem deploy e sem limpeza manual.

## Banco e migracoes

- Nenhuma nova migration estrutural foi necessaria nesta fase.
- O RBAC reaproveita totalmente:
  - `usuarios.tipo_acesso`
  - `usuario_empresas`
  - `clientes.matriz_id`
  - `clientes.is_matriz`
- Como nao houve mudanca de schema, nao foi criado arquivo SQL novo apenas para formalidade.

## Testes automatizados

- `app/tests/rbac_route_matrix_unit_test.php`
  - valida matriz de acessos por perfil/modulo/acao.
- `app/tests/access_profiles_integration_test.php`
  - valida leitura, escrita, exclusao, bloqueio por perfil, manipulacao de URL, acesso cruzado entre empresas e tentativa de escalada de privilegio.
- `app/tests/helpers/access_gate_probe.php`
  - helper para exercitar o gate central com perfis e escopos simulados.

## Observacoes de manutencao

- Qualquer novo modulo deve ser classificado em `AccessControl::PREFIX_MODULES`.
- Qualquer nova rota de mutacao deve entrar em `WRITE_ACTIONS` ou `DELETE_ACTIONS`.
- Se um novo recurso usar `cliente_id` indireto, a validacao de escopo deve continuar nos models ou ser reforcada no controller apos o `find()`.
