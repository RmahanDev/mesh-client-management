<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * اجرای خودکار مایگریشن‌های database/migrations برای دیتابیس‌هایی که با نسخه قدیمی‌تر نصب شده‌اند.
 * هر مایگریشن یک شرط «قبلاً اعمال شده؟» دارد؛ بعد از git pull اولین ورود، جدول‌های جدید را خودکار می‌سازد.
 */
class Migrator
{
    /** نام فایل => شرط SQL که اگر 1 برگرداند یعنی اعمال شده است */
    private const MIGRATIONS = [
        '002_internal_tickets' => "SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'internal_tickets')
                                    AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'admins' AND column_name = 'department_id')",
        '003_profit_sharing' => "SELECT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'project_shares')
                                    AND EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'projects' AND column_name = 'profit_sharing')",
    ];

    public static function run(): void
    {
        $version = (string)count(self::MIGRATIONS);
        if (($_SESSION['mm_migrated'] ?? null) === $version) {
            return;
        }
        $pdo = DB::pdo();
        $locked = (int)$pdo->query("SELECT GET_LOCK('mm_migrate', 15)")->fetchColumn();
        try {
            foreach (self::MIGRATIONS as $name => $check) {
                if ((int)$pdo->query($check)->fetchColumn() === 1) {
                    continue;
                }
                $file = APP_ROOT . '/database/migrations/' . $name . '.sql';
                if (!is_file($file)) {
                    continue;
                }
                foreach (self::split((string)file_get_contents($file)) as $st) {
                    if (preg_match('/^USE\b/i', $st)) {
                        continue;
                    }
                    $pdo->exec($st);
                }
            }
            $_SESSION['mm_migrated'] = $version;
        } catch (\Throwable $e) {
            http_response_code(500);
            exit('<div dir="rtl" style="font-family:tahoma;padding:30px"><h3>به‌روزرسانی خودکار دیتابیس انجام نشد</h3><p>فایل‌های داخل <code>database/migrations</code> را به‌ترتیب در phpMyAdmin اجرا کنید.</p>'
                . (config('debug') ? '<pre style="direction:ltr">' . e($e->getMessage()) . '</pre>' : '') . '</div>');
        } finally {
            if ($locked) {
                $pdo->query("SELECT RELEASE_LOCK('mm_migrate')");
            }
        }
    }

    /** جداکننده دستورهای SQL با در نظر گرفتن رشته‌ها و کامنت‌ها */
    public static function split(string $sql): array
    {
        $out = [];
        $cur = '';
        $q = null;
        $n = strlen($sql);
        for ($i = 0; $i < $n; $i++) {
            $c = $sql[$i];
            if ($q === null) {
                if ($c === '-' && substr($sql, $i, 3) === '-- ') {
                    $e = strpos($sql, "\n", $i);
                    $i = $e === false ? $n : $e;
                    continue;
                }
                if ($c === "'" || $c === '"' || $c === '`') {
                    $q = $c;
                } elseif ($c === ';') {
                    if (trim($cur) !== '') {
                        $out[] = trim($cur);
                    }
                    $cur = '';
                    continue;
                }
            } else {
                if ($c === '\\' && $q !== '`') {
                    $cur .= $c . ($sql[++$i] ?? '');
                    continue;
                }
                if ($c === $q) {
                    $q = null;
                }
            }
            $cur .= $c;
        }
        if (trim($cur) !== '') {
            $out[] = trim($cur);
        }
        return $out;
    }
}
