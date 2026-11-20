#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SONAR_DIR="${ROOT_DIR}/build/sonar"
mkdir -p "${SONAR_DIR}"

declare -a SERVICES=("bff-gateway:bff" "finance-service:finance" "planner-service:planner")

for entry in "${SERVICES[@]}"; do
  service="${entry%%:*}"
  prefix="${entry##*:}"

  echo "============================================================"
  echo "[pulse:quality] Checking ${service}..."
  echo "============================================================"

  docker compose exec -T "${service}" composer cs-check
  docker compose exec -T "${service}" composer rector:dry
  docker compose exec -T "${service}" composer analyse:json
  docker compose exec -T "${service}" composer test:coverage

  # Rewrite container path (/opt/www/) to monorepo-relative path (services/<service>/) for SonarQube
  sed "s|/opt/www/|services/${service}/|g" \
    "${ROOT_DIR}/services/${service}/build/coverage/clover.xml" \
    > "${SONAR_DIR}/${prefix}-clover.xml"

  sed "s|/opt/www/|services/${service}/|g" \
    "${ROOT_DIR}/services/${service}/build/reports/phpstan.json" \
    > "${SONAR_DIR}/${prefix}-phpstan.json"
done

echo "============================================================"
echo "[pulse:quality] Checking frontend (TypeScript)..."
echo "============================================================"
docker compose exec -T frontend npm run typecheck

echo ""
echo "[pulse:quality] All checks passed! SonarQube reports generated in build/sonar/"
echo "[pulse:quality] HTML coverage reports:"
echo "  - services/bff-gateway/build/coverage/html/index.html"
echo "  - services/finance-service/build/coverage/html/index.html"
echo "  - services/planner-service/build/coverage/html/index.html"
