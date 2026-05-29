ALTER TABLE treinamentos_agenda
  ADD COLUMN data_fim DATETIME NULL AFTER data,
  ADD INDEX idx_treinamentos_agenda_data_fim (data_fim);
