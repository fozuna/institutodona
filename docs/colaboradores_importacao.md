# Colaboradores: Regras de Cadastro e Importação

## Campos obrigatórios

- `Nome`
- `DN`
- `Unidade`
- `Função`
- `Setor`
- `Departamento`

## Campos opcionais

- `Documento`
- `Celular`
- `Email`

## Regras de validação

- `Nome`: máximo de 100 caracteres.
- `Documento`: opcional; quando informado, deve ser um CPF/CNPJ válido.
- `DN`: obrigatório no formato `DD/MM/AAAA`.
- `Celular`: opcional; quando informado, máximo de 15 caracteres.
- `Email`: opcional; quando informado, deve ter formato válido e não pode duplicar outro já cadastrado.
- `Unidade`: obrigatório; deve corresponder ao nome exato da unidade existente.
- `Função`, `Setor` e `Departamento`: obrigatórios; em filiais, devem respeitar o catálogo herdado da matriz.

## Cadastro manual

- O formulário manual de colaboradores aceita submissão com `Email` vazio.
- Quando `Email`, `Documento` ou `Celular` não forem informados, o sistema persiste `NULL` no banco, evitando conflitos por string vazia.

## Importação por planilha

- O modelo padrão continua exibindo todas as colunas aceitas pelo sistema.
- `Documento`, `Celular` e `Email` aparecem no modelo apenas como referência e podem permanecer vazios.
- O importador mantém inalteradas as validações dos demais campos obrigatórios.
