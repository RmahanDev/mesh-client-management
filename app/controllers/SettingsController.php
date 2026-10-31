<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Backup;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Settings;

defined('APP_ROOT') or exit('No direct access');

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->requireSuper();
        $data = ['title' => 'تنظیمات', 'backups' => Backup::listServer(), 'zipOk' => class_exists('ZipArchive')];
        $this->render('settings/index', $data + (new AdminsController())->data());
    }

    public function save(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $name = (string)input('company_name');
        if ($name === '') {
            json_error('نام سیستم الزامی است');
        }
        Settings::set('company_name', mb_substr($name, 0, 100));
        Settings::set('reminder_days', max(1, min(60, input_int('reminder_days', 7))));
        Settings::set('upload_max_mb', max(1, min(100, input_int('upload_max_mb', 10))));
        Settings::set('default_support_months', max(0, min(120, input_int('default_support_months', 3))));
        unset($_SESSION['_notif_check']);
        json_ok('تنظیمات ذخیره شد');
    }

    /** دانلود مستقیم بکاپ (فرم معمولی، نه AJAX) */
    public function backupDownload(): void
    {
        $this->requirePost();
        $this->requireSuper();
        @set_time_limit(0);
        $tmp = tempnam(sys_get_temp_dir(), 'mmb');
        try {
            Backup::createZip($tmp, (bool)input('with_files'));
        } catch (\Throwable $e) {
            @unlink($tmp);
            http_response_code(500);
            exit('خطا در ساخت بکاپ: ' . e($e->getMessage()));
        }
        $name = 'mesh-backup-' . date('Ymd-His') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    public function backupSave(): void
    {
        $this->requirePost();
        $this->requireSuper();
        @set_time_limit(0);
        try {
            $path = Backup::saveToServer('manual', (bool)input('with_files'));
        } catch (\Throwable $e) {
            json_error('بکاپ ساخته نشد: ' . $e->getMessage(), 500);
        }
        json_ok('بکاپ روی سرور ذخیره شد (' . basename($path) . ')');
    }

    public function backupFile(): void
    {
        $this->requireSuper();
        $name = basename((string)input('name'));
        $path = Backup::dir() . '/' . $name;
        if (!preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name) || !is_file($path)) {
            \App\Core\Router::notFound();
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function backupDelete(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $name = basename((string)input('name'));
        $path = Backup::dir() . '/' . $name;
        if (!preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name) || !is_file($path)) {
            json_error('فایل پیدا نشد', 404);
        }
        @unlink($path);
        json_ok('بکاپ حذف شد');
    }

    public function restore(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $this->confirmDanger('بازگردانی');
        $f = $_FILES['backup'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
            json_error($f && $f['error'] === UPLOAD_ERR_INI_SIZE ? 'حجم فایل از محدودیت PHP سرور بیشتر است' : 'فایل بکاپ را انتخاب کنید');
        }
        @set_time_limit(0);
        $safety = null;
        try {
            $r = Backup::restore($f['tmp_name'], $f['name'], function () use (&$safety) {
                $safety = $this->safetyBackup();
            });
        } catch (\Throwable $e) {
            json_error('بازگردانی انجام نشد: ' . $e->getMessage() . ($safety ? " (بکاپ ایمنی قبل از عملیات: $safety)" : ''), 422);
        }
        Auth::logout();
        json_ok('بازگردانی انجام شد؛ ' . fa_digits($r['tables']) . ' جدول و ' . fa_digits($r['files']) . ' فایل. دوباره وارد شوید.', ['redirect' => url('auth/login')]);
    }

    public function reset(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $mode = (string)input('mode');
        if (!in_array($mode, ['data', 'factory'], true)) {
            json_error('نوع ریست نامعتبر است');
        }
        $this->confirmDanger($mode === 'data' ? 'پاک‌سازی' : 'ریست کامل');
        if ($mode === 'factory' && !is_writable(APP_ROOT . '/config') ) {
            json_error('دسترسی حذف فایل config وجود ندارد؛ ریست کامل ممکن نیست');
        }
        @set_time_limit(0);
        $safety = $this->safetyBackup();
        try {
            if ($mode === 'data') {
                $n = Backup::resetData();
                unset($_SESSION['_notif_check']);
                json_ok('اطلاعات پاک شد (' . fa_digits($n) . ' جدول). ادمین‌ها، تنظیمات، دپارتمان‌ها و دسته‌بندی‌ها حفظ شدند.' . ($safety ? " بکاپ ایمنی: $safety" : ''), ['redirect' => url('settings')]);
            }
            Backup::factoryReset();
        } catch (\Throwable $e) {
            json_error('ریست انجام نشد: ' . $e->getMessage(), 500);
        }
        Auth::logout();
        json_ok('برنامه به حالت اولیه برگشت. به صفحه نصب می‌روید.', ['redirect' => base_url() . '/install/']);
    }

    /** رمز مدیر + عبارت تأیید برای عملیات خطرناک */
    private function confirmDanger(string $phrase): void
    {
        $row = DB::one('SELECT password_hash FROM admins WHERE id = ?', [Auth::id()]);
        if (!$row || !password_verify((string)($_POST['password'] ?? ''), $row['password_hash'])) {
            json_error('رمز عبور شما اشتباه است', 422, ['errors' => ['password' => 'اشتباه']]);
        }
        if (trim((string)($_POST['phrase'] ?? '')) !== $phrase) {
            json_error('برای تأیید باید عبارت «' . $phrase . '» را دقیقاً بنویسید', 422, ['errors' => ['phrase' => 'اشتباه']]);
        }
    }

    private function safetyBackup(): ?string
    {
        if (!class_exists('ZipArchive')) {
            return null;
        }
        try {
            return basename(Backup::saveToServer('before-change'));
        } catch (\Throwable $e) {
            json_error('بکاپ ایمنی قبل از عملیات ساخته نشد، پس عملیات انجام نشد: ' . $e->getMessage(), 500);
        }
    }
}
