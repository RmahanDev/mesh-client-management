<?php defined('APP_ROOT') or exit;
/** مودال‌های پشتیبانی: تمدید و تیکت */ ?>
<?= modal_start('renewalModal', 'تمدید پشتیبانی', 'bi-arrow-repeat', url('support/renewal-save'), '', ['id' => ''] + (!empty($p) ? ['project_id' => $p['id']] : [])) ?>
    <div class="row g-3">
        <?php if (empty($p)): ?>
            <div class="col-12"><label class="form-label">پروژه <span class="req">*</span></label><select name="project_id" class="form-select" required><option value="">انتخاب...</option><?php foreach ($projectOptions ?? [] as $po): ?><option value="<?= $po['id'] ?>"><?= e($po['title'] . ' — ' . $po['customer']) ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <div class="col-md-6"><label class="form-label">مدت تمدید (ماه) <span class="req">*</span></label><input type="number" min="1" name="months" class="form-control" value="3" required></div>
        <div class="col-md-6"><label class="form-label">مبلغ تمدید (تومان)</label><input type="text" name="amount" class="form-control money" inputmode="numeric" placeholder="۰ = رایگان"></div>
        <div class="col-md-6"><label class="form-label">شروع از تاریخ</label><input type="text" name="start_date" class="form-control jdate" autocomplete="off"><div class="form-text">خالی = از پایان پشتیبانی فعلی (یا امروز)</div></div>
        <div class="col-md-6"><label class="form-label">وضعیت پرداخت</label>
            <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" name="is_paid" value="1" id="renPaid"><label class="form-check-label" for="renPaid">پرداخت شده</label></div></div>
        <div class="col-md-6"><label class="form-label">تاریخ پرداخت</label><input type="text" name="paid_date" class="form-control jdate" autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label">توضیحات</label><input type="text" name="notes" class="form-control"></div>
        <div class="col-12 new-only"><?php $dzName = 'files'; $dzLabel = 'فاکتور / رسید تمدید'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end('ثبت تمدید') ?>

<?= modal_start('ticketModal', 'تیکت / درخواست پشتیبانی', 'bi-ticket-detailed', url('support/ticket-save'), 'modal-lg', ['id' => ''] + (!empty($p) ? ['project_id' => $p['id']] : [])) ?>
    <div class="row g-3">
        <?php if (empty($p)): ?>
            <div class="col-12"><label class="form-label">پروژه <span class="req">*</span></label><select name="project_id" class="form-select" required><option value="">انتخاب...</option><?php foreach ($projectOptions ?? [] as $po): ?><option value="<?= $po['id'] ?>"><?= e($po['title'] . ' — ' . $po['customer']) ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <div class="col-md-8"><label class="form-label">موضوع <span class="req">*</span></label><input type="text" name="subject" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">اولویت</label><select name="priority" class="form-select"><?php foreach (['low', 'normal', 'high', 'urgent'] as $pr): ?><option value="<?= $pr ?>" <?= $pr === 'normal' ? 'selected' : '' ?>><?= priority_label($pr) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">کانال درخواست</label><select name="channel" class="form-select"><option value="">—</option><?php foreach (['phone' => 'تماس تلفنی', 'telegram' => 'تلگرام', 'whatsapp' => 'واتس‌اپ', 'email' => 'ایمیل', 'inperson' => 'حضوری', 'other' => 'سایر'] as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">وضعیت</label><select name="status" class="form-select"><?php foreach (['open', 'in_progress', 'answered', 'closed'] as $s): ?><option value="<?= $s ?>"><?= ticket_status_label($s) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="form-label">شرح درخواست</label><textarea name="description" class="form-control" rows="4"></textarea></div>
        <div class="col-12 new-only"><?php $dzName = 'files'; $dzLabel = 'اسکرین‌شات یا فایل مرتبط'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end('ذخیره تیکت') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#renewalModal form, #ticketModal form').forEach(f =>
        f.addEventListener('mm:filled', e => f.querySelector('.new-only')?.classList.toggle('d-none', !!e.detail.id)));
});
</script>
