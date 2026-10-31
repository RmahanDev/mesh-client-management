<?php
namespace App\Core;

use PDO;
use RuntimeException;
use ZipArchive;

defined('APP_ROOT') or exit('No direct access');

/** پشتیبان‌گیری، بازیابی و ریست (بدون نیاز به mysqldump؛ فقط PHP + ZipArchive) */
class Backup
{
    /** جدول‌هایی که در «پاک‌سازی اطلاعات» حفظ می‌شوند */
    public const KEEP_ON_DATA_RESET = ['admins', 'admin_permissions', 'settings', 'departments', 'categories'];
    private const ALLOWED_STATEMENT = '/^(DROP TABLE IF EXISTS `[A-Za-z0-9_]+`|CREATE TABLE `[A-Za-z0-9_]+`|INSERT INTO `[A-Za-z0-9_]+`|SET (NAMES|FOREIGN_KEY_CHECKS|SQL_MODE|TIME_ZONE|UNIQUE_CHECKS)\b)/i';

    public static function dir(): string
    {
        $d = APP_ROOT . '/storage/backups';
        if (!is_dir($d)) {
            @mkdir($d, 0775, true);
        }
        foreach (['.htaccess' => "Require all denied\nDeny from all\n", 'index.php' => "<?php http_response_code(403); exit;\n"] as $f => $c) {
            if (!is_file("$d/$f")) {
                @file_put_contents("$d/$f", $c);
            }
        }
        return $d;
    }

    public static function uploadsDir(): string
    {
        return rtrim((string)config('storage_path'), '/\\');
    }

    public static function tables(): array
    {
        return array_map(fn($r) => (string)array_values($r)[0], DB::all('SHOW TABLES'));
    }

