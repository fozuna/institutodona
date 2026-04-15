## Avaliação Pública Isolada

Implementação do endpoint público de avaliação com acesso anônimo e isolamento do restante do sistema.

### Endpoint público

- Página pública permanente: `/index.php?route=avaliacao-publica/open&slug={slug}`
- API pública de validação: `/public_html/public/avaliacao/api/validate/{slug}`
- Base pública configurável por ambiente: `PUBLIC_EVALUATION_BASE_URL`
- Fallback portátil entre ambientes: `/index.php?route=avaliacao-publica/open&slug={slug}`

Essas rotas não passam pelo front controller autenticado do sistema interno e não usam `requireLogin()`.

### Isolamento aplicado

- Controller dedicado: `AvaliacaoPublicaController`
- Entrada dedicada: `public_html/public/avaliacao/index.php`
- Rewrite dedicada em `public_html/public/avaliacao/.htaccess`
- Roteamento local dedicado em `public_html/router.php`
- Rewrite raiz para URL amigável em `public_html/.htaccess`
- Geração padrão do link usando o front controller explícito `index.php?route=avaliacao-publica/open&slug=...`
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
- O mesmo link pode ser reutilizado indefinidamente em diferentes dispositivos, abas anônimas e contextos de compartilhamento
- Usuários anônimos permanecem restritos à página pública e à API pública de validação
- Não há renderização do layout interno do sistema
- Não há menu, navegação lateral ou atalhos para outras funcionalidades
- O slug permanente controla o escopo do acesso
- Rate limit por IP e método continua ativo

### Exportação de PDF

- Geração em A4 usando `dompdf` a partir de um template HTML dedicado
- Arquivo persistido em `storage/pdfs/avaliacoes`
- Geração automática do PDF após conclusão da avaliação pública
- Download permanente disponível na tela interna de consulta da avaliação
- Download público imediato disponível após submissão, protegido por assinatura HMAC do `slug` e `avaliacao_id`
- Rota administrativa: `index.php?route=avaliacoes/relatorio_pdf&id={id}`
- Prévia HTML para inspeção visual: `index.php?route=avaliacoes/relatorio_pdf&id={id}&preview=1`

### Validação realizada

- Teste local sem sessão autenticada garantindo renderização da página pública
- Teste local sem sessão autenticada garantindo resposta da API pública
- Teste local manual do endpoint amigável `/avaliar/{slug}` abrindo diretamente a etapa 1
- Geração padrão atual prioriza o endpoint explícito `index.php?route=avaliacao-publica/open&slug=...` para evitar redirecionamentos indevidos em produção
- Teste local de geração standalone sem criar avaliação interna
- Teste local de render garantindo fallback de cópia no frontend
- Teste automatizado confirmando geração de PDF `%PDF`, cache em disco e download público assinado
- Teste automatizado confirmando presença das seções principais na prévia HTML do PDF
