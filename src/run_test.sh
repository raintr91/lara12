#!/usr/bin/env sh
set -eu

SCRIPT_DIR="$(CDPATH= cd -- "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

php artisan migrate:rollback --env=testing --force
php artisan migrate --env=testing --force

# pcov.directory phải trùng thư mục gốc Laravel (api/src); sai path → coverage 0%.
php -d pcov.enabled=1 \
  -d "pcov.directory=${SCRIPT_DIR}" \
  -d pcov.exclude='~vendor|coverage~' \
  ./vendor/bin/phpunit --coverage-html=coverage