    /** نوشتن دامپ SQL در فایل */
    public static function dumpSql(string $file): void
    {
        $pdo = DB::pdo();
        $fh = fopen($file, 'wb');
        if (!$fh) {
            throw new RuntimeException('نوشتن فایل موقت ممکن نیست');
        }
        fwrite($fh, "-- Mesh Client Management backup\n-- " . date('Y-m-d H:i:s') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");
        foreach (self::tables() as $t) {
            $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM)[1];
            fwrite($fh, "DROP TABLE IF EXISTS `$t`;\n$create;\n\n");
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
            $st = $pdo->query("SELECT * FROM `$t`");
            $cols = null;
            $batch = [];
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $cols ??= '`' . implode('`,`', array_keys($row)) . '`';
                $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v), $row)) . ')';
                if (count($batch) >= 200) {
                    fwrite($fh, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            $st->closeCursor();
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            if ($batch) {
                fwrite($fh, "INSERT INTO `$t` ($cols) VALUES\n" . implode(",\n", $batch) . ";\n");
            }
            fwrite($fh, "\n");
        }
        fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\n");
        fclose($fh);
    }

    /** ساخت فایل zip شامل دیتابیس (و در صورت نیاز فایل‌های آپلودی) */
    public static function createZip(string $zipPath, bool $withFiles): void
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('افزونه zip در PHP فعال نیست');
        }
        $sql = $zipPath . '.sql.tmp';
        self::dumpSql($sql);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($sql);
            throw new RuntimeException('ساخت فایل zip ممکن نیست');
        }
        $zip->addFile($sql, 'database.sql');
        $files = 0;
        if ($withFiles && is_dir(self::uploadsDir())) {
            $base = self::uploadsDir();
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $f) {
                if ($f->isFile() && $f->getFilename() !== 'index.php') {
                    $zip->addFile($f->getPathname(), 'uploads/' . str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1)));
                    $files++;
                }
            }
        }
        $zip->addFromString('manifest.json', json_encode(['app' => 'mesh-client-management', 'created_at' => date('c'), 'tables' => count(self::tables()), 'files' => $files, 'with_files' => $withFiles], JSON_UNESCAPED_UNICODE));
        $zip->close();
        @unlink($sql);
    }

    /** ذخیره بکاپ روی سرور؛ مسیر فایل را برمی‌گرداند */
    public static function saveToServer(string $tag, bool $withFiles = true): string
    {
        $path = self::dir() . '/' . $tag . '-' . date('Ymd-His') . '.zip';
        self::createZip($path, $withFiles);
        return $path;
    }

    public static function listServer(): array
    {
        $out = [];
        foreach (glob(self::dir() . '/*.zip') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    /** بازیابی از فایل zip یا sql آپلودشده */
    public static function restore(string $tmpFile, string $origName, ?callable $beforeApply = null): array
    {
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $sqlFile = null;
        $zip = null;
        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                throw new RuntimeException('فایل zip معتبر نیست');
            }
            $man = $zip->getFromName('manifest.json');
            if ($zip->locateName('database.sql') === false || $man === false || (json_decode($man, true)['app'] ?? '') !== 'mesh-client-management') {
                throw new RuntimeException('این فایل، پشتیبان این برنامه نیست');
            }
            $sqlFile = $tmpFile . '.sql';
            file_put_contents($sqlFile, $zip->getFromName('database.sql'));
        } elseif ($ext === 'sql') {
            $sqlFile = $tmpFile;
        } else {
            throw new RuntimeException('فقط فایل zip یا sql پشتیبان پذیرفته می‌شود');
        }
        $statements = Migrator::split((string)file_get_contents($sqlFile));
        $hasAdmins = false;
        foreach ($statements as $st) {
            if (!preg_match(self::ALLOWED_STATEMENT, $st)) {
                throw new RuntimeException('فایل شامل دستور غیرمجاز است و بازیابی انجام نشد');
            }
            if (stripos($st, 'CREATE TABLE `admins`') === 0) {
                $hasAdmins = true;
            }
        }
        if (!$hasAdmins) {
            throw new RuntimeException('فایل پشتیبان ناقص است (جدول ادمین‌ها وجود ندارد)');
        }
        if ($beforeApply) {
            $beforeApply();   // مثلاً بکاپ ایمنی؛ فقط بعد از معتبر بودن فایل
        }
        $pdo = DB::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($statements as $st) {
            $pdo->exec($st);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $restored = 0;
        if ($zip) {
            self::clearUploads();
            $base = self::uploadsDir();
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (!str_starts_with($name, 'uploads/') || str_ends_with($name, '/') || str_contains($name, '..') || str_contains($name, '\\')) {
                    continue;
                }
                $rel = substr($name, 8);
                if (preg_match('/\.(php\d?|phtml|phar|htaccess)$/i', $rel)) {
                    continue;
                }
                $dest = $base . '/' . $rel;
                if (!is_dir(dirname($dest))) {
                    @mkdir(dirname($dest), 0775, true);
                }
                file_put_contents($dest, $zip->getFromIndex($i));
                $restored++;
            }
            $zip->close();
            @unlink($sqlFile);
        }
        unset($_SESSION['mm_migrated']);
        return ['tables' => count(array_filter($statements, fn($s) => stripos($s, 'CREATE TABLE') === 0)), 'files' => $restored];
    }

    public static function clearUploads(): void
    {
        $base = self::uploadsDir();
        if (!is_dir($base)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $f) {
            if ($f->isDir()) {
                @rmdir($f->getPathname());
            } elseif ($f->getFilename() !== 'index.php') {
                @unlink($f->getPathname());
            }
        }
    }

    /** پاک‌سازی همه داده‌ها به‌جز ادمین‌ها، دسترسی‌ها، تنظیمات، دپارتمان‌ها و دسته‌بندی‌ها */
    public static function resetData(): int
    {
        $pdo = DB::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $n = 0;
        foreach (self::tables() as $t) {
            if (!in_array($t, self::KEEP_ON_DATA_RESET, true)) {
                $pdo->exec("TRUNCATE TABLE `$t`");
                $n++;
            }
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        self::clearUploads();
        return $n;
    }

    /** حذف همه جدول‌ها و فایل پیکربندی تا برنامه دوباره از صفحه نصب راه‌اندازی شود */
    public static function factoryReset(): void
    {
        $pdo = DB::pdo();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (self::tables() as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        self::clearUploads();
        @unlink(APP_ROOT . '/config/config.php');
    }
}
