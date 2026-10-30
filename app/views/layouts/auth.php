<?php defined('APP_ROOT') or exit; ?>
<!doctype html>
<html lang="fa" dir="rtl" data-bs-theme="dark">
<head>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body>
<?= $content ?>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
