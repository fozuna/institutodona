## Relatório técnico — Falha ao gerar PDF (Dompdf indisponível)

### Resumo

Erro em produção: `Falha ao gerar PDF: Dependência Dompdf indisponível...`

Impacto: todos os endpoints que geram PDF (Avaliações, Treinamentos, etc.) falham quando o Dompdf não está carregado/instalado no servidor.

### Achados (no repositório)

1) `composer.json` declara Dompdf:
- `dompdf/dompdf: 3.1`

2) Ponto de entrada da aplicação:
- `app/autoload.php` inclui `vendor/autoload.php` apenas se existir (não falha explicitamente se não existir).

3) Locais de uso do Dompdf no código:
- `app/services/AvaliacaoPdfService.php`
- `app/services/TreinamentoDocumentService.php`

### Causa raiz provável em produção

Uma destas condições (ou combinação):
- `vendor/` ausente no deploy (deploy incompleto, build quebrado, artefato ignorado).
- `composer install --no-dev` não executado no servidor (ou executado com falha).
- permissões de leitura insuficientes em `vendor/` e/ou `vendor/autoload.php`.
- `open_basedir`/restrições de leitura impedindo acesso ao `vendor/`.

### Ações executadas no código (mitigação + observabilidade)

1) Mecanismo de verificação prévia:
- Criado `App\Core\PdfSupport` para validar disponibilidade do Dompdf antes de gerar PDFs.

2) Mensagem amigável:
- Para Avaliações (`AvaliacoesController::relatorioPdf`) e Treinamentos (endpoints PDF), retornamos `503` com mensagem amigável quando o Dompdf estiver indisponível.

3) Hardening do Dompdf no serviço:
- `AvaliacaoPdfService` deixa de verificar Dompdf no construtor (evita abortar fluxos não-PDF).

4) Ferramenta de diagnóstico local/servidor:
- `php app/tools/dompdf_diagnose.php` imprime status do composer.json/lock, vendor/autoload e classes.

5) Testes automatizados:
- `app/tests/dompdf_bootstrap_smoke.php` (já existia)
- `app/tests/pdf_support_force_missing_unit_test.php` (novo)
- `app/tests/treinamentos_presenca_pdf_smoke.php` (novo)

### Procedimento recomendado em produção (passo-a-passo)

1) Backup
- Backup do diretório do projeto e do banco (snapshot/rsync).

2) Validar dependência no servidor
- `cat composer.json | head -n 50`
- `composer show dompdf/dompdf`

3) Validar autoload
- `ls -la vendor/autoload.php`
- `php -r "require 'vendor/autoload.php'; echo class_exists('Dompdf\\\\Dompdf')?'ok':'fail';"`

4) Logs
- PHP-FPM: `/var/log/php8.*-fpm.log` (ou CloudPanel UI)
- Nginx/Apache: `/var/log/nginx/error.log` ou `/var/log/apache2/error.log`

5) Permissões (padrão recomendado)
- `find vendor -type d -exec chmod 755 {} \\;`
- `find vendor -type f -exec chmod 644 {} \\;`
- garantir que o usuário do PHP-FPM/Apache tenha leitura em `vendor/`.

6) Se ausente: instalar dependências (sem dev)
- `composer install --no-dev --optimize-autoloader`

7) Smoke test no servidor
- `php app/tools/dompdf_diagnose.php`
- abrir endpoints de PDF (Avaliações/Treinamentos) e validar download.

### Confirmação pós-correção

Após `composer install --no-dev --optimize-autoloader` e validação de permissões, o Dompdf deve estar disponível (`class_exists` true) e os módulos de PDF voltam a funcionar.

