#!/usr/bin/env bash
# ==============================================================================
# Script: bundle_app.sh
# Purpose: Pre-builds dependencies and generates release archive in an isolated
#          staging directory so your local dev vendor environment is never disturbed.
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
OUTPUT_DIR="${PROJECT_ROOT}/deployment/dist"
ARCHIVE_NAME="swmp-release.tar.gz"

echo "=== SWMP Offline Release Packager ==="
echo "Project Root: ${PROJECT_ROOT}"

cd "${PROJECT_ROOT}"

# Ensure build tools exist
command -v composer >/dev/null 2>&1 || { echo >&2 "Error: composer is required on build machine."; exit 1; }
command -v npm >/dev/null 2>&1 || { echo >&2 "Error: npm is required on build machine."; exit 1; }
command -v php >/dev/null 2>&1 || { echo >&2 "Error: php is required on build machine."; exit 1; }
command -v rsync >/dev/null 2>&1 || { echo >&2 "Error: rsync is required on build machine."; exit 1; }

mkdir -p "${OUTPUT_DIR}"

echo "Step 1: Compiling frontend assets (Vite)..."
npm run build

echo "Step 2: Discovering Filament assets in local environment..."
php artisan filament:upgrade --ansi || true

echo "Step 3: Creating isolated staging build environment..."
STAGE_DIR=$(mktemp -d "${TMPDIR:-/tmp}/swmp_stage_XXXXXX")
trap 'rm -rf "${STAGE_DIR}"' EXIT

echo "Staging files into ${STAGE_DIR}..."
rsync -a \
    --exclude='.git' \
    --exclude='.github' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='tests' \
    --exclude='.env' \
    --exclude='.phpunit.result.cache' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/data/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='deployment/dist' \
    "${PROJECT_ROOT}/" "${STAGE_DIR}/"

# Ensure storage directories exist in staging
mkdir -p "${STAGE_DIR}/storage/app/public"
mkdir -p "${STAGE_DIR}/storage/framework"/{cache/data,sessions,views}
mkdir -p "${STAGE_DIR}/storage/logs"
mkdir -p "${STAGE_DIR}/bootstrap/cache"
mkdir -p "${STAGE_DIR}/database"

# Provide temporary sqlite file and .env for package discovery during composer install
touch "${STAGE_DIR}/database/database.sqlite"
if [[ -f "${PROJECT_ROOT}/.env.example" ]]; then
    cp "${PROJECT_ROOT}/.env.example" "${STAGE_DIR}/.env"
fi

echo "Step 4: Installing production PHP dependencies (Composer in staging)..."
cd "${STAGE_DIR}"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Remove temporary build files from final archive
rm -f "${STAGE_DIR}/.env"
rm -f "${STAGE_DIR}/database/database.sqlite"

echo "Step 5: Packaging application into ${ARCHIVE_NAME}..."
tar -czf "${OUTPUT_DIR}/${ARCHIVE_NAME}" .

echo ""
echo "=== Build Complete! ==="
echo "Artifact created at: ${OUTPUT_DIR}/${ARCHIVE_NAME}"
echo "Size: $(du -h "${OUTPUT_DIR}/${ARCHIVE_NAME}" | cut -f1)"
echo ""
echo "Next step: Copy ${OUTPUT_DIR}/${ARCHIVE_NAME} to your offline Server 1 (App Server)."
