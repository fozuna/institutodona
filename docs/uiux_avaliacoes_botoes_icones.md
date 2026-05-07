## Objetivo

Reformular a tela **Avaliações** para reduzir ruído visual e padronizar ações com **botões de ícones** com **tooltips** (hover) e nomes acessíveis (WCAG 2.1), mantendo hierarquia de ações (primária/secundária/terciária).

## Onde foi aplicado

- Tela interna de listagem: [avaliacoes/index.php](file:///c:/laragon/www/institutodona/app/views/avaliacoes/index.php)
- Design system (tamanho e foco de ícones): [theme.css](file:///c:/laragon/www/institutodona/public_html/assets/css/theme.css)

## Mudanças de UI/UX

### Padrão de componentes

- **Botão/Link com ícone**: `icon-btn` + variações (`icon-btn--primary`, `icon-btn--muted`, `icon-btn--lg`)
- **Tooltip**: atributo `title` (hover)
- **Acessibilidade**:
  - `aria-label` em todos os controles (nome acessível)
  - texto de apoio com `.sr-only` para leitores de tela
  - foco visível com `:focus-visible` em `icon-btn` e `icon-action`
  - feedback de cópia via `aria-live` (status invisível)

### Hierarquia visual

- **Primária (alto destaque)**: fundo `brand-red` (`icon-btn--primary`)
- **Secundária/Terciária**: fundo neutro (`icon-btn--muted`) com cor do ícone por contexto (ex.: `text-brand-red`)

## Mapa de ações → ícones (justificativa)

| Ação | Ícone (Feather) | Justificativa (convenção) |
|---|---|---|
| Nova avaliação | `plus` | Padrão universal para “criar/adicionar” |
| Abrir formulário/link | `external-link` | Padrão para “abrir em nova aba / sair do contexto” |
| Copiar link | `copy` | Padrão para “copiar para área de transferência” |
| Enviar por e-mail | `mail` | Padrão para “enviar e-mail” |
| Compartilhar no WhatsApp | `message-circle` | Ícone de mensageria (Feather não possui “whatsapp” nativo) |
| Compartilhar LinkedIn | `linkedin` | Marca reconhecível / atalho cognitivo |
| Compartilhar Facebook | `facebook` | Marca reconhecível / atalho cognitivo |
| Ver avaliação | `bar-chart-2` | “Resultados / métricas” (já usado no sistema) |
| Baixar PDF | `download` | Padrão para “download” (já usado no sistema) |

## Teste de usabilidade (checklist prático)

Checklist para validação rápida (5–10 minutos) com 2–3 usuários internos:

1. Encontrar e acionar “Nova avaliação” sem ler tooltip.
2. Abrir o formulário público e voltar para a listagem.
3. Copiar o link público e colar em outra aba.
4. Identificar e executar “Ver” e “Baixar PDF” em uma linha da tabela.
5. Navegação por teclado:
   - Tab percorre todos os botões
   - foco visível aparece em cada botão
   - leitura do leitor de tela anuncia nomes corretos (aria-label)

Critério de aceitação: usuário executa as tarefas sem pedir ajuda e sem “tentativa e erro” repetida.

## Notas técnicas

- Tooltips via `title` são simples e compatíveis, mas não substituem nome acessível. Por isso há `aria-label` + `.sr-only`.
- O status invisível (`role="status" aria-live="polite"`) melhora feedback para leitores de tela em ações como “copiar”.
