# Guia de ícones e padronização de botões (SIS+)

## Objetivo

Padronizar ícones intuitivos para ações recorrentes em botões e links de ação, reduzindo textos extensos quando necessário, mantendo acessibilidade (tooltip + aria-label) e consistência visual em todo o sistema.

## Padrão adotado (global)

O sistema passa a aplicar ícones automaticamente em botões/links com aparência de botão (classes com `rounded`/`icon-btn`/`icon-action`) e texto equivalente às ações abaixo.

Regras:

- Sempre manter `title` e `aria-label` com o texto original da ação.
- Para ações “Novo/Nova …”: reduzir o texto visível para “Novo”/“Nova” e manter o texto completo no tooltip/aria-label.
- Não duplicar ícones: se já houver `svg` ou `data-feather`, o botão é ignorado.

Implementação: `public_html/assets/js/app.js` (executa no `DOMContentLoaded` e reprocessa os ícones).

## Mapeamento de ícones (Feather)

| Ação (texto original) | Ícone |
|---|---|
| Voltar | `arrow-left` |
| Filtrar | `filter` |
| Limpar | `x-circle` |
| Salvar | `save` |
| Cancelar | `x` |
| Gerar link | `link` |
| Copiar | `copy` |
| Baixar | `download` |
| Abrir | `external-link` |
| Novo/Nova … | `plus` |

## Antes e depois (exemplos “visuais” em HTML)

### 1) Botão “Voltar”

Antes:

```html
<a class="px-3 py-2 rounded bg-gray-200 text-brand-brown" href="javascript:history.back()">Voltar</a>
```

Depois (renderizado em runtime):

```html
<a class="px-3 py-2 rounded bg-gray-200 text-brand-brown inline-flex items-center gap-2"
   title="Voltar"
   aria-label="Voltar">[icon: arrow-left] Voltar</a>
```

### 2) Botão “Novo Colaborador” (texto extenso)

Antes:

```html
<a class="px-3 py-2 rounded bg-brand-red text-white" href="...">Novo Colaborador</a>
```

Depois (texto reduzido + acessibilidade preservada):

```html
<a class="px-3 py-2 rounded bg-brand-red text-white inline-flex items-center gap-2"
   title="Novo Colaborador"
   aria-label="Novo Colaborador">[icon: plus] Novo</a>
```

## Páginas com maior incidência de botões textuais (mapeadas)

Arquivos (views) identificados com “Novo/Voltar/Filtrar/Limpar/Salvar/Cancelar/Baixar”:

- app/views/aplicacoes/create.php
- app/views/aplicacoes/show.php
- app/views/auditorias/editar_realizada.php
- app/views/auditorias/index.php
- app/views/auditorias/show.php
- app/views/avaliacoes/create.php
- app/views/avaliacoes/publica.php
- app/views/avaliacoes/show.php
- app/views/biblioteca/index.php
- app/views/clientes/index.php
- app/views/clientes/show.php
- app/views/coaching/index.php
- app/views/colaboradores/index.php
- app/views/consultores/create.php
- app/views/consultores/edit.php
- app/views/consultores/index.php
- app/views/cronograma/add_evento.php
- app/views/cronograma/create.php
- app/views/cronograma/index.php
- app/views/cronograma/show.php
- app/views/dashboard/kanban.php
- app/views/departamentos/index.php
- app/views/funcoes/index.php
- app/views/indicadores/index.php
- app/views/logs/index.php
- app/views/manuais/create.php
- app/views/manuais/index.php
- app/views/manuais/portal.php
- app/views/metodologias/create.php
- app/views/metodologias/edit.php
- app/views/metodologias/index.php
- app/views/pilares/create.php
- app/views/pilares/edit.php
- app/views/pilares/index.php
- app/views/planoacao/create.php
- app/views/planoacao/index.php
- app/views/planoacao/show.php
- app/views/processos/index.php
- app/views/reunioes/index.php
- app/views/setores/index.php
- app/views/tarefas/index.php
- app/views/treinamentos/dashboard.php
- app/views/treinamentos/index.php
- app/views/treinamentos/presenca.php
- app/views/treinamentos/show.php
- app/views/usuarios/index.php

## Validação

- Ícones são reprocessados via `feather.replace()` após a padronização, garantindo renderização mesmo em páginas com conteúdo dinâmico.
- Acessibilidade mantida via `title` + `aria-label` em todos os botões convertidos.
