<?php defined('APP_ROOT') or exit;
use App\Core\Settings;
use App\Core\Auth;
$serverMax = ini_get('upload_max_filesize'); ?>
<ul class="nav nav-pills nav-pills-mm mb-3 flex-nowrap overflow-auto">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-general"><i class="bi bi-gear me-1"></i>عمومی</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-admins"><i class="bi bi-shield-check me-1"></i>مدیران و دسترسی</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-password"><i class="bi bi-key me-1"></i>تغییر رمز</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-backup"><i class="bi bi-cloud-arrow-down me-1"></i>پشتیبان‌گیری و بازیابی</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-reset"><i class="bi bi-exclamation-octagon me-1"></i>ریست</button></li>
</ul>
<div class="tab-content">
<div class="tab-pane fade show active" id="tab-general">
<div class="row"><div class="col-xl-8">
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
</div>

<div class="tab-pane fade" id="tab-admins">
<?php require APP_ROOT . '/app/views/admins/_manage.php'; ?>
</div>

<div class="tab-pane fade" id="tab-password">
<div class="row"><div class="col-lg-6 col-xl-5">
    <div class="card"><div class="card-header"><i class="bi bi-key me-1 text-primary"></i>تغییر رمز عبور من</div>
    <div class="card-body">
        <form class="ajax-form" method="post" action="<?= url('auth/password') ?>" data-reload="false"><?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">رمز فعلی</label><input type="password" name="current" class="form-control ltr" required autocomplete="current-password"></div>
            <div class="mb-3"><label class="form-label">رمز جدید <span class="small text-muted">(حداقل ۸ کاراکتر)</span></label><input type="password" name="password" class="form-control ltr" required minlength="8" autocomplete="new-password"></div>
            <div class="mb-3"><label class="form-label">تکرار رمز جدید</label><input type="password" name="password_confirm" class="form-control ltr" required autocomplete="new-password"></div>
            <button class="btn btn-grad"><i class="bi bi-check2 me-1"></i>تغییر رمز</button>
        </form>
        <div class="small text-muted mt-3">رمز سایر مدیران را از تب «مدیران و دسترسی» (ویرایش هر ادمین) تغییر دهید.</div>
    </div></div>
</div></div>
</div>

<div class="tab-pane fade" id="tab-backup">
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header"><i class="bi bi-download me-1 text-success"></i>گرفتن بکاپ</div>
        <div class="card-body">
            <p class="small text-muted">یک فایل zip شامل همه اطلاعات دیتابیس (مشتریان، پروژه‌ها، پرداخت‌ها، تیکت‌ها، ادمین‌ها و ...) و در صورت انتخاب، فایل‌های پیوست.</p>
            <?php if (!$zipOk): ?><div class="alert alert-warning small">افزونه zip در PHP فعال نیست؛ بکاپ ممکن نیست.</div><?php endif; ?>
            <form method="post" action="<?= url('settings/backup-download') ?>"><?= csrf_field() ?>
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="with_files" value="1" id="bkFiles" checked><label class="form-check-label" for="bkFiles">فایل‌های پیوست هم در بکاپ باشد</label></div>
                <button class="btn btn-grad" <?= $zipOk ? '' : 'disabled' ?>><i class="bi bi-download me-1"></i>دانلود بکاپ</button>
            </form>
            <hr>
            <form class="ajax-form" method="post" action="<?= url('settings/backup-save') ?>"><?= csrf_field() ?>
                <input type="hidden" name="with_files" value="1">
                <button class="btn btn-soft" <?= $zipOk ? '' : 'disabled' ?>><i class="bi bi-hdd me-1"></i>ذخیره یک بکاپ روی سرور</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-header"><i class="bi bi-upload me-1 text-warning"></i>بازگردانی از بکاپ</div>
        <div class="card-body">
            <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>بازگردانی، همه اطلاعات فعلی را با محتوای بکاپ جایگزین می‌کند. قبل از آن خودکار یک بکاپ ایمنی روی سرور ذخیره می‌شود.</div>
            <form class="ajax-form" method="post" action="<?= url('settings/restore') ?>" enctype="multipart/form-data" data-reload="false"><?= csrf_field() ?>
                <div class="mb-3"><label class="form-label">فایل بکاپ (zip یا sql)</label><input type="file" name="backup" class="form-control" accept=".zip,.sql" required></div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label">رمز عبور شما</label><input type="password" name="password" class="form-control ltr" required autocomplete="current-password"></div>
                    <div class="col-md-6"><label class="form-label">برای تأیید بنویسید «بازگردانی»</label><input type="text" name="phrase" class="form-control" required autocomplete="off"></div>
                </div>
                <button class="btn btn-warning"><i class="bi bi-arrow-counterclockwise me-1"></i>بازگردانی</button>
            </form>
        </div></div>
    </div>
    <div class="col-12">
        <div class="card"><div class="card-header"><i class="bi bi-hdd-stack me-1 text-primary"></i>بکاپ‌های ذخیره‌شده روی سرور (<?= fa_digits(count($backups)) ?>)</div>
            <div class="table-responsive"><table class="table">
                <thead><tr><th>فایل</th><th>حجم</th><th>تاریخ</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($backups as $b): ?>
                    <tr><td class="ltr text-start"><?= e($b['name']) ?></td><td><?= fa_digits(number_format($b['size'] / 1048576, 2)) ?> MB</td><td><?= jdate(date('Y-m-d H:i:s', $b['time']), true) ?></td>
                        <td class="text-nowrap text-start">
                            <a class="btn btn-sm btn-soft-primary btn-icon" href="<?= url('settings/backup-file', ['name' => $b['name']]) ?>" title="دانلود"><i class="bi bi-download"></i></a>
                            <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('settings/backup-delete') ?>" data-post='<?= json_attr(['name' => $b['name']]) ?>' data-confirm="این بکاپ حذف شود؟"><i class="bi bi-trash"></i></button>
                        </td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$backups): ?><div class="empty-state"><i class="bi bi-hdd"></i>بکاپی روی سرور نیست</div><?php endif; ?></div>
        </div>
    </div>
