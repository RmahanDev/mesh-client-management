<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$levels = [0 => 'بدون دسترسی', 1 => 'فقط مشاهده', 2 => 'مدیریت کامل']; ?>
<div class="alert alert-info small"><i class="bi bi-info-circle me-1"></i>مدیر کل به همه بخش‌ها دسترسی دارد. برای سایر ادمین‌ها مشخص کنید هر بخش را <b>نبینند</b>، <b>فقط ببینند</b> یا <b>مدیریت کنند</b> (ثبت، ویرایش، حذف).</div>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>ادمین‌ها</span>
        <button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#adminModal" data-title="ادمین جدید" data-fill='<?= json_attr(['is_active' => 1]) ?>'><i class="bi bi-person-plus"></i> ادمین جدید</button>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>ادمین</th><th>نقش</th><th>دپارتمان</th><?php foreach (Auth::SECTIONS as [$l, $i]): ?><th class="text-center"><i class="bi <?= $i ?>" title="<?= e($l) ?>" data-bs-toggle="tooltip"></i></th><?php endforeach; ?><th>آخرین ورود</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): $pp = $perms[$a['id']] ?? []; ?>
                <tr class="<?= $a['is_active'] ? '' : 'opacity-50' ?>">
                    <td><div class="d-flex align-items-center gap-2"><span class="avatar"><?= e(mb_substr($a['name'], 0, 1)) ?></span><div><div class="fw-semibold"><?= e($a['name']) ?></div><div class="small text-muted ltr">@<?= e($a['username']) ?></div></div></div></td>
                    <td><?= $a['is_super'] ? '<span class="badge bs-primary">مدیر کل</span>' : '<span class="badge bs-secondary">ادمین</span>' ?><?= $a['is_active'] ? '' : ' <span class="badge bs-danger">غیرفعال</span>' ?></td>
                    <td class="small"><?= $a['dept_name'] ? e($a['dept_name']) : '<span class="text-muted">—</span>' ?></td>
                    <?php foreach (Auth::SECTIONS as $sec => $_): $lv = $a['is_super'] ? 2 : ($pp[$sec] ?? 0); ?>
                        <td class="text-center"><?= [0 => '<i class="bi bi-dash text-muted"></i>', 1 => '<i class="bi bi-eye text-info" title="مشاهده"></i>', 2 => '<i class="bi bi-pencil-square text-success" title="مدیریت"></i>'][$lv] ?></td>
                    <?php endforeach; ?>
                    <td class="small"><?= $a['last_login_at'] ? jdate($a['last_login_at'], true) : '—' ?></td>
                    <td class="text-nowrap text-start">
                        <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#adminModal" data-title="ویرایش ادمین" data-fill='<?= json_attr(array_merge($a, ['perm' => $pp])) ?>'><i class="bi bi-pencil"></i></button>
                        <?php if ($a['id'] != Auth::id()): ?><button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('admins/delete') ?>" data-post='<?= json_attr(['id' => $a['id']]) ?>' data-confirm="ادمین «<?= e($a['name']) ?>» حذف شود؟"><i class="bi bi-trash"></i></button><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-diagram-3 me-1 text-primary"></i>دپارتمان‌ها (برای تیکت داخلی)</span>
        <button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#deptModal" data-title="دپارتمان جدید" data-fill='<?= json_attr(['color' => '#3b82f6', 'sort_order' => 0]) ?>'><i class="bi bi-plus-lg"></i> دپارتمان</button>
    </div>
    <div class="list-group list-group-flush">
        <?php foreach ($departments as $d): ?>
            <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                <div><span class="badge" style="background:<?= e($d['color']) ?>">&nbsp;</span> <b class="ms-1"><?= e($d['name']) ?></b> <span class="small text-muted">· <?= fa_digits($d['members']) ?> عضو</span></div>
                <div>
                    <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#deptModal" data-title="ویرایش دپارتمان" data-fill='<?= json_attr($d) ?>'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('departments/delete') ?>" data-post='<?= json_attr(['id' => $d['id']]) ?>' data-confirm="دپارتمان «<?= e($d['name']) ?>» حذف شود؟"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$departments): ?><div class="empty-state">دپارتمانی تعریف نشده</div><?php endif; ?>
    </div>
</div>
<?= modal_start('deptModal', 'دپارتمان', 'bi-diagram-3', url('departments/save'), '', ['id' => '']) ?>
    <div class="row g-3">
        <div class="col-12"><label class="form-label">نام دپارتمان <span class="req">*</span></label><input type="text" name="name" class="form-control" required></div>
        <div class="col-6"><label class="form-label">رنگ</label><input type="color" name="color" class="form-control form-control-color w-100" value="#3b82f6"></div>
        <div class="col-6"><label class="form-label">ترتیب نمایش</label><input type="number" name="sort_order" class="form-control" value="0"></div>
    </div>
<?= modal_end() ?>

<?= modal_start('adminModal', 'ادمین', 'bi-shield-check', url('admins/save'), 'modal-lg', ['id' => '']) ?>
    <div class="row g-3">
        <div class="col-md-4"><label class="form-label">نام <span class="req">*</span></label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">نام کاربری (انگلیسی) <span class="req">*</span></label><input type="text" name="username" class="form-control ltr" required autocomplete="off"></div>
        <div class="col-md-4"><label class="form-label">موبایل</label><input type="text" name="mobile" class="form-control ltr digits"></div>
        <div class="col-md-6"><label class="form-label">دپارتمان</label><select name="department_id" class="form-select"><option value="">بدون دپارتمان</option><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label">رمز عبور <span class="small text-muted" data-pass-hint>(حداقل ۸ کاراکتر)</span></label><input type="password" name="password" class="form-control ltr" autocomplete="new-password"></div>
        <div class="col-12 d-flex align-items-end gap-4">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="admActive"><label class="form-check-label" for="admActive">فعال</label></div>
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_super" value="1" id="admSuper"><label class="form-check-label" for="admSuper">مدیر کل (دسترسی کامل)</label></div>
        </div>
    </div>
    <div class="section-box mt-3" data-perm-box>
        <div class="section-box-title"><i class="bi bi-sliders text-primary"></i> سطح دسترسی هر بخش</div>
        <table class="table table-sm perm-table mb-0">
            <thead><tr><th>بخش</th><?php foreach ($levels as $l): ?><th><?= $l ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach (Auth::SECTIONS as $sec => [$label, $icon]): ?>
                <tr><td><i class="bi <?= $icon ?> me-1 text-primary"></i><?= e($label) ?></td>
                    <?php foreach ($levels as $lv => $_): ?><td><input class="form-check-input" type="radio" name="perm[<?= $sec ?>]" value="<?= $lv ?>" <?= $lv === 0 ? 'checked' : '' ?>></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?= modal_end() ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const f = document.querySelector('#adminModal form');
    const sup = f.querySelector('[name=is_super]');
    const sync = () => f.querySelector('[data-perm-box]').classList.toggle('d-none', sup.checked);
    sup.addEventListener('change', sync);
    f.addEventListener('mm:filled', e => {
        const perm = e.detail.perm || {};
        f.querySelectorAll('input[name^="perm["]').forEach(r => {
            const sec = r.name.slice(5, -1);
            r.checked = String(perm[sec] || 0) === r.value;
        });
        f.querySelector('[data-pass-hint]').textContent = e.detail.id ? '(خالی = بدون تغییر)' : '(حداقل ۸ کاراکتر)';
        sync();
    });
});
</script>
