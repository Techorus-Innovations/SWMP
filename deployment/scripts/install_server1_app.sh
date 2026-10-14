#!/usr/bin/env bash
# ==============================================================================
# Script: install_server1_app.sh
# Purpose: Run on OFFLINE Server 1 (Application Server - Ubuntu)
# ==============================================================================

set -euo pipefail

echo "=== SWMP Application Server (Server 1) Offline Installation ==="

if [[ $EUID -ne 0 ]]; then
   echo "Error: This script must be run as root (use sudo)." 
   exit 1
fi

DEB_DIR="${1:-/tmp/app-server-debs}"
RELEASE_TAR="${2:-/tmp/swmp-release.tar.gz}"
APP_DIR="/var/www/swmp"

if [[ ! -d "${DEB_DIR}" ]]; then
    echo "Error: Directory '${DEB_DIR}' containing .deb files does not exist."
    echo "Usage: sudo ./install_server1_app.sh </path/to/extracted/app-server-debs> </path/to/swmp-release.tar.gz>"
    exit 1
fi

if [[ ! -f "${RELEASE_TAR}" ]]; then
    echo "Error: Archive '${RELEASE_TAR}' does not exist."
    echo "Usage: sudo ./install_server1_app.sh </path/to/extracted/app-server-debs> </path/to/swmp-release.tar.gz>"
    exit 1
fi

echo "Step 1: Installing Nginx, PHP 8.2-FPM, Extensions, and Supervisor offline..."
dpkg -i -E "${DEB_DIR}"/*.deb || {
    echo "Resolving any package dependency order issues..."
    dpkg --configure -a
    dpkg -i -E "${DEB_DIR}"/*.deb
}

echo "Step 2: Deploying application files to ${APP_DIR}..."
mkdir -p "${APP_DIR}"
tar -xzf "${RELEASE_TAR}" -C "${APP_DIR}"

echo "Step 3: Setting correct directory permissions..."
chown -R www-data:www-data "${APP_DIR}"
chmod -R 755 "${APP_DIR}"
chmod -R 775 "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache"

echo "Step 4: Configuring Nginx virtual host..."
NGINX_CONF_SRC="${APP_DIR}/deployment/configs/nginx-swmp.conf"
if [[ -f "${NGINX_CONF_SRC}" ]]; then
    cp "${NGINX_CONF_SRC}" /etc/nginx/sites-available/swmp
    ln -sf /etc/nginx/sites-available/swmp /etc/nginx/sites-enabled/swmp
    rm -f /etc/nginx/sites-enabled/default
    nginx -t && systemctl reload nginx
else
    echo "Warning: ${NGINX_CONF_SRC} not found. Nginx config skipped."
fi

echo "Step 5: Configuring Supervisor for queue workers..."
SUPERVISOR_CONF_SRC="${APP_DIR}/deployment/configs/supervisor-swmp.conf"
if [[ -f "${SUPERVISOR_CONF_SRC}" ]]; then
    cp "${SUPERVISOR_CONF_SRC}" /etc/supervisor/conf.d/swmp-worker.conf
    supervisorctl reread
    supervisorctl update
fi

echo "Step 6: Setting up Laravel scheduler in cron..."
CRON_JOB="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(crontab -u www-data -l 2>/dev/null || true; echo "${CRON_JOB}") | sort -u | crontab -u www-data -

echo "Step 7: Preparing environment file..."
if [[ ! -f "${APP_DIR}/.env" ]]; then
    if [[ -f "${APP_DIR}/deployment/configs/.env.production.example" ]]; then
        cp "${APP_DIR}/deployment/configs/.env.production.example" "${APP_DIR}/.env"
        chown www-data:www-data "${APP_DIR}/.env"
        chmod 600 "${APP_DIR}/.env"
        echo "Created ${APP_DIR}/.env from template. PLEASE EDIT IT with your Server 2 DB IP & Password!"
    fi
fi

# Enable services
systemctl enable nginx
systemctl enable php8.2-fpm
systemctl enable supervisor
systemctl restart php8.2-fpm
systemctl restart nginx

echo ""
echo "=== Application Server Installation Complete! ==="
echo ""
echo "FINAL STEPS:"
echo "1. Edit ${APP_DIR}/.env with your database credentials (Server 2 IP and password)."
echo "2. Run the post-install initialization command as www-data:"
echo "   sudo -u www-data php ${APP_DIR}/artisan key:generate --force"
echo "   sudo -u www-data php ${APP_DIR}/artisan storage:link"
echo "   sudo -u www-data php ${APP_DIR}/artisan migrate --force"
echo "   sudo -u www-data php ${APP_DIR}/artisan config:cache"
echo "   sudo -u www-data php ${APP_DIR}/artisan route:cache"
echo "   sudo -u www-data php ${APP_DIR}/artisan view:cache"
echo "   sudo -u www-data php ${APP_DIR}/artisan icons:cache"
echo "3. Start supervisor workers:"
echo "   sudo supervisorctl start swmp-worker:*"
