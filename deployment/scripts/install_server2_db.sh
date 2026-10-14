#!/usr/bin/env bash
# ==============================================================================
# Script: install_server2_db.sh
# Purpose: Run on OFFLINE Server 2 (Database Server - Ubuntu)
# ==============================================================================

set -euo pipefail

echo "=== SWMP Database Server (Server 2) Offline Installation ==="

if [[ $EUID -ne 0 ]]; then
   echo "Error: This script must be run as root (use sudo)." 
   exit 1
fi

DEB_DIR="${1:-/tmp/db-server-debs}"
SETUP_SQL="${2:-./deployment/database/setup_swmp_db.sql}"

if [[ ! -d "${DEB_DIR}" ]]; then
    echo "Error: Directory '${DEB_DIR}' containing .deb files does not exist."
    echo "Usage: sudo ./install_server2_db.sh </path/to/extracted/db-server-debs> [path/to/setup_swmp_db.sql]"
    exit 1
fi

echo "Step 1: Installing MySQL .deb packages offline..."
dpkg -i -E "${DEB_DIR}"/*.deb || {
    echo "Resolving any package dependency order issues..."
    dpkg --configure -a
    dpkg -i -E "${DEB_DIR}"/*.deb
}

echo "Step 2: Configuring MySQL network binding for internal network..."
MYSQL_CNF="/etc/mysql/mysql.conf.d/mysqld.cnf"
if [[ -f "${MYSQL_CNF}" ]]; then
    # Change bind-address to 0.0.0.0
    sed -i 's/^bind-address\s*=.*/bind-address = 0.0.0.0/' "${MYSQL_CNF}"
    # In newer MySQL versions mysqlx-bind-address might exist
    sed -i 's/^mysqlx-bind-address\s*=.*/mysqlx-bind-address = 0.0.0.0/' "${MYSQL_CNF}" 2>/dev/null || true
fi

echo "Step 3: Enabling and restarting MySQL service..."
systemctl enable mysql
systemctl restart mysql

echo "Step 4: Setting up automated nightly database backup..."
BACKUP_DIR="/var/backups/mysql"
mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

BACKUP_SCRIPT="/usr/local/bin/swmp_backup_mysql.sh"
cat << 'EOF' > "${BACKUP_SCRIPT}"
#!/usr/bin/env bash
BACKUP_DIR="/var/backups/mysql"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="${BACKUP_DIR}/swmp_${TIMESTAMP}.sql.gz"

# Run mysqldump and compress
mysqldump --single-transaction --quick --lock-tables=false swmp | gzip > "${FILENAME}"
chmod 600 "${FILENAME}"

# Retain backups for 14 days
find "${BACKUP_DIR}" -type f -name "swmp_*.sql.gz" -mtime +14 -delete
EOF

chmod +x "${BACKUP_SCRIPT}"

# Add to crontab if not already added
if ! crontab -l 2>/dev/null | grep -q "swmp_backup_mysql.sh"; then
    (crontab -l 2>/dev/null || true; echo "0 2 * * * /usr/local/bin/swmp_backup_mysql.sh >> /var/log/swmp_backup.log 2>&1") | crontab -
fi

echo ""
echo "=== MySQL Server Installation Finished! ==="
echo ""
echo "Next step: Initialize the database using the SQL script:"
echo "1. Edit ${SETUP_SQL} with your App Server IP and password."
echo "2. Run: sudo mysql < ${SETUP_SQL}"
echo "3. Optional Firewall configuration:"
echo "   sudo ufw allow from <APP_SERVER_IP> to any port 3306 proto tcp"
