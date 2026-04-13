## Avaliação Pública Isolada

Implementação do endpoint público de avaliação com acesso anônimo e isolamento do restante do sistema.

### Endpoint público

- Página pública: `/public_html/public/avaliacao/{token}`
- API pública de validação: `/public_html/public/avaliacao/api/validate/{token}`
- Base pública configurável por ambiente: `PUBLIC_EVALUATION_BASE_URL`
- Fallback portátil entre ambientes: `/index.php?route=avaliacao-publica/open&token={token}`

Essas rotas não passam pelo front controller autenticado do sistema interno e não usam `requireLogin()`.

### Isolamento aplicado

- Controller dedicado: `AvaliacaoPublicaController`
- Entrada dedicada: `public_html/public/avaliacao/index.php`
- Rewrite dedicada em `public_html/public/avaliacao/.htaccess`
- Roteamento local dedicado em `public_html/router.php`
- Rota pública fallback no front controller: `avaliacao-publica/open`
- View pública standalone, sem menu administrativo, sem layout interno e sem links de navegação para áreas autenticadas

### Headers de segurança

O endpoint público envia:

- `X-Frame-Options: DENY`
- `Content-Security-Policy` restritiva
- `Referrer-Policy: no-referrer`
- `X-Content-Type-Options: nosniff`
- `X-Robots-Tag: noindex, nofollow, noarchive`
- `Permissions-Policy` bloqueando recursos desnecessários
- `Cache-Control: no-store, no-cache, must-revalidate, max-age=0`

Além disso, a view pública usa meta `robots` para não indexação.

### Garantias funcionais

- O link público funciona sem sessão autenticada
- O link gerado pelo botão superior é standalone e não depende de `cliente_id`, `avaliacao_id` prévio ou histórico de clientes
- Usuários anônimos permanecem restritos à página pública e à API pública de validação
- Não há renderização do layout interno do sistema
- Não há menu, navegação lateral ou atalhos para outras funcionalidades
- O token UUID controla o escopo do acesso
- Rate limit por IP e método continua ativo

### Validação realizada

- Teste local sem sessão autenticada garantindo renderização da página pública
- Teste local sem sessão autenticada garantindo resposta da API pública
- Teste local de geração standalone sem criar avaliação interna
- Teste local de render garantindo fallback de cópia no frontend
- Verificação remota:
  - `/public/avaliacao/...` pode não existir conforme a configuração do host
  - `/public_html/public/avaliacao/...` respondeu como página pública sem exigir login
