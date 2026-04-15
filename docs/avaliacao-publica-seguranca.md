## Avaliação Pública Isolada

Implementação do endpoint público de avaliação com acesso anônimo e isolamento do restante do sistema.

### Endpoint público

- Página pública fixa: `/public/avaliacoes.php`
- URL final esperada: `https://{dominio}/public/avaliacoes.php`
- Base pública configurável por ambiente: `PUBLIC_AVALIACOES_STATIC_URL`
- Resolução de contexto por domínio: `clientes.dominio_publico`
- Fallbacks internos opcionais: `PUBLIC_AVALIACOES_CONTEXT_MAP`, `PUBLIC_AVALIACOES_DEFAULT_CLIENTE_ID`, `PUBLIC_AVALIACOES_DEFAULT_EMPRESA`

Esse endpoint é público, não usa autenticação e não depende de `slug`, `token`, sessão ou expiração.

### Isolamento aplicado

- Controller dedicado: `PublicAvaliacoesController`
- Resolvedor de contexto dedicado: `PublicAvaliacaoContextResolver`
- Entradas públicas fixas:
  - `public/avaliacoes.php`
  - `public_html/public/avaliacoes.php`
- Compatibilidade legada redirecionando para o endpoint fixo:
  - `public_html/public/avaliacao/index.php`
  - reescritas em `.htaccess` e `public_html/.htaccess`
- View pública standalone, sem menu administrativo, sem layout interno e sem links para áreas autenticadas

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

- O formulário público funciona sem sessão autenticada
- O acesso é sempre pelo mesmo caminho fixo `/public/avaliacoes.php`
- Não existe geração de link por botão, `slug`, `token`, JWT ou qualquer identificador dinâmico no acesso
- Não existe lógica de expiração, TTL ou invalidação temporal
- O contexto da empresa é resolvido internamente pelo domínio ou por configuração backend
- Usuários anônimos permanecem restritos ao formulário público
- Não há renderização do layout interno do sistema
- Rate limit leve por IP, método, ação e user-agent continua ativo

### Exportação de PDF

- Geração em A4 usando `dompdf` a partir de um template HTML dedicado
- Arquivo persistido em `storage/pdfs/avaliacoes`
- Geração automática do PDF após conclusão da avaliação pública
- Download permanente disponível na tela interna de consulta da avaliação
- Download público imediato disponível após submissão, protegido por assinatura HMAC do host e `avaliacao_id`
- Rota administrativa: `index.php?route=avaliacoes/relatorio_pdf&id={id}`
- Prévia HTML para inspeção visual: `index.php?route=avaliacoes/relatorio_pdf&id={id}&preview=1`

### Validação realizada

- Teste local sem sessão autenticada garantindo renderização da página pública fixa
- Teste local confirmando múltiplos envios consecutivos pelo mesmo endpoint fixo
- Teste local confirmando criação da avaliação interna sem qualquer `slug` ou `token`
- Teste automatizado confirmando geração de PDF `%PDF`, cache em disco e download público assinado
- Teste automatizado confirmando presença das seções principais na prévia HTML do PDF
- Teste da listagem administrativa confirmando abertura/cópia do endpoint fixo
