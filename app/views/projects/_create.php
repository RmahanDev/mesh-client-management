<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canFinance = Auth::canManage('finance');
$defSupport = (int)\App\Core\Settings::get('default_support_months', 3);
$shareAdmins = \App\Core\DB::all('SELECT id, name FROM admins WHERE is_active = 1 ORDER BY name'); ?>
<div class="modal fade" id="projectCreateModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form class="ajax-form" method="post" action="<?= url('projects/store') ?>" enctype="multipart/form-data" id="projectCreateForm">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-kanban"></i><span>ثبت پروژه جدید</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger form-alert d-none"></div>

                    <div class="section-box mb-3">
                        <div class="section-box-title"><i class="bi bi-info-circle text-primary"></i> اطلاعات پروژه</div>
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label">مشتری <span class="req">*</span></label>
                                <select name="customer_id" class="form-select" required>
                                    <option value="">انتخاب مشتری...</option>
                                    <?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name'] . ($c['company'] ? ' (' . $c['company'] . ')' : '')) ?></option><?php endforeach; ?>
                                </select>
                                <?php if (Auth::canManage('customers')): ?><a href="<?= url('customers') ?>" class="small">مشتری جدید؟ ابتدا از بخش مشتریان ثبت کنید</a><?php endif; ?>
                            </div>
                            <div class="col-md-7"><label class="form-label">عنوان پروژه <span class="req">*</span></label><input type="text" name="title" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">توضیحات کوتاه</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                            <div class="col-md-6"><label class="form-label">دسته‌بندی‌ها (چند انتخابی)</label><?php $cpId = 'c'; require __DIR__ . '/_catpicker.php'; ?></div>
                            <div class="col-12"><label class="form-label">جزئیات کامل (امکانات، تکنولوژی، توافقات...)</label><textarea name="details" class="form-control" rows="3"></textarea></div>
                            <div class="col-6 col-md-3"><label class="form-label">وضعیت</label>
                                <select name="status" class="form-select"><option value="pending">در انتظار شروع</option><option value="active" selected>در حال انجام</option><option value="delivered">تحویل شده</option></select></div>
                            <div class="col-6 col-md-3"><label class="form-label">تاریخ شروع</label><input type="text" name="start_date" class="form-control jdate" autocomplete="off"></div>
                            <div class="col-6 col-md-3"><label class="form-label">تاریخ تحویل (اختیاری)</label><input type="text" name="delivery_date" class="form-control jdate" autocomplete="off"></div>
                            <div class="col-6 col-md-3"><label class="form-label">مدت پشتیبانی (ماه)</label><input type="number" min="0" name="support_months" value="<?= $defSupport ?>" class="form-control"></div>
                            <div class="col-12"><div class="small text-info" data-support-end></div></div>
                        </div>
                    </div>

                    <div class="section-box mb-3">
                        <div class="section-box-title"><i class="bi bi-cash-coin text-success"></i> مبلغ و نحوه پرداخت</div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4"><label class="form-label">مبلغ کل پروژه (تومان) <span class="req">*</span></label><input type="text" name="total_amount" class="form-control money" required inputmode="numeric" placeholder="مثلاً ۲۵,۰۰۰,۰۰۰"></div>
                        </div>
                        <?php if ($canFinance): $bId = 'c'; $bTotalSource = '[name=total_amount]'; require __DIR__ . '/_builder.php'; else: ?>
                            <input type="hidden" name="payment_type" value="single">
                            <div class="small text-muted">برنامه پرداخت را ادمین مالی ثبت می‌کند.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($canFinance): ?>
                    <div class="section-box mb-3 d-none" data-show-for="installment">
                        <div class="section-box-title"><i class="bi bi-shield-check text-info"></i> تضمین قرارداد اقساطی</div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="has_promissory" value="1" id="hasProm" data-toggle-box="#promBox">
                            <label class="form-check-label" for="hasProm">سفته گرفته‌ایم</label>
                        </div>
                        <div id="promBox" class="row g-2 mb-3 d-none">
                            <div class="col-md-4"><label class="form-label">مبلغ کل سفته‌ها (تومان)</label><input type="text" name="promissory_amount" class="form-control money" inputmode="numeric"></div>
                            <div class="col-md-2"><label class="form-label">چند فقره؟</label><input type="number" min="1" name="promissory_count" value="1" class="form-control"></div>
                            <div class="col-md-6"><label class="form-label">سریال‌ها (اختیاری)</label><input type="text" name="promissory_serials" class="form-control"></div>
                            <div class="col-12"><?php $dzName = 'promissory_files'; $dzLabel = 'تصویر سفته‌ها را آپلود کنید'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="has_guarantor" value="1" id="hasGuar" data-toggle-box="#guarBox">
                            <label class="form-check-label" for="hasGuar">این قرارداد ضامن دارد</label>
                        </div>
                        <div id="guarBox" class="row g-2 d-none">
                            <div class="col-md-4"><label class="form-label">نام و نام خانوادگی ضامن <span class="req">*</span></label><input type="text" name="guarantor_name" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label">شماره تماس</label><input type="text" name="guarantor_mobile" class="form-control ltr digits" inputmode="tel"></div>
                            <div class="col-md-3"><label class="form-label">کد ملی</label><input type="text" name="guarantor_national_code" class="form-control ltr digits" maxlength="10" inputmode="numeric"></div>
                            <div class="col-md-2"><label class="form-label">تاریخ تولد</label><input type="text" name="guarantor_birth_date" class="form-control jdate" autocomplete="off"></div>
                            <div class="col-12"><label class="form-label">آدرس</label><input type="text" name="guarantor_address" class="form-control"></div>
                            <div class="col-12"><?php $dzName = 'guarantor_files'; $dzLabel = 'مدارک ضامن (کارت ملی، تعهدنامه...)'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
                        </div>
                    </div>

                    <div class="section-box mb-3">
                        <div class="section-box-title justify-content-between">
                            <span><i class="bi bi-tools text-warning"></i> هزینه‌ها و ابزارهای خریداری‌شده (برای محاسبه سود خالص)</span>
                            <button type="button" class="btn btn-sm btn-soft-primary" data-repeater-add="#expList"><i class="bi bi-plus-lg"></i> هزینه</button>
                        </div>
                        <div id="expList" data-template="expTpl" data-reset-html></div>
                        <div class="small text-muted">مثل هاست، دامنه، قالب، افزونه، لایسنس، دستمزد همکار و ... (بعداً هم قابل اضافه است)</div>
                    </div>

                    <div class="section-box mb-3">
                        <div class="section-box-title justify-content-between">
                            <span><i class="bi bi-pie-chart text-success"></i> سهم‌بندی سود پروژه</span>
                            <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="profit_sharing" value="1" id="newShEnable" data-toggle-box="#newShBox"><label class="form-check-label small" for="newShEnable">فعال</label></div>
                        </div>
                        <div id="newShBox" class="d-none">
                            <div class="small text-muted mb-2">سهم هر نفر از «سود = دریافتی − هزینه ابزارها» حساب می‌شود. فقط ادمین‌ها را می‌توان انتخاب کرد.</div>
                            <div id="newShList" data-template="newShTpl" data-reset-html></div>
                            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-2">
                                <button type="button" class="btn btn-sm btn-soft-primary" data-repeater-add="#newShList"><i class="bi bi-plus-lg"></i> افزودن نفر</button>
                                <div class="small" data-new-sh-sum></div>
                            </div>
                            <div class="small text-muted mt-1">مبلغ‌های کنار هر درصد برآورد است: با فرض دریافت کامل مبلغ پروژه و کسر هزینه‌های بالا.</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="section-box">
                        <div class="section-box-title"><i class="bi bi-paperclip text-primary"></i> پیوست‌های پروژه (قرارداد، فایل‌ها...)</div>
                        <?php $dzName = 'files'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" class="btn btn-grad px-4"><i class="bi bi-check2-circle me-1"></i>ثبت پروژه</button>
                </div>
            </form>
        </div>
    </div>
