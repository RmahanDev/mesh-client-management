<?php defined('APP_ROOT') or exit; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="#151f33">
<link rel="manifest" href="<?= base_url() ?>/manifest.webmanifest">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="مدیریت مش">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?= e(($title ?? '') ? $title . ' | ' : '') ?><?= e(\App\Core\Settings::get('company_name', config('app_name'))) ?></title>
<script>
    (function () { try { var t = localStorage.getItem('mm-theme'); if (t) document.documentElement.setAttribute('data-bs-theme', t); } catch (e) { } })();
</script>
<link rel="icon" href="<?= asset('img/logo.svg') ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.rtl.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
