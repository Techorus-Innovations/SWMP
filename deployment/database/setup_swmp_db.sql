-- ==============================================================================
-- SWMP Production Database Initialization Script (Server 2)
-- Replace '<APP_SERVER_IP>' and 'REPLACE_WITH_STRONG_PASSWORD' before running
-- ==============================================================================

CREATE DATABASE IF NOT EXISTS `swmp`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Allow access specifically from the Application Server IP
CREATE USER IF NOT EXISTS 'swmp_user'@'<APP_SERVER_IP>' IDENTIFIED BY 'REPLACE_WITH_STRONG_PASSWORD';

GRANT ALL PRIVILEGES ON `swmp`.* TO 'swmp_user'@'<APP_SERVER_IP>';

FLUSH PRIVILEGES;

-- Verify creation
SELECT user, host FROM mysql.user WHERE user = 'swmp_user';
