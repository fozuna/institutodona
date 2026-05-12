# Indicadores CRUD e API

## Objetivo

Reestruturar o módulo de `Indicadores` para suportar:

- cliente com autocomplete e validação de ativo
- indicador único por cliente
- departamento e setor ativos com dependência AJAX
- múltiplos responsáveis em tabela associativa `indicador_responsavel`
- periodicidade com intervalo inicial/final
- unidade de medida normalizada em `unidades_medida`
- limites de controle com alertas visuais
- soft delete para auditoria

## Estrutura persistida

### Tabelas

- `indicadores`
  - `cliente_id`
  - `indicador`
  - `departamento_id`
  - `setor_id`
  - `periodicidade_tipo`
  - `data_inicial`
  - `data_final`
  - `valor`
  - `unidade_medida_id`
  - `valor_minimo`
  - `valor_maximo`
  - `created_at`, `updated_at`, `deleted_at`
  - `created_by`, `updated_by`, `deleted_by`
- `indicador_responsavel`
  - `indicador_id`
  - `colaborador_id`
- `indicador_eventos`
  - `indicador_id`
  - `cliente_id`
  - `data_evento`
  - `periodo_inicio`
  - `periodo_fim`
  - `valor_meta`
  - `valor_atingido`
  - `percentual_cumprimento`
  - `status_meta`
  - `observacao`
- `unidades_medida`
  - `nome`
  - `simbolo`
  - `tipo`
  - `fator_conversao_base`
  - `ativo`

### Índices e integridade

- índices em `cliente_id`, `departamento_id`, `setor_id`, `unidade_medida_id`, `deleted_at`
- índices auxiliares em `departamentos(cliente_id, ativo)`, `setores(departamento_id, ativo)`, `colaboradores(cliente_id, ativo)`
- `UNIQUE` em `indicador_responsavel(indicador_id, colaborador_id)`
- `UNIQUE` em `indicador_eventos(indicador_id, data_evento)`
- foreign keys para clientes, departamentos, setores, unidades e colaboradores

## Regras de negócio

- `cliente_id` deve existir e estar ativo
- `indicador` é obrigatório, máximo 255 e único por cliente considerando apenas registros não excluídos
- `departamento_id` deve pertencer ao cliente e estar ativo
- `setor_id` deve pertencer ao departamento e estar ativo
- `responsavel_ids[]` devem apontar para colaboradores ativos do cliente
- `periodicidade_tipo` aceita `diaria|semanal|mensal|bimestral|trimestral|semestral|anual`
- `data_inicial <= data_final`
- `unidade_medida_id` deve existir e estar ativa
- `valor` percentual aceita somente `0..100`
- `valor` inteiro aceita somente números inteiros
- `valor_minimo < valor_maximo`
- valor fora da faixa não bloqueia gravação; apenas aciona alerta visual
- cada indicador materializa eventos recorrentes em `indicador_eventos` do início ao fim do período
- cada evento recebe `valor_meta` por snapshot do indicador e aceita `valor_atingido`
- `percentual_cumprimento = (valor_atingido / valor_meta) * 100`
- status de meta:
  - `atingida` quando `>= 100%`
  - `parcial` quando `>= 80% e < 100%`
  - `nao_atingida` quando `< 80%`
  - `pendente` quando ainda não houve lançamento

## Endpoints RESTful usados pelo módulo

### CRUD web

- `GET index.php?route=indicadores/index`
- `GET index.php?route=indicadores/create`
- `POST index.php?route=indicadores/store`
- `GET index.php?route=indicadores/edit&id={id}`
- `POST index.php?route=indicadores/update`
- `POST index.php?route=indicadores/delete`
- `POST index.php?route=indicadores/updateValorAjax`
  - atualiza o campo `indicadores.valor` (edição inline na listagem), com CSRF e validação por unidade (inteiro/percentual)
- `POST index.php?route=indicadores/deleteAjax`
  - soft delete do indicador via AJAX (remoção do card em tela sem reload)
