-- =====================================================================
--  «مدیریت مش» - اسکیمای دیتابیس (MySQL 5.7+ / MariaDB 10.3+)
--  نحوه نصب: در phpMyAdmin تب Import → این فایل را انتخاب و Go بزنید.
--  دیتابیس modiriat_mosh خودکار ساخته می‌شود.
--  ورود پیش‌فرض:  نام کاربری: admin   رمز: admin123   (بعد از ورود حتماً تغییر دهید)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `modiriat_mosh` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `modiriat_mosh`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- ادمین‌ها و دسترسی‌ها
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(7) NOT NULL DEFAULT '#3b82f6',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dept_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `username` VARCHAR(60) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `mobile` VARCHAR(20) DEFAULT NULL,
  `department_id` INT UNSIGNED DEFAULT NULL,
  `is_super` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'مدیر کل',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admins_username` (`username`),
  KEY `idx_admins_dept` (`department_id`),
  CONSTRAINT `fk_admin_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `admin_permissions`;
CREATE TABLE `admin_permissions` (
  `admin_id` INT UNSIGNED NOT NULL,
  `section` VARCHAR(30) NOT NULL COMMENT 'customers|projects|finance|support|reports|categories',
  `level` TINYINT NOT NULL DEFAULT 0 COMMENT '0=بدون دسترسی 1=مشاهده 2=مدیریت',
  PRIMARY KEY (`admin_id`, `section`),
  CONSTRAINT `fk_perm_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- مشتریان و راه‌های ارتباطی
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(80) NOT NULL,
  `last_name` VARCHAR(80) NOT NULL,
  `company` VARCHAR(150) DEFAULT NULL,
  `national_code` VARCHAR(10) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customers_name` (`last_name`, `first_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `customer_contacts`;
CREATE TABLE `customer_contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(20) NOT NULL COMMENT 'mobile|phone|email|telegram|whatsapp|instagram|eitaa|bale|website|address|other',
  `value` VARCHAR(255) NOT NULL,
  `label` VARCHAR(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contacts_customer` (`customer_id`),
  KEY `idx_contacts_value` (`value`(40)),
  CONSTRAINT `fk_contact_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- دسته‌بندی‌ها (درختی، قابل ویرایش) و پروژه‌ها
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(7) NOT NULL DEFAULT '#3b82f6',
  `icon` VARCHAR(40) NOT NULL DEFAULT 'bi-tag',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_cat_parent` (`parent_id`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `total_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'تومان',
  `payment_type` ENUM('single','stages','installment') NOT NULL DEFAULT 'single',
  `status` ENUM('pending','active','delivered','canceled') NOT NULL DEFAULT 'active',
  `start_date` DATE DEFAULT NULL,
  `delivery_date` DATE DEFAULT NULL COMMENT 'تاریخ تحویل',
  `support_months` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `support_start` DATE DEFAULT NULL,
  `support_end` DATE DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_projects_customer` (`customer_id`),
  KEY `idx_projects_status` (`status`),
  KEY `idx_projects_support_end` (`support_end`),
  CONSTRAINT `fk_project_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `project_categories`;
CREATE TABLE `project_categories` (
  `project_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`project_id`, `category_id`),
  KEY `idx_pc_category` (`category_id`),
  CONSTRAINT `fk_pc_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- برنامه پرداخت (یک‌جا / مراحل / پیش‌پرداخت و اقساط) و دریافتی‌ها
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `kind` ENUM('single','stage','prepayment','installment') NOT NULL,
  `seq` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `title` VARCHAR(150) NOT NULL,
  `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `due_date` DATE DEFAULT NULL COMMENT 'اختیاری',
  `paid_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `paid_date` DATE DEFAULT NULL COMMENT 'آخرین تاریخ دریافت',
  `status` ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending',
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payments_project` (`project_id`, `seq`),
  KEY `idx_payments_due` (`status`, `due_date`),
  CONSTRAINT `fk_payment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `receipts`;
CREATE TABLE `receipts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED NOT NULL,
  `amount` BIGINT UNSIGNED NOT NULL,
  `paid_date` DATE NOT NULL,
  `method` VARCHAR(20) DEFAULT NULL COMMENT 'card|transfer|cash|pos|cheque|online|other',
  `ref_no` VARCHAR(100) DEFAULT NULL COMMENT 'شماره پیگیری',
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_receipts_payment` (`payment_id`),
  KEY `idx_receipts_date` (`paid_date`),
  CONSTRAINT `fk_receipt_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_receipt_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- سفته‌ها (برای قرارداد اقساطی)
DROP TABLE IF EXISTS `promissory_notes`;
CREATE TABLE `promissory_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'مبلغ کل سفته‌ها',
  `count` SMALLINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'تعداد فقره',
  `serials` VARCHAR(255) DEFAULT NULL COMMENT 'سریال‌ها',
  `status` ENUM('held','returned') NOT NULL DEFAULT 'held',
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_promissory_project` (`project_id`),
  CONSTRAINT `fk_promissory_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ضامن‌ها
DROP TABLE IF EXISTS `guarantors`;
CREATE TABLE `guarantors` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `mobile` VARCHAR(20) DEFAULT NULL,
  `national_code` VARCHAR(10) DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `address` VARCHAR(500) DEFAULT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_guarantor_project` (`project_id`),
  CONSTRAINT `fk_guarantor_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- هزینه‌های پروژه (ابزار، سرور، لایسنس، ...) برای محاسبه سود خالص
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `expense_date` DATE NOT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_expenses_project` (`project_id`),
  KEY `idx_expenses_date` (`expense_date`),
  CONSTRAINT `fk_expense_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- پشتیبانی: تمدیدها و تیکت‌ها
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `support_renewals`;
CREATE TABLE `support_renewals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `months` SMALLINT UNSIGNED NOT NULL,
  `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `paid_date` DATE DEFAULT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_renewal_project` (`project_id`),
  KEY `idx_renewal_paid` (`is_paid`, `paid_date`),
  CONSTRAINT `fk_renewal_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE `tickets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `priority` ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` ENUM('open','in_progress','answered','closed') NOT NULL DEFAULT 'open',
  `channel` VARCHAR(30) DEFAULT NULL COMMENT 'از چه راهی درخواست داده',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  `closed_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tickets_project` (`project_id`),
  KEY `idx_tickets_status` (`status`),
  CONSTRAINT `fk_ticket_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `ticket_replies`;
CREATE TABLE `ticket_replies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ticket_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `body` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_replies_ticket` (`ticket_id`),
  CONSTRAINT `fk_reply_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- پیوست‌ها (برای همه بخش‌ها) - فایل‌ها در storage/uploads (خصوصی)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `internal_ticket_replies`;
DROP TABLE IF EXISTS `internal_tickets`;
CREATE TABLE `internal_tickets` (
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

CREATE TABLE `internal_ticket_replies` (
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

DROP TABLE IF EXISTS `attachments`;
CREATE TABLE `attachments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` VARCHAR(20) NOT NULL COMMENT 'customer|project|payment|promissory|guarantor|expense|ticket|renewal|internal_ticket',
  `entity_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) DEFAULT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_path` VARCHAR(255) NOT NULL,
  `mime` VARCHAR(120) NOT NULL,
  `size` INT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_att_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- نوتیفیکیشن‌ها، تنظیمات و لاگ
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(30) NOT NULL,
  `section` VARCHAR(30) NOT NULL,
  `admin_id` INT UNSIGNED DEFAULT NULL COMMENT 'اعلان اختصاصی برای یک ادمین؛ NULL = عمومی طبق دسترسی بخش',
  `ref_type` VARCHAR(20) DEFAULT NULL,
  `ref_id` INT UNSIGNED DEFAULT NULL,
  `uniq_key` VARCHAR(100) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `body` VARCHAR(1000) DEFAULT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notif_key` (`uniq_key`),
  KEY `idx_notif_section` (`section`),
  KEY `idx_notif_admin` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `notification_reads`;
CREATE TABLE `notification_reads` (
  `notification_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NOT NULL,
  `read_at` DATETIME NOT NULL,
  PRIMARY KEY (`notification_id`, `admin_id`),
  CONSTRAINT `fk_nr_notif` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_nr_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` VARCHAR(60) NOT NULL,
  `value` TEXT DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(30) NOT NULL,
  `entity_type` VARCHAR(30) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `details` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_log_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- داده‌های اولیه
-- =====================================================================

INSERT INTO `departments` (`name`, `color`, `sort_order`) VALUES
('مالی', '#10b981', 1), ('پشتیبانی', '#0ea5e9', 2), ('فنی و توسعه', '#6366f1', 3), ('فروش و بازاریابی', '#f97316', 4);

-- مدیر کل: admin / admin123
INSERT INTO `admins` (`id`, `name`, `username`, `password_hash`, `is_super`, `is_active`, `created_at`) VALUES
(1, 'مدیر کل', 'admin', '$2y$10$hI6lGbriWdc/C4CSPJxCg.dZgFo2GBak5YGfH2LabVQZYXwptvP7W', 1, 1, NOW());

INSERT INTO `settings` (`key`, `value`) VALUES
('company_name', 'مدیریت مش'),
('reminder_days', '7'),
('upload_max_mb', '10'),
('default_support_months', '3');

INSERT INTO `categories` (`id`, `parent_id`, `name`, `color`, `icon`, `sort_order`) VALUES
(1, NULL, 'طراحی سایت', '#3b82f6', 'bi-window', 1),
(2, NULL, 'سئو', '#10b981', 'bi-search', 2),
(3, NULL, 'امنیت', '#0ea5e9', 'bi-shield-lock', 3),
(4, NULL, 'نرم‌افزار اندرویدی', '#22c55e', 'bi-android2', 4),
(5, NULL, 'فرانت‌اند', '#6366f1', 'bi-palette', 5),
(6, NULL, 'بک‌اند', '#14b8a6', 'bi-server', 6),
(7, NULL, 'دیتابیس', '#0891b2', 'bi-database', 7),
(8, NULL, 'IT', '#2563eb', 'bi-pc-display', 8),
(9, NULL, 'تدریس', '#059669', 'bi-mortarboard', 9),
(10, NULL, 'پشتیبانی', '#0d9488', 'bi-headset', 10),
(11, NULL, 'متفرقه', '#64748b', 'bi-three-dots', 11),
(12, 1, 'وردپرس', '#3b82f6', 'bi-wordpress', 1),
(13, 1, 'فروشگاهی', '#3b82f6', 'bi-cart3', 2),
(14, 1, 'شرکتی', '#3b82f6', 'bi-building', 3),
(15, 6, 'لاراول', '#14b8a6', 'bi-code-slash', 1),
(16, 6, 'PHP خام', '#14b8a6', 'bi-filetype-php', 2),
(17, 2, 'سئو داخلی', '#10b981', 'bi-search', 1),
(18, 2, 'سئو خارجی / لینک‌سازی', '#10b981', 'bi-link-45deg', 2);
