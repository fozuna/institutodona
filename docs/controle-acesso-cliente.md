# Controle de acesso por cliente

## Objetivo

Permitir navegação completa no menu para usuários autenticados, com isolamento automático de dados por cliente e filiais associadas.

## Arquitetura

- `Auth::login` passa a materializar o escopo de clientes permitido no momento da autenticação.
- `TenantScopeResolver` resolve a hierarquia `matriz -> filiais` de forma recursiva, com fallback e filtros de elegibilidade.
- `UsuarioEmpresaModel` realiza a atribuição em lote do vínculo usuário-empresa.
- Tabela `usuario_empresas` registra origem do vínculo (`direto` ou `herdado`) para rastreabilidade.
- `BaseController` aplica fallback de escopo em rotas antes bloqueadas por perfil e registra auditoria de navegação.
- `BaseModel` centraliza utilitários de escopo:
  - `tenantInCondition`
  - `normalizeScopedClienteId`
  - `canAccessClienteId`
- Models de domínio aplicam automaticamente o escopo em leitura e escrita.

## Regras de escopo

- Usuário `instituto` mantém acesso irrestrito.
- Usuário `cliente_admin` e `cliente` possuem privilégios administrativos no próprio escopo.
- Usuário `reader` possui acesso somente leitura.
- Usuário não `instituto`:
  - visualiza somente dados com `cliente_id/id_cliente` dentro de `allowed_client_ids`;
  - pode ser vinculado a múltiplas empresas no cadastro (`id_clientes[]`);
  - ao informar cliente fora do escopo, o sistema usa fallback para o primeiro cliente permitido;
  - operações por ID passam a validar escopo no `WHERE` por cliente, inclusive em updates e deletes.
- Criação e atualização de usuário cliente:
  - seleciona uma ou mais matrizes/empresas diretas;
  - propaga automaticamente permissões para filiais elegíveis;
  - grava permissões diretas e herdadas em lote.

## Regras de segurança por perfil

- `instituto`
  - leitura e escrita sem restrição de cliente.
- `cliente_admin` e `cliente`
  - leitura e escrita permitidas apenas no escopo de empresas vinculadas.
  - escrita permitida somente em rotas de mutação com método `POST`.
- `reader`
  - permitido apenas `GET/HEAD`.
  - bloqueio de rotas de mutação mesmo quando acessadas via `GET`.

## Verificação de escopo

- Backend
  - `BaseController::requireLogin` aplica guarda por rota/método e bloqueio por perfil.
  - `BaseModel` aplica `tenantInCondition` em consultas e mutações.
- Frontend
  - interface em modo reader desabilita formulários de escrita e oculta links de mutação.
- Banco
  - tabela `usuario_empresas` mantém escopo explícito usuário-empresa.
  - chaves estrangeiras e índices reforçam integridade e desempenho do isolamento.

## Hierarquia cliente/filial

- A hierarquia é derivada de `clientes.matriz_id`.
- O escopo inclui o cliente raiz do usuário e todos os descendentes.
- O algoritmo usa percurso em profundidade sobre o grafo carregado da tabela `clientes`.
- Filiais inativas (`clientes.ativo = 0`) são excluídas da herança.
- Filiais restritas (`clientes.acesso_restrito = 1`) são excluídas da herança.

## Auditoria

- Login registra:
  - tipo de acesso
  - cliente do usuário
  - escopo calculado de clientes
- Requisições autenticadas registram:
  - rota
  - cliente resolvido por escopo
  - lista de clientes permitidos
- Ações administrativas de clientes registram:
  - rota de escrita
  - método HTTP
  - escopo de clientes ativo no momento da ação
- Visualizações/exportações por cliente registram `cliente_id` para rastreabilidade.

## Compatibilidade

- Menus e rotas antes restritas por perfil não retornam mais bloqueio textual.
- O controle passa a ser de dados (filtro), mantendo funcionalidades existentes.
- Usuário de instituto segue sem restrição de visualização.

## Migração

- Script: `app/database/migrations/20260326_tenant_scope.php`
- Ajustes:
  - índices para colunas de cliente
  - FK opcional em `clientes.matriz_id` para integridade da hierarquia
  - criação de `usuario_empresas` com FKs para `usuarios` e `clientes`
  - inclusão de `clientes.ativo` e `clientes.acesso_restrito`
