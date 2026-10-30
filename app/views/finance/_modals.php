<?php defined('APP_ROOT') or exit;
/** مودال‌های مالی: ثبت دریافت، ویرایش ردیف پرداخت، هزینه، سفته، ضامن */ ?>

<?= modal_start('receiveModal', 'ثبت دریافت وجه', 'bi-wallet2', url('finance/receive'), '', ['payment_id' => '']) ?>
    <div class="small text-muted mb-3" data-receive-info></div>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">مبلغ دریافتی (تومان) <span class="req">*</span></label><input type="text" name="amount" class="form-control money" required inputmode="numeric"></div>
        <div class="col-md-6"><label class="form-label">تاریخ دریافت <span class="req">*</span></label><input type="text" name="paid_date" class="form-control jdate" required autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label">روش پرداخت</label><select name="method" class="form-select"><?= method_options('card') ?></select></div>
        <div class="col-md-6"><label class="form-label">شماره پیگیری / چک</label><input type="text" name="ref_no" class="form-control ltr"></div>
        <div class="col-12"><label class="form-label">توضیحات</label><input type="text" name="notes" class="form-control"></div>
        <div class="col-12"><label class="form-label">رسید / فیش (چند فایل)</label><?php $dzName = 'files'; $dzLabel = 'تصویر فیش یا رسید پرداخت'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end('ثبت دریافت', 'btn-success') ?>

<?= modal_start('paymentModal', 'ردیف پرداخت', 'bi-calendar-check', url('finance/payment-save'), '', ['id' => ''] + (!empty($p) ? ['project_id' => $p['id']] : [])) ?>
    <div class="row g-3">
        <div class="col-12"><label class="form-label">عنوان</label><input type="text" name="title" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">نوع</label>
            <select name="kind" class="form-select"><option value="single">یک‌جا</option><option value="stage">مرحله</option><option value="prepayment">پیش‌پرداخت</option><option value="installment">قسط</option></select></div>
        <div class="col-md-6"><label class="form-label">مبلغ (تومان)</label><input type="text" name="amount" class="form-control money" required inputmode="numeric"></div>
        <div class="col-md-6"><label class="form-label">تاریخ سررسید (اختیاری برای مراحل)</label><input type="text" name="due_date" class="form-control jdate" autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label">یادداشت</label><input type="text" name="notes" class="form-control"></div>
    </div>
    <div class="small text-muted mt-3"><i class="bi bi-info-circle"></i> تغییر مبلغ یک ردیف، جمع برنامه پرداخت را تغییر می‌دهد؛ اختلاف با مبلغ کل در بالای جدول نمایش داده می‌شود.</div>
<?= modal_end() ?>

<?= modal_start('expenseModal', 'هزینه / ابزار پروژه', 'bi-tools', url('finance/expense-save'), '', ['id' => ''] + (!empty($p) ? ['project_id' => $p['id']] : [])) ?>
    <div class="row g-3">
        <?php if (empty($p)): ?>
            <div class="col-12"><label class="form-label">پروژه <span class="req">*</span></label><select name="project_id" class="form-select" required><option value="">انتخاب...</option><?php foreach ($projectOptions ?? [] as $po): ?><option value="<?= $po['id'] ?>"><?= e($po['title'] . ' — ' . $po['customer']) ?></option><?php endforeach; ?></select></div>
        <?php endif; ?>
        <div class="col-12"><label class="form-label">عنوان هزینه <span class="req">*</span></label><input type="text" name="title" class="form-control" required placeholder="مثلاً: خرید قالب، هاست یک‌ساله، لایسنس افزونه"></div>
        <div class="col-md-6"><label class="form-label">مبلغ (تومان) <span class="req">*</span></label><input type="text" name="amount" class="form-control money" required inputmode="numeric"></div>
        <div class="col-md-6"><label class="form-label">تاریخ <span class="req">*</span></label><input type="text" name="expense_date" class="form-control jdate" required autocomplete="off"></div>
        <div class="col-12"><label class="form-label">توضیحات</label><input type="text" name="notes" class="form-control"></div>
        <div class="col-12 new-only"><?php $dzName = 'files'; $dzLabel = 'فاکتور خرید (اختیاری)'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end() ?>

<?php if (!empty($p)): ?>
<?= modal_start('promissoryModal', 'سفته', 'bi-file-earmark-ruled', url('finance/promissory-save'), '', ['id' => '', 'project_id' => $p['id']]) ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">مبلغ کل سفته‌ها (تومان)</label><input type="text" name="amount" class="form-control money" required inputmode="numeric"></div>
        <div class="col-md-6"><label class="form-label">تعداد فقره</label><input type="number" min="1" name="count" class="form-control" value="1" required></div>
        <div class="col-md-8"><label class="form-label">سریال‌ها</label><input type="text" name="serials" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">وضعیت</label><select name="status" class="form-select"><option value="held">نزد ما</option><option value="returned">عودت داده شد</option></select></div>
        <div class="col-12"><label class="form-label">توضیحات</label><input type="text" name="notes" class="form-control"></div>
        <div class="col-12 new-only"><?php $dzName = 'files'; $dzLabel = 'تصویر سفته‌ها'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end() ?>

<?= modal_start('guarantorModal', 'ضامن', 'bi-person-badge', url('finance/guarantor-save'), 'modal-lg', ['id' => '', 'project_id' => $p['id']]) ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">نام و نام خانوادگی <span class="req">*</span></label><input type="text" name="full_name" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">شماره تماس</label><input type="text" name="mobile" class="form-control ltr digits" inputmode="tel"></div>
        <div class="col-md-6"><label class="form-label">کد ملی</label><input type="text" name="national_code" class="form-control ltr digits" maxlength="10" inputmode="numeric"></div>
        <div class="col-md-6"><label class="form-label">تاریخ تولد</label><input type="text" name="birth_date" class="form-control jdate" autocomplete="off"></div>
        <div class="col-12"><label class="form-label">آدرس</label><input type="text" name="address" class="form-control"></div>
        <div class="col-12"><label class="form-label">توضیحات</label><input type="text" name="notes" class="form-control"></div>
        <div class="col-12 new-only"><?php $dzName = 'files'; $dzLabel = 'مدارک ضامن'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end() ?>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#expenseModal form, #promissoryModal form, #guarantorModal form').forEach(f =>
        f.addEventListener('mm:filled', e => f.querySelector('.new-only')?.classList.toggle('d-none', !!e.detail.id)));
    const rf = document.querySelector('#receiveModal form');
    rf?.addEventListener('mm:filled', e => {
        rf.querySelector('[data-receive-info]').textContent = e.detail._info || '';
        if (!e.detail.paid_date) rf.querySelector('[name=paid_date]').value = MM.jalali.format(MM.jalali.today());
    });
    const ef = document.querySelector('#expenseModal form');
    ef?.addEventListener('mm:filled', e => { if (!e.detail.expense_date) ef.querySelector('[name=expense_date]').value = MM.jalali.format(MM.jalali.today()); });
});
</script>
