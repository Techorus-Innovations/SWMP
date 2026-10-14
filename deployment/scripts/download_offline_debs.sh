#!/usr/bin/env bash
# ==============================================================================
# Script: download_offline_debs.sh
# Purpose: Run on an internet-connected Ubuntu machine (matching target OS version)
#          to download all required .deb packages and dependencies offline.
# ==============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TARGET_DIR="${SCRIPT_DIR}/../dist/debs"

echo "=== SWMP Offline Ubuntu Package Downloader ==="
echo "Target directory: ${TARGET_DIR}"

# Ensure running on Ubuntu / Debian
if ! command -v apt-get >/dev/null 2>&1; then
    echo "Error: This script must be executed on an Ubuntu/Debian system matching the server release."
    exit 1
fi

mkdir -p "${TARGET_DIR}/app-server"
mkdir -p "${TARGET_DIR}/db-server"

# Ensure universe and ondrej/php repo (if needed for PHP 8.2 on older Ubuntu)
echo "Ensuring package repositories..."
sudo apt-get update -y
sudo apt-get install -y --no-install-recommends software-properties-common ca-certificates

# If PHP 8.2 is not in default repos (e.g., Ubuntu 20.04/22.04), add PPA
if ! apt-cache show php8.2-fpm >/dev/null 2>&1; then
    echo "Adding ppa:ondrej/php repository for PHP 8.2..."
    sudo add-apt-repository -y ppa:ondrej/php
    sudo apt-get update -y
fi

APP_PACKAGES=(
    nginx
    supervisor
    php8.2-fpm
    php8.2-cli
    php8.2-mysql
    php8.2-mbstring
    php8.2-xml
    php8.2-curl
    php8.2-zip
    php8.2-bcmath
    php8.2-intl
    php8.2-gd
    php8.2-sqlite3
    php8.2-opcache
    unzip
    tar
    curl
)

DB_PACKAGES=(
    mysql-server
    mysql-client
)

echo "--------------------------------------------------------"
echo "Downloading App Server packages into ${TARGET_DIR}/app-server..."
sudo apt-get clean
sudo apt-get install --download-only -y \
    -o Dir::Cache="/tmp/apt-app-cache" \
    -o Dir::Cache::archives="${TARGET_DIR}/app-server" \
    "${APP_PACKAGES[@]}"

echo "--------------------------------------------------------"
echo "Downloading Database Server packages into ${TARGET_DIR}/db-server..."
sudo apt-get clean
sudo apt-get install --download-only -y \
    -o Dir::Cache="/tmp/apt-db-cache" \
    -o Dir::Cache::archives="${TARGET_DIR}/db-server" \
    "${DB_PACKAGES[@]}"

echo "--------------------------------------------------------"
echo "Creating compressed archives of packages..."
cd "${TARGET_DIR}"
tar -czf app-server-debs.tar.gz -C app-server .
tar -czf db-server-debs.tar.gz -C db-server .

echo "=== Package Download Complete! ==="
echo "App Server DEBs archive: ${TARGET_DIR}/app-server-debs.tar.gz"
echo "DB Server DEBs archive:  ${TARGET_DIR}/db-server-debs.tar.gz"
