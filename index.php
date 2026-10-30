<?php
/**
 * مدیریت مش - Front Controller
 * همه درخواست‌ها از این فایل عبور می‌کنند: index.php?r=controller/action
 */
define('APP_ROOT', __DIR__);

// اگر برنامه هنوز نصب نشده، به صفحه نصب برو
if (!is_file(APP_ROOT . '/config/config.php')) {
    header('Location: ' . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/') . '/install/');
    exit;
}

require APP_ROOT . '/app/bootstrap.php';
App\Core\Migrator::run();

App\Core\Router::dispatch($_GET['r'] ?? 'dashboard');
