<?php defined('APP_ROOT') or exit;
use App\Core\Auth; ?>
<div class="modal fade" id="projectEditModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form class="ajax-form" method="post" action="<?= url('projects/update') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i><span>ویرایش پروژه</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger form-alert d-none"></div>
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">مشتری</label>
                            <select name="customer_id" class="form-select" required>
                                <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-7"><label class="form-label">عنوان پروژه</label><input type="text" name="title" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">توضیحات کوتاه</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                        <div class="col-md-6"><label class="form-label">دسته‌بندی‌ها</label><?php $cpId = 'e'; require __DIR__ . '/_catpicker.php'; ?></div>
                        <div class="col-12"><label class="form-label">جزئیات کامل</label><textarea name="details" class="form-control" rows="3"></textarea></div>
                        <div class="col-md-4"><label class="form-label">مبلغ کل (تومان)</label><input type="text" name="total_amount" class="form-control money" <?= Auth::canManage('finance') ? '' : 'readonly' ?>></div>
                        <div class="col-md-4"><label class="form-label">وضعیت</label>
                            <select name="status" class="form-select"><?php foreach (['pending', 'active', 'delivered', 'canceled'] as $s): ?><option value="<?= $s ?>"><?= project_status_label($s) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label">تاریخ شروع</label><input type="text" name="start_date" class="form-control jdate" autocomplete="off"></div>
                        <div class="col-md-4"><label class="form-label">تاریخ تحویل</label><input type="text" name="delivery_date" class="form-control jdate" autocomplete="off"></div>
                        <div class="col-md-4"><label class="form-label">مدت پشتیبانی (ماه)</label><input type="number" min="0" name="support_months" class="form-control"></div>
                        <div class="col-md-4 d-flex align-items-end"><div class="small text-info" data-support-end></div></div>
                    </div>
                    <div class="small text-muted mt-3"><i class="bi bi-info-circle"></i> اگر مبلغ کل را تغییر دهید، برنامه پرداخت را از تب «پرداخت‌ها» بازسازی کنید.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-grad">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>
