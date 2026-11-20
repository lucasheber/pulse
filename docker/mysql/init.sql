-- Pulse — Database per Service initialization
-- Each microservice has its own isolated database.
-- Cross-database queries or JOINs are strictly forbidden.

CREATE DATABASE IF NOT EXISTS `bff_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `finance_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `planner_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `bff_db`.* TO 'pulse'@'%';
GRANT ALL PRIVILEGES ON `finance_db`.* TO 'pulse'@'%';
GRANT ALL PRIVILEGES ON `planner_db`.* TO 'pulse'@'%';

FLUSH PRIVILEGES;
