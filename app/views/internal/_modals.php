<?php defined('APP_ROOT') or exit;
/** فیلدهای انتخاب دپارتمان و مسئول؛ با انتخاب مسئول، دپارتمان او خودکار تنظیم می‌شود */
function it_target_fields(array $departments, array $admins, bool $required): void { ?>
    <div class="col-md-6"><label class="form-label">دپارتمان مقصد <?= $required ? '<span class="req">*</span>' : '' ?></label>
        <select name="to_department_id" class="form-select" data-it-dept><option value="">انتخاب...</option><?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">ادمین مسئول</label>
        <select name="assigned_to" class="form-select" data-it-admin><option value="">بدون مسئول مشخص (همه اعضای دپارتمان)</option>
            <?php foreach ($admins as $a): ?><option value="<?= $a['id'] ?>" data-dept="<?= (int)$a['department_id'] ?>"><?= e($a['name']) ?><?= $a['dept_name'] ? ' — ' . e($a['dept_name']) : '' ?></option><?php endforeach; ?></select>
        <div class="form-text">مسئول انتخاب‌شده اعلان اختصاصی دریافت می‌کند.</div></div>
<?php } ?>
<?= modal_start('itModal', 'تیکت داخلی', 'bi-send', url('internal/save'), 'modal-lg') ?>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label">موضوع <span class="req">*</span></label><input type="text" name="subject" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">اولویت</label><select name="priority" class="form-select"><?php foreach (['low', 'normal', 'high', 'urgent'] as $pr): ?><option value="<?= $pr ?>" <?= $pr === 'normal' ? 'selected' : '' ?>><?= priority_label($pr) ?></option><?php endforeach; ?></select></div>
        <?php it_target_fields($departments, $admins, true); ?>
        <?php if (!empty($projects)): ?><div class="col-12"><label class="form-label">پروژه مرتبط</label><select name="project_id" class="form-select"><option value="">—</option><?php foreach ($projects as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['title']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
        <div class="col-12"><label class="form-label">شرح</label><textarea name="description" class="form-control" rows="4"></textarea></div>
        <div class="col-12"><?php $dzName = 'files'; $dzLabel = 'فایل پیوست'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end('ارسال تیکت') ?>
<script>
document.addEventListener('change', e => {
    if (!e.target.matches('[data-it-admin]')) return;
    const d = e.target.selectedOptions[0]?.dataset.dept;
    const sel = e.target.form.querySelector('[data-it-dept]');
    if (d && d !== '0' && sel) sel.value = d;
});
</script>
