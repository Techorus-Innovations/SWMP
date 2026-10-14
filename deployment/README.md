# SWMP Offline Deployment Guide (Ubuntu Bare-Metal)

This directory contains all tools, scripts, and configurations required to deploy **SWMP** on **two offline (air-gapped) Ubuntu servers**:
* **Server 1 (Application Server):** Nginx + PHP 8.2-FPM + Supervisor + Laravel App.
* **Server 2 (Database Server):** MySQL 8.0 Server + Automated Nightly Backups.

---

## Directory Overview

```text
deployment/
├── README.md                      # This comprehensive guide
├── configs/
│   ├── .env.production.example    # Production environment variables (Mail set strictly to log)
│   ├── nginx-swmp.conf            # Nginx virtual host configuration
│   └── supervisor-swmp.conf       # Supervisor configuration for queue workers
├── database/
│   └── setup_swmp_db.sql          # SQL script to initialize database and restricted user
└── scripts/
    ├── bundle_app.sh              # [Step 1] Build machine: Compiles vendor, assets, and builds tarball
    ├── download_offline_debs.sh   # [Step 2] Online Ubuntu machine: Downloads .deb packages
    ├── install_server1_app.sh     # [Step 4] Offline Server 1: Installs App packages and configures server
    └── install_server2_db.sh      # [Step 3] Offline Server 2: Installs MySQL packages and configures DB
```

---

## Phase 1: On Internet-Connected Machine

You need an internet-connected machine to build the application release and download the offline Ubuntu packages.

### 1. Build Application Release Tarball
In the project root directory, run:
```bash
./deployment/scripts/bundle_app.sh
```
* **What it does:** Installs production composer packages, compiles frontend assets with Vite (`npm run build`), discovers Filament assets, and archives everything into `deployment/dist/swmp-release.tar.gz`.

### 2. Download Offline Ubuntu Packages
On an internet-connected machine running Ubuntu (matching your target servers' version, e.g., 22.04 or 24.04):
```bash
./deployment/scripts/download_offline_debs.sh
```
* **What it does:** Downloads all `.deb` files and transitive dependencies for Nginx, PHP 8.2, MySQL 8.0, and Supervisor into:
  * `deployment/dist/debs/app-server-debs.tar.gz`
  * `deployment/dist/debs/db-server-debs.tar.gz`

---

## Phase 2: Transfer Files to Offline Servers

Copy the generated files to the target servers using a USB drive or internal network:

| Server | Files to Transfer |
| :--- | :--- |
| **Server 2 (DB Server)** | `db-server-debs.tar.gz`<br>`deployment/scripts/install_server2_db.sh`<br>`deployment/database/setup_swmp_db.sql` |
| **Server 1 (App Server)** | `app-server-debs.tar.gz`<br>`swmp-release.tar.gz`<br>`deployment/scripts/install_server1_app.sh` |

---

## Phase 3: Setup Server 2 (Database Server)

Perform these steps on **Server 2**:

1. Extract the downloaded DB packages:
   ```bash
   mkdir -p /tmp/db-server-debs
   tar -xzf db-server-debs.tar.gz -C /tmp/db-server-debs
   ```
2. Run the installation script:
   ```bash
   chmod +x install_server2_db.sh
   sudo ./install_server2_db.sh /tmp/db-server-debs setup_swmp_db.sql
   ```
3. Edit `setup_swmp_db.sql` to replace:
   * `<APP_SERVER_IP>` with the internal IP address of Server 1.
   * `REPLACE_WITH_STRONG_PASSWORD` with a secure database password.
4. Execute the SQL setup:
   ```bash
   sudo mysql < setup_swmp_db.sql
   ```
5. *(Optional but Recommended)* If UFW firewall is enabled on Server 2:
   ```bash
   sudo ufw allow from <SERVER_1_IP> to any port 3306 proto tcp
   ```

---

## Phase 4: Setup Server 1 (Application Server)

Perform these steps on **Server 1**:

1. Extract the downloaded App packages:
   ```bash
   mkdir -p /tmp/app-server-debs
   tar -xzf app-server-debs.tar.gz -C /tmp/app-server-debs
   ```
2. Run the installation script:
   ```bash
   chmod +x install_server1_app.sh
   sudo ./install_server1_app.sh /tmp/app-server-debs swmp-release.tar.gz
   ```
3. Configure the environment:
   Edit `/var/www/swmp/.env` with your editor:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://<SERVER_1_IP_OR_DOMAIN>

   DB_CONNECTION=mysql
   DB_HOST=<SERVER_2_IP>
   DB_PORT=3306
   DB_DATABASE=swmp
   DB_USERNAME=swmp_user
   DB_PASSWORD=<YOUR_SECURE_PASSWORD>

   SESSION_DRIVER=database
   QUEUE_CONNECTION=database
   CACHE_STORE=database

   MAIL_MAILER=log
   ```
4. Run final initialization commands as `www-data`:
   ```bash
   sudo -u www-data php /var/www/swmp/artisan key:generate --force
   sudo -u www-data php /var/www/swmp/artisan storage:link
   sudo -u www-data php /var/www/swmp/artisan migrate --force
   sudo -u www-data php /var/www/swmp/artisan config:cache
   sudo -u www-data php /var/www/swmp/artisan route:cache
   sudo -u www-data php /var/www/swmp/artisan view:cache
   sudo -u www-data php /var/www/swmp/artisan icons:cache
   ```
5. Start the background queue worker:
   ```bash
   sudo supervisorctl start swmp-worker:*
   ```

---

## Verification & Health Check

1. **Verify Web Service:** Open `http://<SERVER_1_IP>` in your browser. The application should load with all styles and Filament assets.
2. **Verify Database Connectivity:**
   ```bash
   php /var/www/swmp/artisan db:show
   ```
3. **Verify Queue Worker Status:**
   ```bash
   sudo supervisorctl status
   ```
   Should display `swmp-worker:swmp-worker_00 RUNNING` and `swmp-worker:swmp-worker_01 RUNNING`.
4. **Verify Database Backups (Server 2):**
   ```bash
   sudo /usr/local/bin/swmp_backup_mysql.sh
   ls -lh /var/backups/mysql/
   ```
