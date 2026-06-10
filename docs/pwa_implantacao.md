# Implantacao PWA

## Objetivo

Disponibilizar uma interface PWA (Progressive Web App) integrada ao sistema existente, reutilizando autenticacao, banco, APIs, regras de negocio e RBAC.

## Requisitos de infraestrutura

- HTTPS obrigatorio para instalacao e Service Worker (producao).
- O DocumentRoot deve apontar para `public_html/`.
- Cache/headers: manter os assets estaticos acessiveis em `/pwa/*` e `/assets/*`.

## Estrutura criada

- `public_html/pwa/manifest.webmanifest`
- `public_html/pwa/sw.js`
- `public_html/pwa/pwa.js`
- `public_html/pwa/pwa.css`
- `public_html/pwa/offline.html`
- `app/views/layouts/pwa.php`
- `app/controllers/PwaController.php`
- `app/views/pwa/*`

## Rotas

- `/pwa/login`
- `/pwa/dologin`
- `/pwa/logout`
- `/pwa/dashboard`
- `/pwa/tratamentos`
- `/pwa/balancos`
- `/pwa/auditorias`
- `/pwa/oficinas`
- `/pwa/indicadores`
- `/pwa/indicadores/historico`
- `/pwa/indicadores/graficos`
- `/pwa/agenda`
- `/pwa/planoacao`
- `/pwa/tarefas`
- `/pwa/cronogramas`
- `/pwa/avaliacoes`

As rotas acima sao resolvidas por rewrite no `.htaccess` do `public_html/`.

## Controle de acesso por plataforma

Foi adicionada a coluna `usuarios.platform_access` com valores:

- `WEB`
- `PWA`
- `WEB_PWA`

Aplicacao:

- Login WEB valida `Auth::canUseWeb()`.
- Login PWA valida `Auth::canUsePwa()`.
- Rotas PWA exigem sessao e validacao do campo antes de renderizar.

## Offline e cache

- O Service Worker faz precache do shell do app (manifest, css, js, offline.html e assets base).
- Para navegacao em `/pwa/*`, usa estrategia network-first com fallback para cache/offline.
- Para `/assets/*` e `/pwa/*`, usa cache-first.

## Sincronizacao

- Quando offline, submits POST no contexto do PWA (sem campos de senha) sao enfileirados em `localStorage`.
- Ao reconectar, o PWA tenta reenviar automaticamente o queue.

## Push Notifications (preparacao)

- O Service Worker ja suporta `push` e `notificationclick`.
- Para habilitar envio real, sera necessario configurar backend de assinaturas e VAPID.

## Checklist de validacao

- Acessar `/pwa/login` e instalar em Android/iOS/desktop.
- Testar offline: abrir uma pagina PWA, desligar rede, recarregar e validar fallback.
- Testar platform_access:
  - usuario WEB: bloqueado no PWA.
  - usuario PWA: bloqueado no WEB.
  - usuario WEB_PWA: acessa ambos.
- Testar RBAC: usuario sem permissao de modulo deve receber negacao padronizada.
