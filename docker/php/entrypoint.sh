#!/usr/bin/env bash
set -euo pipefail

cd /opt/www

if [ ! -f ".env" ] && [ -f ".env.example" ]; then
  cp .env.example .env
fi

if [ ! -f "vendor/autoload.php" ]; then
  echo "[pulse] Installing Composer dependencies for ${APP_NAME:-service}..."
  composer install --no-interaction --prefer-dist
fi

rm -rf runtime/container

if [ "${HYPERF_WATCH:-true}" = "true" ] && [ -f "vendor/bin/hyperf" -o -d "vendor/hyperf/watcher" ]; then
  echo "[pulse] Starting ${APP_NAME:-service} with Hyperf Watcher..."
  exec php bin/hyperf.php server:watch
else
  echo "[pulse] Starting ${APP_NAME:-service}..."
  exec php bin/hyperf.php start
fi
