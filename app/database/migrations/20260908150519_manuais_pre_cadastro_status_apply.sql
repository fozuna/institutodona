-- Item 04 (Pré-cadastro de Manuais) - Sprint 01: fundação de banco.
--
-- Torna `arquivo`/`tipo_arquivo` opcionais (um Manual em pré-cadastro ainda
-- não tem documento) e adiciona `status`/`publicado_em` para representar o
-- ciclo de vida `pre_cadastro -> publicado`. `tamanho` PERMANECE NOT NULL
-- DEFAULT 0 (decisão documentada no relatório da sprint: o Model já
-- normaliza ausência de tamanho para 0 via `(int)($data['tamanho'] ?? 0)`
-- em ManualModel::create()/update(), e nenhum arquivo real jamais tem
-- tamanho 0 - validateUpload() já exige sizeBytes > 0 - então 0 é um
-- sentinela seguro e inequívoco de "sem arquivo", sem introduzir mais um
-- estado NULL desnecessário).
--
-- `status` é a única fonte de verdade sobre publicação; `publicado_em` é só
-- metadado temporal (pode ficar NULL para registros legados, que já nascem
-- 'publicado' pelo DEFAULT abaixo). Nenhum Manual/vínculo de filial
-- existente é tocado por este ALTER: é aditivo, sem UPDATE/backfill manual.
ALTER TABLE manuais
  MODIFY COLUMN arquivo VARCHAR(255) NULL,
  MODIFY COLUMN tipo_arquivo VARCHAR(10) NULL,
  ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'publicado' AFTER usuario_id,
  ADD COLUMN publicado_em DATETIME NULL AFTER status,
  ADD INDEX idx_manuais_status (status);
