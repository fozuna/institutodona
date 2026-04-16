#!/bin/sh
set -eu

cd /var/www/html

php app/database/migrate.php

exec apache2-foreground
