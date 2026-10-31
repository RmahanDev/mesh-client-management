<?php
/**
 * نصب‌کننده «مدیریت مش»
 * اطلاعات دیتابیس و مدیر کل را می‌گیرد، جدول‌ها را می‌سازد و config/config.php را تولید می‌کند.
 * بعد از نصب (وجود config/config.php) این صفحه خودکار قفل می‌شود.
 */
declare(strict_types=1);

const ROOT = __DIR__ . '/..';
mb_internal_encoding('UTF-8');
session_name('MOSHINSTALL');
session_start();

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** جداکننده دستورهای SQL با در نظر گرفتن رشته‌ها و کامنت‌ها */
function split_sql(string $sql): array
{
    $out = []; $cur = ''; $q = null; $n = strlen($sql);
    for ($i = 0; $i < $n; $i++) {
        $c = $sql[$i];
        if ($q === null) {
            if ($c === '-' && substr($sql, $i, 3) === '-- ') {
                $e = strpos($sql, "\n", $i);
                $i = $e === false ? $n : $e;
                continue;
            }
            if ($c === "'" || $c === '"' || $c === '`') { $q = $c; }
            elseif ($c === ';') {
                if (trim($cur) !== '') { $out[] = trim($cur); }
                $cur = '';
                continue;
            }
        } else {
            if ($c === '\\' && $q !== '`') { $cur .= $c . ($sql[++$i] ?? ''); continue; }
            if ($c === $q) { $q = null; }
        }
        $cur .= $c;
    }
    if (trim($cur) !== '') { $out[] = trim($cur); }
    return $out;
}

$installed = is_file(ROOT . '/config/config.php');
$errors = [];
$done = false;
$v = ['db_host' => '127.0.0.1', 'db_port' => '3306', 'db_name' => 'modiriat_mosh', 'db_user' => 'root', 'db_pass' => '',
      'admin_name' => '', 'admin_user' => 'admin', 'admin_pass' => '', 'admin_pass2' => ''];

