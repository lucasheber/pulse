#!/bin/sh
set -eu

cd /app

if [ ! -f ".env" ] && [ -f ".env.example" ]; then
  cp .env.example .env
fi

if [ ! -d "node_modules" ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
  echo "[pulse] Installing frontend npm dependencies..."
  npm install
fi

exec npm run dev -- --host 0.0.0.0 --port 5173
