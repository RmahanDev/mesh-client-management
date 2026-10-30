<?php defined('APP_ROOT') or exit; ?>
<!-- تایید -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center pt-4">
                <div class="mb-3"><span class="stat-icon g-orange d-inline-grid" style="width:56px;height:56px;border-radius:16px;color:#fff;font-size:1.5rem"><i class="bi bi-exclamation-triangle"></i></span></div>
                <p class="confirm-text mb-0 fw-semibold"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-danger confirm-ok">بله</button>
            </div>
        </div>
    </div>
</div>

<!-- پیش‌نمایش پیوست -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-eye"></i><span class="preview-name text-truncate"></span></h5>
                <a href="#" class="btn btn-sm btn-soft-primary preview-download me-auto ms-2"><i class="bi bi-download me-1"></i>دانلود</a>
                <button type="button" class="btn-close m-0" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><div class="preview-body"></div></div>
        </div>
    </div>
</div>

<!-- آپلود پیوست (عمومی برای همه بخش‌ها) -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="ajax-form" method="post" action="<?= url('files/upload') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="entity">
                <input type="hidden" name="entity_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-paperclip"></i><span>افزودن پیوست</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger form-alert d-none"></div>
                    <div class="mb-3">
                        <label class="form-label">عنوان (اختیاری)</label>
                        <input type="text" name="title" class="form-control" placeholder="مثلاً: تصویر سفته، فیش واریزی، قرارداد">
                    </div>
                    <?php $dzName = 'files'; require __DIR__ . '/dropzone.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-grad"><i class="bi bi-cloud-arrow-up me-1"></i>آپلود</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- تغییر رمز -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form class="ajax-form" method="post" action="<?= url('auth/password') ?>" data-reload="false">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key"></i><span>تغییر رمز عبور</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger form-alert d-none"></div>
                    <div class="mb-3"><label class="form-label">رمز فعلی</label><input type="password" name="current" class="form-control" required autocomplete="current-password"></div>
                    <div class="mb-3"><label class="form-label">رمز جدید (حداقل ۸ کاراکتر)</label><input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
                    <div><label class="form-label">تکرار رمز جدید</label><input type="password" name="password_confirm" class="form-control" required minlength="8" autocomplete="new-password"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-grad">ذخیره</button>
                </div>
            </form>
        </div>
    </div>
</div>
