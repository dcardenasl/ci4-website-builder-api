#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_NAME="${DOCKER_PROJECT_NAME:-ci4-api-e2e-$$}"
APP_CONTAINER="${PROJECT_NAME}-app"
DB_CONTAINER="${PROJECT_NAME}-db"
NETWORK_NAME="${PROJECT_NAME}-network"
MYSQL_VOLUME="${PROJECT_NAME}-mysql"
ENV_VOLUME="${PROJECT_NAME}-env"
HOST_PORT="${API_E2E_HOST_PORT:-18080}"

compose() {
    docker compose -p "${PROJECT_NAME}" "$@"
}

cleanup() {
    compose down --volumes --remove-orphans >/dev/null 2>&1 || true
}

wait_for_http() {
    local url="$1"
    local attempts="${2:-60}"

    for _ in $(seq 1 "${attempts}"); do
        if curl --fail --silent --show-error "${url}" >/dev/null; then
            return 0
        fi
        sleep 2
    done

    echo "Timed out waiting for ${url}" >&2
    return 1
}

trap cleanup EXIT

export API_APP_CONTAINER_NAME="${APP_CONTAINER}"
export API_DB_CONTAINER_NAME="${DB_CONTAINER}"
export API_NETWORK_NAME="${NETWORK_NAME}"
export API_MYSQL_VOLUME_NAME="${MYSQL_VOLUME}"
export API_ENV_VOLUME_NAME="${ENV_VOLUME}"
export API_HOST_PORT="${HOST_PORT}"
export MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-ci4_e2e_root_password}"
export MYSQL_DATABASE="${MYSQL_DATABASE:-ci4_website_builder_api_e2e}"
export MYSQL_USER="${MYSQL_USER:-ci4_e2e_user}"
export MYSQL_PASSWORD="${MYSQL_PASSWORD:-ci4_e2e_password}"

compose build --pull app
compose up -d

wait_for_http "http://127.0.0.1:${HOST_PORT}/ping"
wait_for_http "http://127.0.0.1:${HOST_PORT}/ready"
wait_for_http "http://127.0.0.1:${HOST_PORT}/swagger.json"

compose exec -T app php spark migrate:status >/dev/null
compose exec -T app php spark db:seed RbacBootstrapSeeder >/dev/null

# A restart must reuse the persisted environment, migrations and idempotent
# bootstrap without changing the public health contract.
compose restart app
wait_for_http "http://127.0.0.1:${HOST_PORT}/ping"
wait_for_http "http://127.0.0.1:${HOST_PORT}/ready"
compose exec -T app php spark migrate:status >/dev/null

echo "Docker E2E passed for ${PROJECT_NAME}"
