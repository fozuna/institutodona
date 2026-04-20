# Ajustes de UI/UX do Menu Lateral

## Objetivo

Eliminar a redundancia do toggle hamburguer e corrigir o cabecalho lateral para que a marca `SisDona` volte a aparecer abaixo da logo, com alinhamento consistente.

## Decisao de Design

- O toggle duplicado foi removido do cabecalho interno da sidebar.
- Foi mantido apenas o toggle da barra superior da area de trabalho.

### Motivo

- o controle permanece acessivel com o menu aberto, recolhido ou oculto em mobile
- a area de marca na sidebar volta a ficar dedicada a logo + nome do sistema
- a experiencia fica mais previsivel, sem dois pontos concorrentes para a mesma acao

## Alteracoes Realizadas

### Layout

- `app/views/layouts/main.php`
  - remove o toggle duplicado da sidebar
  - organiza o cabecalho da marca em bloco vertical (`sidebar-brand`)
  - mantem o toggle unico na barra superior
  - remove o rotulo visual `Menu lateral` da topbar para evitar disputa com o titulo da tela

### Estilos

- `public_html/assets/css/theme.css`
  - adiciona a classe `sidebar-brand`
  - adiciona a classe `sidebar-brand-logo`
  - ajusta estados de colapso para manter o bloco da marca alinhado
  - preserva a proporcao original da logo com `width` fluida, `height: auto` e `object-fit: contain`
  - preserva responsividade em desktop e mobile

### Comportamento

- `public_html/assets/js/app.js`
  - continua controlando colapso/expansao via `localStorage`
  - mantem sincronizacao de `aria-expanded`, `aria-label` e estados mobile/desktop

## Validacao

- smoke test de layout: `app/tests/layout_sidebar_toggle_smoke.php`
- verificacoes cobertas:
  - apenas um toggle renderizado
  - atributos ARIA presentes
  - overlay mobile presente
  - persistencia em `localStorage`

## Evidencias Visuais

### Antes

- referencia: imagem enviada pelo usuario mostrando:
  - toggle duplicado
  - quebra visual do texto `SisDona`

### Depois

- captura visivel: `c:\Users\FABIOO~1.MAD\AppData\Local\Temp\trae\screenshots\sidebar-toggle-after.png`
- captura full page: `c:\Users\FABIOO~1.MAD\AppData\Local\Temp\trae\screenshots\sidebar-toggle-after-full.png`

## Causas Raiz Identificadas

- a logo horizontal estava sendo comprimida visualmente no estado recolhido por falta de limites proporcionais especificos para esse contexto
- o texto `Menu lateral` na topbar criava ruido visual e aproximava excessivamente o cabecalho do conteudo principal
- a solucao aplicada separa melhor a hierarquia:
  - marca no menu lateral
  - controle unico na topbar
  - titulo da tela livre na area de conteudo

## Observacao

- Nesta sessao houve validacao estrutural, responsiva e visual no navegador integrado.
- Nao houve execucao manual real em multiplos navegadores externos; essa homologacao final ainda depende de validacao manual adicional.
