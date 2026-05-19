## Incidente: Avaliações públicas não avançam após a Etapa 1

### Sintoma

Ao acessar a avaliação pública por link do tipo:

- `.../index.php?route=avaliacao-publica/open&slug=...`

o usuário preenche a Etapa 1 e clica em **Continuar**, porém o fluxo não avança para a Etapa 2.

### Causa raiz

O formulário da Etapa 1 estava sendo renderizado com `action` apontando para:

- `https://{host}/index.php`

sem preservar a query string `?route=avaliacao-publica/open&slug=...`.

Como o roteamento do sistema depende do parâmetro `route`, o POST era recebido pelo front controller sem rota, caindo no default (`auth/login`). Na prática:

- O backend da avaliação pública não recebia o POST da Etapa 1.
- Não havia persistência/avanço, pois a requisição não chegava no fluxo correto.

### Evidências típicas no navegador (DevTools)

- **Network**: requisição `POST /index.php` (sem `route=...`) retornando HTML da tela de login.
- **Console**: sem erros de JavaScript relevantes (o bloqueio é de roteamento/URL do form).
- **Status**: normalmente `200` com HTML de login (ou redirecionamento dependendo do ambiente).

### Correção aplicada

A URL de ação do formulário passou a ser composta com base em `REQUEST_URI` quando disponível, preservando path + query string (incluindo `route` e `slug`).

Arquivos alterados:

- `app/controllers/PublicAvaliacoesController.php`
  - `currentUrl()` agora utiliza `$_SERVER['REQUEST_URI']` quando presente e iniciado por `/`.

### Testes executados

- Teste automatizado local (smoke) cobrindo:
  - Renderização do Step 1 no endpoint fixo `/public/avaliacoes.php`.
  - `action` em HTTPS quando `X-Forwarded-Proto=https`.
  - Renderização do Step 1 quando servido via `/index.php?route=avaliacao-publica/open&slug=...` preservando query no `action`.
  - Avanço Step 1 → Step 2.
  - Finalização gerando avaliação, redirecionando com assinatura de PDF e retorno de `%PDF` no download.

Arquivo:

- `app/tests/public_avaliacao_smoke.php`

### Checklist de validação em homologação

- Acessar `.../index.php?route=avaliacao-publica/open&slug=...`.
- Preencher Etapa 1 e clicar **Continuar**.
- Confirmar em Network que o POST aponta para a mesma URL (incluindo `route` e `slug`).
- Confirmar renderização da Etapa 2.
- Finalizar e validar:
  - criação do registro em `avaliacoes`
  - geração/download do PDF (quando aplicável)