</div>
</div>

<div class="tab-pane fade" id="tab-reset">
<div class="alert alert-danger"><i class="bi bi-exclamation-octagon me-1"></i><b>منطقه خطر.</b> این عملیات برگشت‌پذیر نیست؛ فقط بکاپ ایمنی که پیش از اجرا روی سرور ذخیره می‌شود امکان برگشت می‌دهد.</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-warning h-100"><div class="card-header"><i class="bi bi-eraser me-1 text-warning"></i>پاک‌سازی اطلاعات</div>
        <div class="card-body">
            <p class="small text-muted">مشتریان، پروژه‌ها، پرداخت‌ها، هزینه‌ها، تیکت‌ها، سهم‌ها، اعلان‌ها و فایل‌های پیوست پاک می‌شوند. ادمین‌ها، دسترسی‌ها، تنظیمات، دپارتمان‌ها و دسته‌بندی‌ها می‌مانند.</p>
            <form class="ajax-form" method="post" action="<?= url('settings/reset') ?>" data-reload="false"><?= csrf_field() ?>
                <input type="hidden" name="mode" value="data">
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label">رمز عبور شما</label><input type="password" name="password" class="form-control ltr" required autocomplete="current-password"></div>
                    <div class="col-md-6"><label class="form-label">بنویسید «پاک‌سازی»</label><input type="text" name="phrase" class="form-control" required autocomplete="off"></div>
                </div>
                <button class="btn btn-warning"><i class="bi bi-eraser me-1"></i>پاک‌سازی اطلاعات</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card border-danger h-100"><div class="card-header"><i class="bi bi-trash3 me-1 text-danger"></i>ریست کامل (بازگشت به حالت نصب)</div>
        <div class="card-body">
            <p class="small text-muted">همه جدول‌ها (شامل ادمین‌ها) و فایل تنظیمات حذف می‌شود و به صفحه نصب برمی‌گردید تا از اول دیتابیس و مدیر کل بسازید.</p>
            <form class="ajax-form" method="post" action="<?= url('settings/reset') ?>" data-reload="false"><?= csrf_field() ?>
                <input type="hidden" name="mode" value="factory">
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label">رمز عبور شما</label><input type="password" name="password" class="form-control ltr" required autocomplete="current-password"></div>
                    <div class="col-md-6"><label class="form-label">بنویسید «ریست کامل»</label><input type="text" name="phrase" class="form-control" required autocomplete="off"></div>
                </div>
                <button class="btn btn-danger"><i class="bi bi-trash3 me-1"></i>ریست کامل</button>
            </form>
        </div></div>
    </div>
</div>
</div>
</div>
<?= modal_start('settingsModal', 'ویرایش تنظیمات', 'bi-gear', url('settings/save')) ?>
    <div class="row g-3">
        <div class="col-12"><label class="form-label">نام سیستم</label><input type="text" name="company_name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">یادآوری (روز قبل)</label><input type="number" min="1" max="60" name="reminder_days" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">پشتیبانی پیش‌فرض (ماه)</label><input type="number" min="0" name="default_support_months" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">حداکثر حجم فایل (MB)</label><input type="number" min="1" max="100" name="upload_max_mb" class="form-control"></div>
    </div>
<?= modal_end() ?>