$checks = [
    ['PHP 8.1 یا بالاتر (نسخه فعلی: ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.1.0', '>=')],
    ['افزونه pdo_mysql', extension_loaded('pdo_mysql')],
    ['افزونه mbstring', extension_loaded('mbstring')],
    ['افزونه fileinfo (بررسی نوع فایل‌های آپلودی)', extension_loaded('fileinfo')],
    ['دسترسی نوشتن در پوشه config', is_writable(ROOT . '/config')],
    ['دسترسی نوشتن در پوشه storage', is_writable(ROOT . '/storage') || is_writable(ROOT . '/storage/uploads')],
    ['وجود فایل database/schema.sql', is_file(ROOT . '/database/schema.sql')],
];
$envOk = !in_array(false, array_column($checks, 1), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    foreach ($v as $k => $_) { $v[$k] = trim((string)($_POST[$k] ?? '')); }
    $v['db_pass'] = (string)($_POST['db_pass'] ?? '');
    $v['admin_pass'] = (string)($_POST['admin_pass'] ?? '');
    $v['admin_pass2'] = (string)($_POST['admin_pass2'] ?? '');
    $overwrite = !empty($_POST['overwrite']);

    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) { $errors[] = 'نشست منقضی شده؛ صفحه را دوباره باز کنید.'; }
    if (!$envOk) { $errors[] = 'پیش‌نیازهای سرور برقرار نیست.'; }
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $v['db_name'])) { $errors[] = 'نام دیتابیس فقط شامل حرف انگلیسی، عدد و _ باشد.'; }
    if ($v['db_host'] === '' || $v['db_user'] === '') { $errors[] = 'آدرس سرور و نام کاربری دیتابیس الزامی است.'; }
    if ($v['admin_name'] === '') { $errors[] = 'نام مدیر کل را وارد کنید.'; }
    if (!preg_match('/^[a-z0-9_.\-]{3,60}$/i', $v['admin_user'])) { $errors[] = 'نام کاربری مدیر حداقل ۳ حرف انگلیسی/عدد باشد.'; }
    if (mb_strlen($v['admin_pass']) < 8) { $errors[] = 'رمز عبور مدیر حداقل ۸ کاراکتر باشد.'; }
    if ($v['admin_pass'] !== $v['admin_pass2']) { $errors[] = 'تکرار رمز عبور مطابقت ندارد.'; }

    if (!$errors) {
        $opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $pdo = null;
        try {
            try {
                $pdo = new PDO("mysql:host={$v['db_host']};port=" . (int)$v['db_port'] . ';charset=utf8mb4', $v['db_user'], $v['db_pass'], $opts);
            } catch (PDOException $e) {
                $m = in_array((int)$e->getCode(), [1045, 1698], true) ? 'نام کاربری یا رمز دیتابیس اشتباه است.' : ($e->getCode() == 2002 ? 'اتصال به سرور دیتابیس برقرار نشد؛ آدرس/پورت را بررسی کنید و مطمئن شوید MySQL روشن است.' : $e->getMessage());
                throw new RuntimeException($m);
            }
            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $v['db_name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . $v['db_name'] . '`');
            $pdo->exec("SET NAMES utf8mb4");
            $existing = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN ('admins','customers','projects')")->fetchColumn();
            if ($existing > 0 && !$overwrite) {
                throw new RuntimeException('در دیتابیس «' . $v['db_name'] . '» از قبل جدول‌های برنامه وجود دارد. نصب همه آن‌ها را پاک می‌کند؛ اگر مطمئن هستید گزینه تأیید پاک‌سازی را بزنید یا نام دیگری بدهید.');
            }

            $sql = file_get_contents(ROOT . '/database/schema.sql');
            $sql = preg_replace('/^INSERT INTO `admins`.*?;\s*$/ms', '', $sql, 1);  // ادمین پیش‌فرض نصب نشود
            foreach (split_sql($sql) as $st) {
                if (preg_match('/^(CREATE DATABASE|USE)\b/i', $st)) { continue; }
                $pdo->exec($st);
            }
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
            $ins = $pdo->prepare('INSERT INTO admins (name, username, password_hash, is_super, is_active, created_at) VALUES (?, ?, ?, 1, 1, NOW())');
            $ins->execute([mb_substr($v['admin_name'], 0, 120), strtolower($v['admin_user']), password_hash($v['admin_pass'], PASSWORD_DEFAULT)]);

            $cfg = file_get_contents(ROOT . '/config/config.sample.php');
            $set = static function (string $key, string $val) use (&$cfg): void {
                $cfg = preg_replace("/('" . $key . "'\\s*=>\\s*)(?:'(?:[^'\\\\]|\\\\.)*'|\\d+)/u", '${1}' . addcslashes(var_export($val, true), '\\$'), $cfg, 1);
            };
            $set('host', $v['db_host']);
            $set('name', $v['db_name']);
            $set('user', $v['db_user']);
            $set('pass', $v['db_pass']);
            $hostName = strtolower(explode(':', (string)($_SERVER['HTTP_HOST'] ?? 'localhost'))[0]);
            $isLocal = in_array($hostName, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($hostName, '.test') || str_ends_with($hostName, '.local');
            if (!$isLocal) {   // روی هاست واقعی نمایش خطاها خاموش شود
                $cfg = preg_replace("/('debug'\\s*=>\\s*)true/", '${1}false', $cfg, 1);
            }
            $cfg = preg_replace("/('port'\\s*=>\\s*)\\d+/", '${1}' . (int)$v['db_port'], $cfg, 1);
            if (file_put_contents(ROOT . '/config/config.php', $cfg) === false) {
                throw new RuntimeException('نوشتن config/config.php ممکن نشد؛ دسترسی پوشه config را بررسی کنید.');
            }
            @mkdir(ROOT . '/storage/uploads', 0775, true);
            $done = true;
            unset($_SESSION['csrf']);
        } catch (Throwable $e) {
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'خطا در نصب: ' . $e->getMessage();
        }
    }
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$base = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/install/index.php'))), '/');
?>
<!doctype html>
<html lang="fa" dir="rtl" data-bs-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>نصب مدیریت مش</title>
<link rel="stylesheet" href="../assets/vendor/bootstrap/bootstrap.rtl.min.css">
<link rel="stylesheet" href="../assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/app.css">
<script>try{var t=localStorage.getItem('mm-theme');if(t)document.documentElement.setAttribute('data-bs-theme',t)}catch(e){}</script>
</head>
<body>
<div class="container py-4" style="max-width:760px">
    <div class="text-center mb-4">
        <span class="mm-brand-logo d-inline-grid mb-2" style="width:56px;height:56px;font-size:1.6rem"><svg viewBox="0 0 64 64" width="100%" height="100%" aria-hidden="true"><path d="M18 44V20l14 14 14-14v24" fill="none" stroke="#fff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <h1 class="h3 fw-black">نصب مدیریت مش</h1>
        <div class="text-muted">اطلاعات دیتابیس و مدیر کل را وارد کنید؛ بقیه کارها خودکار انجام می‌شود.</div>
    </div>

<?php if ($done): ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-check-circle-fill text-success" style="font-size:3.2rem"></i>
        <h2 class="h4 mt-3">نصب با موفقیت انجام شد</h2>
        <p class="text-muted">دیتابیس ساخته شد، مدیر کل ثبت شد و تنظیمات ذخیره گردید. این صفحه از این به بعد قفل است.</p>
        <a class="btn btn-grad px-4" href="<?= h($base) ?>/index.php?r=auth/login"><i class="bi bi-box-arrow-in-left me-1"></i>ورود به پنل</a>
        <div class="small text-muted mt-3">برای امنیت بیشتر می‌توانید پوشه <code>install</code> را حذف کنید.</div>
    </div></div>
<?php elseif ($installed): ?>
    <div class="card"><div class="card-body text-center py-5">
        <i class="bi bi-lock-fill text-warning" style="font-size:3rem"></i>
        <h2 class="h5 mt-3">برنامه قبلاً نصب شده است</h2>
        <p class="text-muted mb-3">برای نصب مجدد، ابتدا فایل <code>config/config.php</code> را حذف کنید (دیتابیس قبلی هنگام نصب پاک می‌شود).</p>
        <a class="btn btn-grad" href="<?= h($base) ?>/index.php">رفتن به برنامه</a>
    </div></div>
<?php else: ?>
    <div class="card mb-3"><div class="card-header"><i class="bi bi-clipboard-check me-1 text-primary"></i>بررسی پیش‌نیازها</div>
        <ul class="list-group list-group-flush">
            <?php foreach ($checks as [$label, $ok]): ?>
                <li class="list-group-item bg-transparent d-flex justify-content-between"><span><?= h($label) ?></span>
                    <?= $ok ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-x-circle-fill text-danger"></i>' ?></li>
            <?php endforeach; ?>
        </ul></div>

    <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle me-1"></i><?= h($er) ?></div><?php endforeach; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
        <div class="card mb-3"><div class="card-header"><i class="bi bi-database me-1 text-primary"></i>اطلاعات دیتابیس MySQL</div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-8"><label class="form-label">آدرس سرور</label><input name="db_host" class="form-control ltr" value="<?= h($v['db_host']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">پورت</label><input name="db_port" class="form-control ltr" value="<?= h($v['db_port']) ?>" required></div>
                <div class="col-12"><label class="form-label">نام دیتابیس <span class="small text-muted">(اگر وجود نداشته باشد ساخته می‌شود)</span></label><input name="db_name" class="form-control ltr" value="<?= h($v['db_name']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">نام کاربری</label><input name="db_user" class="form-control ltr" value="<?= h($v['db_user']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">رمز عبور <span class="small text-muted">(لاراگون: خالی)</span></label><input type="password" name="db_pass" class="form-control ltr" value="<?= h($v['db_pass']) ?>" autocomplete="new-password"></div>
            </div></div></div>

        <div class="card mb-3"><div class="card-header"><i class="bi bi-person-gear me-1 text-primary"></i>مدیر کل برنامه</div>
            <div class="card-body"><div class="row g-3">
                <div class="col-md-6"><label class="form-label">نام و نام خانوادگی</label><input name="admin_name" class="form-control" value="<?= h($v['admin_name']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">نام کاربری (انگلیسی)</label><input name="admin_user" class="form-control ltr" value="<?= h($v['admin_user']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">رمز عبور <span class="small text-muted">(حداقل ۸ کاراکتر)</span></label><input type="password" name="admin_pass" class="form-control ltr" required autocomplete="new-password"></div>
                <div class="col-md-6"><label class="form-label">تکرار رمز عبور</label><input type="password" name="admin_pass2" class="form-control ltr" required autocomplete="new-password"></div>
            </div></div></div>

        <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="overwrite" value="1" id="ow">
            <label class="form-check-label small" for="ow">اگر دیتابیس انتخاب‌شده از قبل جدول‌های برنامه را دارد، همه آن‌ها پاک و دوباره ساخته شوند.</label></div>
        <button class="btn btn-grad w-100 py-2" <?= $envOk ? '' : 'disabled' ?>><i class="bi bi-rocket-takeoff me-1"></i>نصب برنامه</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
