DROP TABLE IF EXISTS manual_filial_links;

ALTER TABLE manual_portal_tokens
  DROP COLUMN filters_json,
  DROP COLUMN scope_ids_json;
