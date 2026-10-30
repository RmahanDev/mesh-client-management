-- =====================================================================
--  مهاجرت ۰۰۲: تیکت داخلی بین ادمین‌ها و دپارتمان‌ها
--  فقط یک بار روی دیتابیس موجود modiriat_mosh اجرا شود (phpMyAdmin → Import یا خط فرمان).
-- =====================================================================
USE `modiriat_mosh`;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(7) NOT NULL DEFAULT '#3b82f6',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `admins`
  ADD COLUMN `department_id` INT UNSIGNED DEFAULT NULL AFTER `mobile`,
  ADD KEY `idx_admins_dept` (`department_id`),
  ADD CONSTRAINT `fk_admin_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;

ALTER TABLE `notifications`
  ADD COLUMN `admin_id` INT UNSIGNED DEFAULT NULL COMMENT 'اعلان اختصاصی برای یک ادمین؛ NULL = عمومی طبق دسترسی بخش' AFTER `section`,
  ADD KEY `idx_notif_admin` (`admin_id`);

CREATE TABLE IF NOT EXISTS `internal_tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` ENUM('open','in_progress','done','closed') NOT NULL DEFAULT 'open',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `from_department_id` INT UNSIGNED DEFAULT NULL,
  `to_department_id` INT UNSIGNED DEFAULT NULL,
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_it_status` (`status`),
  KEY `idx_it_to_dept` (`to_department_id`),
  KEY `idx_it_assigned` (`assigned_to`),
  KEY `idx_it_creator` (`created_by`),
  CONSTRAINT `fk_it_creator` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_it_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_it_to_dept` FOREIGN KEY (`to_department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_it_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `internal_ticket_replies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `kind` ENUM('reply','system') NOT NULL DEFAULT 'reply',
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_itr_ticket` (`ticket_id`),
  CONSTRAINT `fk_itr_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `internal_tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `departments` (`name`, `color`, `sort_order`) VALUES
('مالی', '#10b981', 1), ('پشتیبانی', '#0ea5e9', 2), ('فنی و توسعه', '#6366f1', 3), ('فروش و بازاریابی', '#f97316', 4);
