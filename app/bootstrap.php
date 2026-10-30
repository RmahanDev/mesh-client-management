<?php
defined('APP_ROOT') or exit('No direct access');

$config = require APP_ROOT . '/config/config.php';
$GLOBALS['config'] = $config;

date_default_timezone_set($config['timezone']);
mb_internal_encoding('UTF-8');

if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// Autoloader ساده برای namespace App\
spl_autoload_register(function (string $class) {
    if (str_starts_with($class, 'App\\')) {
        $parts = explode('\\', substr($class, 4));
        $file  = array_pop($parts);
        $dir   = strtolower(implode('/', $parts));
        $path  = APP_ROOT . '/app/' . ($dir ? $dir . '/' : '') . $file . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});

require APP_ROOT . '/app/core/helpers.php';

session_name('MOSHSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
