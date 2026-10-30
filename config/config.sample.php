<?php
/**
 * تنظیمات اصلی «مدیریت مش»
 * بعد از ایمپورت database/schema.sql در phpMyAdmin، اطلاعات دیتابیس را اینجا تنظیم کنید.
 */
defined('APP_ROOT') or exit('No direct access');

return [
    'app_name' => 'مدیریت مش',
    'debug'    => true,              // روی سرور واقعی false کنید
    'timezone' => 'Asia/Tehran',

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'modiriat_mosh',
        'user'    => 'root',
        'pass'    => '',               // پیش‌فرض لاراگون: بدون رمز
        'charset' => 'utf8mb4',
    ],

    // مسیر ذخیره پیوست‌ها (خصوصی). برای امنیت بیشتر می‌توانید پوشه‌ای بیرون از www بدهید.
    'storage_path' => APP_ROOT . '/storage/uploads',

    'upload' => [
        'max_size_mb' => 10,
        'allowed_ext' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
        'allowed_mime' => [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'application/zip', 'application/x-zip-compressed', 'application/x-rar-compressed',
            'application/vnd.rar', 'application/x-rar', 'application/octet-stream',
        ],
    ],
];
