#!/usr/bin/env bash
# Chuẩn hóa quyền thư mục theo hướng dẫn triển khai Laravel:
# - storage, bootstrap/cache: ghi được bởi PHP / worker (thường 775 thư mục, 664 file)
# - public, bootstrap (file cấu hình): đọc được bởi web server (755 thư mục, 644 file)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "Applying permissions under: $ROOT"

find storage bootstrap/cache -type d -exec chmod 775 {} +
find storage bootstrap/cache -type f -exec chmod 664 {} +

chmod 755 public bootstrap
find public -type d -exec chmod 755 {} +
find public -type f -exec chmod 644 {} +

chmod 644 bootstrap/app.php bootstrap/providers.php 2>/dev/null || true

if [[ -n "${WEB_USER:-}" ]]; then
  chown -R "${WEB_USER}${WEB_GROUP:+:$WEB_GROUP}" storage bootstrap/cache || true
  echo "Ownership set to ${WEB_USER}${WEB_GROUP:+:$WEB_GROUP} for storage and bootstrap/cache (if permitted)."
fi

echo "Done."
