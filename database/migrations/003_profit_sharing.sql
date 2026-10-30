-- =====================================================================
--  مهاجرت ۰۰۳: سهم‌بندی سود پروژه بین ادمین‌ها
--  فقط یک بار روی دیتابیس موجود اجرا شود (phpMyAdmin → Import).
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE `projects`
  ADD COLUMN `profit_sharing` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'سهم‌بندی سود فعال است';

CREATE TABLE IF NOT EXISTS `project_shares` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `percent` DECIMAL(5,2) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_share` (`project_id`, `admin_id`),
  KEY `idx_share_admin` (`admin_id`),
  CONSTRAINT `fk_share_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_share_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
