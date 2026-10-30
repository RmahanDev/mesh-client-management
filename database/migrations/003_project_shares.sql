-- =====================================================================
--  مهاجرت ۰۰۳: سهم‌بندی سود پروژه بین ادمین‌ها
--  فقط یک بار روی دیتابیس موجود اجرا شود (phpMyAdmin → انتخاب دیتابیس → Import).
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE `projects`
  ADD COLUMN `shares_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'سهم‌بندی سود فعال است' AFTER `support_end`;

CREATE TABLE IF NOT EXISTS `project_shares` (
  `project_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `percent` DECIMAL(5,2) NOT NULL COMMENT 'درصد از سود خالص پروژه',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`project_id`, `admin_id`),
  KEY `idx_share_admin` (`admin_id`),
  CONSTRAINT `fk_share_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_share_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
