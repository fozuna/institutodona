## Incidente

- Sintoma: erro no envio do questionário público na etapa 2, exibindo mensagem genérica com “Codigo: <id>” (ex.: 9cea336351).
- Ambientes impactados: base testes e base produção.
- Endpoint: `/public/avaliacoes.php`

## Causa raiz (provável)

O envio executa o fluxo de persistência da avaliação e, em seguida, tenta gerar PDF via `AvaliacaoPdfService`.
Quando a dependência `dompdf/dompdf` não está disponível no ambiente (ou autoload/vendor ausente), o construtor do serviço lançava exceção e interrompia o envio, resultando na mensagem genérica com código.

## Correção aplicada

- Tornado o PDF “best-effort”: geração de PDF passa a ser opcional e não impede o envio.
- `AvaliacaoPdfService` agora valida a presença do Dompdf somente no momento de renderizar o PDF, permitindo instanciar o serviço e executar o fluxo sem exceção caso o Dompdf esteja indisponível.
- `PublicAvaliacoesController` passa a capturar exceções relacionadas ao PDF e registrar eventos específicos (`public_avaliacoes_pdf_exception`) sem abortar o envio.
- Logging enriquecido para os erros de envio (`public_avaliacoes_error`) com `file`, `line`, `trace` (limitado) e metadados do request (content-length, max_input_vars, post_max_size, etc.), para correlação via `error_id`.
- Redação de PII no audit log para campos sensíveis do formulário público.

## Monitoramento

- Os erros do fluxo público são registrados em `storage/logs/audit.log`.
- Script para triagem rápida (últimos 50 erros):
  - `php app/tools/public_avaliacoes_error_report.php`

## Ações recomendadas pós-deploy

- Garantir que `storage/` seja gravável pelo usuário do PHP-FPM/Apache.
- (Opcional) Instalar `dompdf/dompdf` e dependências de runtime para reabilitar geração de PDF automaticamente.
- Validar em produção o envio de avaliações e, se desejado, testar o download de PDF com `?download=pdf&avaliacao_id=...&sig=...`.

