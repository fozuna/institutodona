# Filtros e Paginacao do Perfil do Cliente

## Objetivo

Separar completamente a etapa de filtragem da etapa de paginacao na listagem de `Planos de Acao` do perfil do cliente.

## Fluxo

```text
Dataset base (cliente + filial selecionada)
        |
        v
Aplicacao de filtros (status, busca textual)
        |
        +--> Contagem base sem filtros
        |
        +--> Contagem filtrada
        |
        +--> Resumo filtrado
        |
        +--> Paginacao
              |
              +--> per = 10|20|25|50|100 => LIMIT/OFFSET
              |
              +--> per = all => sem LIMIT/OFFSET
```

## Arquitetura

- `countByClientesMulti(...)`: conta o dataset filtrado sem depender da paginacao.
- `filteredByClientesMulti(...)`: retorna o resultado filtrado completo, sem `LIMIT/OFFSET`.
- `paginateByClientesMulti(...)`: aplica somente a janela de paginacao sobre o resultado ja filtrado.
- `summarizeByClientesMulti(...)`: resume somente o conjunto filtrado.

## Parametros

- `plano_status[]`: filtros por status
- `plano_q`: busca textual
- `plano_per`: tamanho de pagina (`10`, `20`, `25`, `50`, `100`, `all`)
- `plano_page`: pagina atual
- `filial_id`: escopo de filial dentro da matriz

## Exemplos

### Lista filtrada com paginacao

```text
index.php?route=clientes/show&id=2&filial_id=3&plano_status[]=Pendente&plano_per=10&plano_page=2
```

### Lista filtrada sem paginacao

```text
index.php?route=clientes/show&id=2&filial_id=3&plano_status[]=Pendente&plano_per=all
```

### Exportacao com os mesmos filtros

```text
index.php?route=clientes/exportPlanos&id=2&filial_id=3&plano_status[]=Pendente
```

## Edge Cases Cobertos

- multiplos status simultaneos
- troca de `plano_per` sem perder filtros ativos
- `plano_per=all` sem `LIMIT/OFFSET`
- busca textual combinada com status
- navegacao entre paginas preservando filtros
- total base diferente do total filtrado

## Observacoes

- A contagem `Total disponivel` usa o dataset base do escopo atual.
- A contagem `Filtrados` usa apenas os filtros ativos.
- A contagem `Exibindo` reflete apenas a fatia mostrada na tela.
