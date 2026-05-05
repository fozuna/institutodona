# Módulo Avançado de Treinamentos

## Entregas principais

- Exportação de colaboradores elegíveis em `PDF` e `XLSX`
- Filtros por `setor`, `cargo`, `data_admissao`, `status_atual` e `status_elegibilidade`
- Geração antecipada de certificados com numeração única e código de autenticação
- Backup automático dos certificados em `storage/pdfs/treinamentos/certificados/backup`
- Lista de presença em `PDF` com horários, assinatura física e termo do instrutor
- Dashboard com percentuais por treinamento, comparativo por setor, acumuladores e alertas
- Separação explícita entre `presenca` e `certificado_emitido`
- Auditoria em `treinamento_auditoria_logs`
- Cache de exportação/consulta em `treinamento_export_cache`

## Rotas principais

- `treinamentos/show`
- `treinamentos/presenca`
- `treinamentos/export_elegiveis`
- `treinamentos/presenca_pdf`
- `treinamentos/certificado`
- `treinamentos/certificado_lote`
- `treinamentos/dashboard`
- `treinamentos/dashboard_pdf`

## Regras implementadas

- Certificado pode ser emitido sem presença confirmada
- Presença confirmada não depende de certificado emitido
- Conclusão do vínculo considera presença confirmada ou certificado emitido
- Emissão em lote só usa participantes já pré-cadastrados no agendamento
- Exportações respeitam escopo do tenant e filtros ativos

## Persistência

- Novos campos em `treinamentos`: `tipo_treinamento`, `template_certificado`, `assinatura_responsavel`
- Novos campos em `colaboradores`: `matricula`, `cpf`, `data_admissao`, `status_atual`
- Novos campos em `treinamento_participantes`: controle de certificado, horários, observação e arquivo

## Validação

- Perfis de escrita usam restrição de gerenciamento no controller
- Leitura continua respeitando autenticação e escopo
- Teste de integração validado em `app/tests/treinamento_module_integration_test.php`