- `GET index.php?route=indicadores/realizado&cliente={cliente_id}`
- `POST index.php?route=indicadores/updateRealizado`
- `GET index.php?route=indicadores/painel&cliente={cliente_id}&ano={ano}`
- `GET index.php?route=indicadores/charts&cliente={cliente_id}`
- `POST index.php?route=indicadores/chartsPdf`
  - exporta PDF dos gráficos exibidos/filtrados na tela de gráficos (captura client-side do canvas em PNG + render server-side em Dompdf)
- `GET index.php?route=indicadores/evento&id={evento_id}`
- `GET index.php?route=indicadores/historico&id={indicador_id}`

### APIs auxiliares automáticas do formulário

- `GET index.php?route=indicadores/apiClientes&q={texto}`
  - retorna clientes ativos para autocomplete
- `GET index.php?route=indicadores/apiDepartamentos&cliente_id={id}`
  - retorna departamentos ativos do cliente
- `GET index.php?route=indicadores/apiSetores&departamento_id={id}`
  - retorna setores ativos do departamento
- `GET index.php?route=indicadores/apiResponsaveis&cliente_id={id}&q={texto}`
  - retorna colaboradores ativos do cliente com nome e e-mail

## Agenda integrada

- o serviço [AgendaEventService.php](file:///c:/laragon/www/institutodona/app/services/AgendaEventService.php) agora inclui o tipo `indicador`
- cada ocorrência de `indicador_eventos` aparece na agenda integrada com link para `indicadores/evento`
- o lançamento do valor atingido pode ser feito pela agenda ou pela tela `indicadores/realizado`

## Contrato de campos do formulário

### Payload principal

- `cliente_id`: `int`, obrigatório
- `cliente_nome`: `string`, apoio visual do autocomplete
- `indicador`: `string(255)`, obrigatório
- `departamento_id`: `int`, obrigatório
- `setor_id`: `int`, obrigatório
- `responsavel_ids[]`: `int[]`, opcional
- `periodicidade_tipo`: `string`, obrigatório
- `data_inicial`: `Y-m-d`, obrigatório
- `data_final`: `Y-m-d`, obrigatório
- `valor`: `decimal`, obrigatório
- `unidade_medida_id`: `int`, obrigatório
- `valor_minimo`: `decimal|null`, opcional em par
- `valor_maximo`: `decimal|null`, opcional em par

## Internacionalização

- o módulo usa `App\Core\I18n`
- locale padrão: `pt-BR`
- rótulos, mensagens de validação, ações e estados de controle usam chaves `indicadores.*`
- fallback automático para `pt-BR` quando a chave não existir em outro locale

## Alertas visuais

- `Dentro do limite`: badge verde
- `Abaixo do mínimo`: badge vermelho
- `Acima do máximo`: badge âmbar
- `Sem limites configurados`: badge cinza
- `Meta atingida`: badge verde
- `Parcialmente atingida`: badge âmbar
- `Meta não atingida`: badge vermelho
- `Pendente`: badge cinza

## Testes

- `app/tests/indicadores_validation_unit_test.php`
  - valida payload base
  - bloqueia duplicidade por cliente
  - valida intervalo de datas
  - valida limites mínimo/máximo
  - valida tipo percentual
  - valida tipo inteiro
  - valida compatibilidade setor/departamento
  - valida escopo de responsáveis
  - valida soft delete
  - valida atualização de valor
  - valida geração automática de eventos recorrentes
  - valida cálculo de cumprimento e status da meta

- `app/tests/indicadores_update_valor_ajax_integration_test.php`
  - cria indicador temporário, atualiza valor via endpoint AJAX e valida persistência
- `app/tests/indicadores_delete_ajax_integration_test.php`
  - cria indicador temporário, exclui via endpoint AJAX e valida soft delete
- `app/tests/indicadores_charts_pdf_force_missing_unit_test.php`
  - força indisponibilidade do Dompdf e valida retorno 503 amigável
