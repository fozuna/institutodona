# API de Auditorias

## Endpoints

- `GET index.php?route=auditorias/api_list`
- `GET index.php?route=auditorias/api_setores&cliente_id={id}`
- `GET index.php?route=auditorias/api_responsaveis&setor_id={id}&cliente_id={id}`
- `POST index.php?route=auditorias/api_store`
- `POST index.php?route=auditorias/api_update`
- `POST index.php?route=auditorias/api_delete`
- `POST index.php?route=auditorias/api_auditar`

## Autenticação e escopo

- Requer sessão autenticada.
- Escopo por empresa aplicado automaticamente.
- Rotas de escrita permitidas para perfis `consultor` e `instituto`.
- Perfil `empresa` (`cliente` e `cliente_admin`) possui apenas leitura.
- Perfil `reader` é bloqueado para escrita.

## Filtros de listagem

- `cliente`
- `setor`
- `responsavel`
- `status` (`Agendada`, `Realizada`)
- `inicio` e `fim` em `DD/MM/YYYY` ou `YYYY-MM-DD`
- `q` (busca global)
- `sort` (`data_desc`, `data_asc`, `empresa`, `setor`, `responsavel`, `status`)
- `page`, `per`

## Exemplo de resposta de listagem

```json
{
  "success": true,
  "items": [
    {
      "id": 10,
      "cliente_id": 3,
      "setor_id": 12,
      "responsavel_id": 4,
      "data_auditoria": "2026-03-25",
      "status": "Agendada",
      "cliente_nome": "Empresa X",
      "setor_nome": "Produção",
      "responsavel_nome": "Colaborador Y"
    }
  ],
  "total": 1,
  "page": 1,
  "per": 10
}
```

## Exemplo de criação

```bash
curl -X POST "https://seu-dominio/index.php?route=auditorias/api_store" \
  -d "csrf={token}" \
  -d "cliente_id=3" \
  -d "setor_id=12" \
  -d "responsavel_id=4" \
  -d "data_auditoria=25/03/2026" \
  -d "pergunta=Como está sendo executado o procedimento operacional?" \
  -d "objetivo=Validar aderência ao procedimento e evidências de execução padronizada." \
  -d "referencia_esperada=POP-001 e checklist de execução"
```

## Regras de erro

- `400` requisição inválida/CSRF.
- `403` sem permissão de perfil ou escopo.
- `409` conflito de estado (`Realizada`) ou vínculo com relatório.
- `422` erro de validação por campo.
