<?php defined('APP_ROOT') or exit; ?>
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form class="ajax-form" method="post" action="<?= url('customers/save') ?>" enctype="multipart/form-data" id="customerForm">
                <?= csrf_field() ?>
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i><span>مشتری جدید</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger form-alert d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">نام <span class="req">*</span></label><input type="text" name="first_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">نام خانوادگی <span class="req">*</span></label><input type="text" name="last_name" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">شرکت / برند</label><input type="text" name="company" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">کد ملی</label><input type="text" name="national_code" class="form-control ltr digits" maxlength="10" inputmode="numeric"></div>
                    </div>

                    <div class="section-box mt-3">
                        <div class="section-box-title justify-content-between">
                            <span><i class="bi bi-telephone-outbound text-primary"></i> راه‌های ارتباطی <span class="req">*</span></span>
                            <button type="button" class="btn btn-sm btn-soft-primary" data-repeater-add="#contactsList"><i class="bi bi-plus-lg"></i> افزودن</button>
                        </div>
                        <div id="contactsList" data-template="contactTpl" data-reset-html></div>
                    </div>

                    <div class="mt-3"><label class="form-label">یادداشت</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <div class="mt-3 new-only">
                        <label class="form-label">مدارک مشتری (اختیاری)</label>
                        <?php $dzName = 'files'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-grad"><i class="bi bi-check2 me-1"></i>ذخیره مشتری</button>
                </div>
            </form>
        </div>
    </div>
</div>
<template id="contactTpl">
    <div class="row g-2 mb-2 repeater-row align-items-center">
        <div class="col-4 col-md-3">
            <select name="contact_type[]" class="form-select" data-field="type">
                <?php foreach (contact_types() as $k => [$l]): ?><option value="<?= $k ?>"><?= e($l) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col"><input type="text" name="contact_value[]" class="form-control" data-field="value" placeholder="مقدار (شماره، آیدی، ایمیل...)"></div>
        <div class="col-3 d-none d-md-block"><input type="text" name="contact_label[]" class="form-control" data-field="label" placeholder="برچسب (اختیاری)"></div>
        <div class="col-auto"><button type="button" class="btn btn-soft-danger btn-icon" data-repeater-remove><i class="bi bi-x-lg"></i></button></div>
    </div>
</template>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('customerForm');
    const list = document.getElementById('contactsList');
    form.addEventListener('mm:filled', e => {
        list.innerHTML = '';
        const contacts = e.detail.contacts || [];
        if (contacts.length) contacts.forEach(c => MM.repeaterAdd(list, c));
        else { MM.repeaterAdd(list, { type: 'mobile' }); MM.repeaterAdd(list, { type: 'telegram' }); }
        form.querySelector('.new-only').classList.toggle('d-none', !!e.detail.id);
    });
});
</script>
