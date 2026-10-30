<?php defined('APP_ROOT') or exit;
/** سازنده برنامه پرداخت - $bTotalSource یا $bTotal */ ?>
<div class="schedule-builder" <?= isset($bTotal) ? 'data-total="' . (int)$bTotal . '"' : 'data-total-source="' . e($bTotalSource ?? '[name=total_amount]') . '"' ?>>
    <div class="choice-cards mb-3">
        <div class="choice-card"><input type="radio" name="payment_type" value="single" id="pt1<?= $bId ?>" checked><label for="pt1<?= $bId ?>"><i class="bi bi-cash-stack"></i><span class="t">یک‌جا</span><span class="d">کل مبلغ در یک پرداخت</span></label></div>
        <div class="choice-card"><input type="radio" name="payment_type" value="stages" id="pt2<?= $bId ?>"><label for="pt2<?= $bId ?>"><i class="bi bi-bar-chart-steps"></i><span class="t">چندمرحله‌ای</span><span class="d">۲ تا بی‌نهایت مرحله، تاریخ اختیاری</span></label></div>
        <div class="choice-card"><input type="radio" name="payment_type" value="installment" id="pt3<?= $bId ?>"><label for="pt3<?= $bId ?>"><i class="bi bi-calendar2-range"></i><span class="t">اقساطی</span><span class="d">پیش‌پرداخت + محاسبه خودکار اقساط</span></label></div>
    </div>

    <div data-pane="stages" class="row g-2 mb-2 align-items-end d-none">
        <div class="col-6 col-md-3"><label class="form-label">تعداد مراحل</label><input type="number" min="2" value="2" class="form-control" data-stage-count></div>
        <div class="col-6 col-md-3"><label class="form-label">تاریخ مرحله اول (اختیاری)</label><input type="text" class="form-control jdate" data-stage-first autocomplete="off"></div>
        <div class="col-6 col-md-2"><label class="form-label">فاصله مراحل (ماه)</label><input type="number" min="0" value="0" class="form-control" data-stage-gap placeholder="۰ = بدون تاریخ" title="۰ = فقط مرحله اول تاریخ دارد"></div>
        <div class="col-6 col-md-2"><label class="form-label">گرد کردن</label><select class="form-select" data-round><option value="1000">هزار</option><option value="10000">ده‌هزار</option><option value="100000" selected>صدهزار</option><option value="1">بدون گرد</option></select></div>
        <div class="col-md-2"><button type="button" class="btn btn-soft-primary w-100" data-build-stages><i class="bi bi-magic"></i> تقسیم</button></div>
    </div>

    <div data-pane="installment" class="d-none">
        <div class="row g-2 mb-2 align-items-end">
            <div class="col-6 col-md-3"><label class="form-label">مبلغ پیش‌پرداخت</label><input type="text" class="form-control money" data-prepay placeholder="۰" inputmode="numeric"></div>
            <div class="col-6 col-md-3"><label class="form-label">تاریخ پیش‌پرداخت</label><input type="text" class="form-control jdate" data-prepay-date autocomplete="off"></div>
            <div class="col-4 col-md-2"><label class="form-label">تعداد اقساط</label><input type="number" min="1" value="6" class="form-control" data-months></div>
            <div class="col-4 col-md-2"><label class="form-label">هر چند ماه</label><input type="number" min="1" value="1" class="form-control" data-interval></div>
            <div class="col-4 col-md-2"><label class="form-label">گرد کردن</label><select class="form-select" data-round><option value="1000">هزار</option><option value="10000">ده‌هزار</option><option value="100000" selected>صدهزار</option><option value="1">بدون گرد</option></select></div>
            <div class="col-12 col-md-4"><label class="form-label">تاریخ اولین قسط <span class="req">*</span></label><input type="text" class="form-control jdate" data-first-date autocomplete="off"></div>
            <div class="col-12 col-md-4"><button type="button" class="btn btn-grad w-100 btn-calc" data-build-inst><i class="bi bi-calculator me-1"></i>محاسبه اقساط</button></div>
        </div>
    </div>

    <div class="schedule-head d-none d-md-flex"><span class="sh-n"></span><span class="sh-t">عنوان</span><span class="sh-a">مبلغ (تومان)</span><span class="sh-d">سررسید</span><span class="sh-x"></span></div>
    <div class="schedule-rows"></div>
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-2">
        <div class="sum-bar flex-grow-1">
            <span>مبلغ: <b data-sum-total>۰</b></span>
            <span>جمع ردیف‌ها: <b data-sum-rows>۰</b></span>
            <span>اختلاف: <span data-sum-diff>—</span></span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-soft" data-fill-diff title="اختلاف مبلغ به ردیف آخر اضافه یا کم می‌شود"><i class="bi bi-arrow-down-up"></i> تطبیق با مبلغ کل</button>
            <button type="button" class="btn btn-sm btn-soft-primary" data-add-row><i class="bi bi-plus-lg"></i> ردیف</button>
        </div>
    </div>
</div>
<?php if (empty($GLOBALS['_schedTplPrinted'])): $GLOBALS['_schedTplPrinted'] = true; ?>
<template id="schedRowTpl">
    <div class="schedule-row row g-2 align-items-center mx-0">
        <div class="col-auto seq-col text-center"><span class="badge bs-primary seq">۱</span></div>
        <div class="t-col"><input type="text" name="sched_title[]" class="form-control form-control-sm" data-f="title" placeholder="عنوان"></div>
        <div class="a-col"><input type="text" name="sched_amount[]" class="form-control form-control-sm money" data-f="amount" placeholder="مبلغ (تومان)" inputmode="numeric"></div>
        <div class="d-col"><input type="text" name="sched_due[]" class="form-control form-control-sm jdate" data-f="due" placeholder="سررسید" autocomplete="off"></div>
        <input type="hidden" name="sched_kind[]" data-f="kind">
        <div class="x-col"><button type="button" class="btn btn-sm btn-soft-danger btn-icon" data-rm-row><i class="bi bi-x"></i></button></div>
    </div>
</template>
<?php endif; ?>
