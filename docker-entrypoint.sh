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

exec apache2-foreground