</div>
<template id="newShTpl">
    <div class="row g-2 mb-2 repeater-row align-items-center">
        <div class="col-6 col-md-5"><select name="share_admin[]" class="form-select form-select-sm"><option value="">انتخاب ادمین...</option><?php foreach ($shareAdmins as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-4 col-md-2"><input type="text" name="share_percent[]" class="form-control form-control-sm ltr" inputmode="decimal" placeholder="درصد" data-new-sh-pct></div>
        <div class="col-8 col-md-4 small fw-semibold text-success" data-new-sh-amt></div>
        <div class="col-4 col-md-1 text-start"><button type="button" class="btn btn-sm btn-soft-danger btn-icon" data-repeater-remove><i class="bi bi-x"></i></button></div>
    </div>
</template>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('projectCreateForm');
    if (!form || !form.querySelector('#newShList')) return;
    function profit() {
        const total = MM.parseMoney(form.querySelector('[name=total_amount]').value);
        let exp = 0; form.querySelectorAll('[name="exp_amount[]"]').forEach(i => exp += MM.parseMoney(i.value));
        return Math.max(0, total - exp);
    }
    function calc() {
        const base = profit(); let t = 0;
        form.querySelectorAll('#newShList .repeater-row').forEach(r => {
            const v = parseFloat(MM.en(r.querySelector('[data-new-sh-pct]').value).replace('٫', '.')) || 0;
            t += v;
            r.querySelector('[data-new-sh-amt]').textContent = v ? '≈ ' + MM.money(Math.floor(base * v / 100)) : '';
        });
        const s = form.querySelector('[data-new-sh-sum]');
        s.textContent = t ? 'جمع درصدها: ' + MM.fa(Math.round(t * 100) / 100) + '٪' : '';
        s.className = 'small ' + (t > 100.001 ? 'text-danger' : 'text-muted');
    }
    form.addEventListener('input', calc);
    form.addEventListener('click', () => setTimeout(calc, 0));
    form.addEventListener('mm:filled', () => { form.querySelector('#newShBox').classList.toggle('d-none', !form.querySelector('#newShEnable').checked); calc(); });
});
</script>
<template id="expTpl">
    <div class="row g-2 mb-2 repeater-row">
        <div class="col-md-6"><input type="text" name="exp_title[]" class="form-control form-control-sm" placeholder="عنوان هزینه / ابزار"></div>
        <div class="col-5 col-md-3"><input type="text" name="exp_amount[]" class="form-control form-control-sm money" placeholder="مبلغ" inputmode="numeric"></div>
        <div class="col-5 col-md-2"><input type="text" name="exp_date[]" class="form-control form-control-sm jdate" placeholder="تاریخ" autocomplete="off"></div>
        <div class="col-2 col-md-1"><button type="button" class="btn btn-sm btn-soft-danger btn-icon" data-repeater-remove><i class="bi bi-x"></i></button></div>
    </div>
</template>
