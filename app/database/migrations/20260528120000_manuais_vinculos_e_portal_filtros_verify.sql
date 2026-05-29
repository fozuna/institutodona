SELECT 1 FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_name = 'manual_filial_links'
LIMIT 1;

SELECT 1 FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manual_portal_tokens' AND column_name = 'scope_ids_json'
LIMIT 1;

SELECT 1 FROM information_schema.columns
WHERE table_schema = DATABASE() AND table_name = 'manual_portal_tokens' AND column_name = 'filters_json'
LIMIT 1;
