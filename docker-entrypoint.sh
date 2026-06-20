#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f /var/www/html/vendor/autoload.php ]; then
  if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
  else
    echo "vendor/autoload.php not found and Composer is unavailable." >&2
    exit 1
  fi
fi

php -r "require 'app/autoload.php'; new Dompdf\\Options(); new Dompdf\\Dompdf(new Dompdf\\Options()); echo 'DOMPDF_OK'.PHP_EOL;"

php app/database/migrate.php
php app/database/migrate_status.php
php -r 'require "app/autoload.php"; $runner = new \App\Database\MigrationRunner(); $status = $runner->status(); $mismatches = $runner->checksumMismatches(); if (!empty($status["pending"]) || !empty($mismatches)) { fwrite(STDERR, json_encode(["pending" => $status["pending"], "checksum_mismatches" => $mismatches], JSON_UNESCAPED_UNICODE) . PHP_EOL); exit(1); } echo "MIGRATIONS_OK" . PHP_EOL;'

exec apache2-foreground
