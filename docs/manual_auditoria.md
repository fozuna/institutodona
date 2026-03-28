# Auditoria — Novas Funcionalidades

## Upload de Arquivos por Questão
- Os anexos são incluídos somente durante a execução da auditoria (tela de avaliação).
- Selecione múltiplos arquivos (limites: 10MB por arquivo, 50MB por questão).
- Ao enviar:
  - Os arquivos são comprimidos (gzip) e validados por antivírus (se disponível).
  - Miniaturas são geradas automaticamente para imagens.
- Os anexos ficam disponíveis para download conforme permissões.

## Opção “NÃO SE APLICA”
- Em avaliações, além de “CONFORME” e “NÃO CONFORME”, use “NÃO SE APLICA”.
- Questões marcadas como “NÃO SE APLICA” não entram no cálculo de conformidade.

## Semáforo de Performance
- Percentual de conformidade = (Conformes / Válidas) × 100.
- Válidas = “CONFORME” + “NÃO CONFORME”.
- Regras:
  - Vermelho: 0–75%
  - Amarelo: 76–90%
  - Verde: 91–100%
- O resultado aparece no detalhe da auditoria após finalização.

## Média por Setor
- Cada auditoria finalizada atualiza a média do setor (mês a mês), ponderada pelo número de questões válidas.
- Disponível para painéis e relatórios (em desenvolvimento contínuo).

## Ajuda
- Em caso de erro de upload, verifique o tamanho do arquivo e tente novamente.
