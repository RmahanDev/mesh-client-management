<?php defined('APP_ROOT') or exit;
use App\Core\Settings;
$serverMax = ini_get('upload_max_filesize'); ?>
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><span>تنظیمات عمومی</span>
        <button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#settingsModal" data-fill='<?= json_attr(['company_name' => Settings::get('company_name'), 'reminder_days' => Settings::get('reminder_days', 7), 'upload_max_mb' => Settings::get('upload_max_mb', 10), 'default_support_months' => Settings::get('default_support_months', 3)]) ?>'><i class="bi bi-pencil"></i> ویرایش</button></div>
    <div class="card-body">
        <div class="kv"><span class="k">نام سیستم</span><span class="v"><?= e(Settings::get('company_name')) ?></span></div>
        <div class="kv"><span class="k">یادآوری قبل از سررسید</span><span class="v"><?= fa_digits(Settings::get('reminder_days', 7)) ?> روز</span></div>
        <div class="kv"><span class="k">مدت پیش‌فرض پشتیبانی</span><span class="v"><?= fa_digits(Settings::get('default_support_months', 3)) ?> ماه</span></div>
        <div class="kv"><span class="k">حداکثر حجم هر فایل</span><span class="v"><?= fa_digits(Settings::get('upload_max_mb', 10)) ?> مگابایت <span class="small text-muted">(محدودیت PHP سرور: <span class="ltr"><?= e($serverMax) ?></span>)</span></span></div>
        <div class="kv"><span class="k">نسخه PHP</span><span class="v ltr"><?= PHP_VERSION ?></span></div>
        <div class="kv"><span class="k">خروجی Excel</span><span class="v"><?= class_exists('ZipArchive') ? '<span class="text-success">xlsx فعال</span>' : '<span class="text-warning">افزونه zip غیرفعال — خروجی CSV</span>' ?></span></div>
    </div>
</div>
</div></div>
<?= modal_start('settingsModal', 'ویرایش تنظیمات', 'bi-gear', url('settings/save')) ?>
    <div class="row g-3">
        <div class="col-12"><label class="form-label">نام سیستم</label><input type="text" name="company_name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">یادآوری (روز قبل)</label><input type="number" min="1" max="60" name="reminder_days" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">پشتیبانی پیش‌فرض (ماه)</label><input type="number" min="0" name="default_support_months" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">حداکثر حجم فایل (MB)</label><input type="number" min="1" max="100" name="upload_max_mb" class="form-control"></div>
    </div>
<?= modal_end() ?>
