<?php defined('APP_ROOT') or exit;
use App\Core\Auth;

$canManage = Auth::canManage('projects');
$canFin = Auth::can('finance');
$canFinM = Auth::canManage('finance');
$canSup = Auth::can('support');
$canSupM = Auth::canManage('support');
$f = $finance;
$remaining = max(0, $p['total_amount'] - $f['paid']);
$pct = $p['total_amount'] ? min(100, round($f['paid'] / $p['total_amount'] * 100)) : 0;
$sd = days_until($p['support_end']);
$editData = $p + ['categories' => array_column($cats, 'id'), 'start_date' => jdate_input($p['start_date']), 'delivery_date' => jdate_input($p['delivery_date'])];
$scheduledSum = $canFin ? array_sum(array_column($payments, 'amount')) : 0;
$kindLabel = ['single' => 'یک‌جا', 'stage' => 'مرحله', 'prepayment' => 'پیش‌پرداخت', 'installment' => 'قسط'];
?>
<div class="hero-card mb-3">
    <div class="d-flex flex-wrap gap-3 align-items-start position-relative">
        <div class="flex-grow-1">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge bg-white text-dark"><?= project_status_label($p['status']) ?></span>
                <span class="badge" style="background:rgba(255,255,255,.18)"><i class="bi bi-credit-card me-1"></i><?= payment_type_label($p['payment_type']) ?></span>
                <?php foreach ($cats as $c): ?><span class="badge" style="background:rgba(255,255,255,.14)"><i class="bi <?= e($c['icon']) ?> me-1"></i><?= e(($c['parent_name'] ? $c['parent_name'] . ' › ' : '') . $c['name']) ?></span><?php endforeach; ?>
            </div>
            <h2 class="h4 fw-black mb-1"><?= e($p['title']) ?></h2>
            <div class="opacity-75"><i class="bi bi-person me-1"></i><a class="text-white" href="<?= url('customers/show', ['id' => $p['customer_id']]) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a><?= $p['company'] ? ' · ' . e($p['company']) : '' ?></div>
        </div>
        <?php if ($canManage): ?>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#projectEditModal" data-fill='<?= json_attr($editData) ?>'><i class="bi bi-pencil me-1"></i>ویرایش</button>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#deliverModal" data-fill='<?= json_attr(['delivery_date' => jdate_input($p['delivery_date'] ?: today()), 'support_months' => $p['support_months']]) ?>'><i class="bi bi-box-seam me-1"></i><?= $p['delivery_date'] ? 'ویرایش تحویل' : 'ثبت تحویل' ?></button>
            <button class="btn btn-sm" style="background:rgba(0,0,0,.2);color:#fff" data-confirm-url="<?= url('projects/delete') ?>" data-post='<?= json_attr(['id' => $p['id']]) ?>' data-confirm="پروژه «<?= e($p['title']) ?>» با همه پرداخت‌ها، هزینه‌ها و پیوست‌ها حذف شود؟"><i class="bi bi-trash"></i></button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canFin): ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-xl"><div class="stat-card text-primary"><span class="stat-icon g-blue"><i class="bi bi-file-earmark-text"></i></span><div class="stat-value text-body"><?= money($p['total_amount'], false) ?></div><div class="stat-label">مبلغ کل قرارداد</div></div></div>
    <div class="col-6 col-xl"><div class="stat-card text-success"><span class="stat-icon g-green"><i class="bi bi-wallet2"></i></span><div class="stat-value text-body"><?= money($f['paid'], false) ?></div><div class="stat-label">دریافت شده (<?= fa_digits($pct) ?>٪)</div></div></div>
    <div class="col-6 col-xl"><div class="stat-card text-warning"><span class="stat-icon g-orange"><i class="bi bi-hourglass-split"></i></span><div class="stat-value text-body"><?= money($remaining, false) ?></div><div class="stat-label">مانده<?= $f['overdue'] ? ' · معوق: ' . money_plain($f['overdue']) : '' ?></div></div></div>
    <div class="col-6 col-xl"><div class="stat-card text-info"><span class="stat-icon g-cyan"><i class="bi bi-tools"></i></span><div class="stat-value text-body"><?= money($f['expenses'], false) ?></div><div class="stat-label">هزینه‌ها و ابزار</div></div></div>
    <div class="col-12 col-xl"><div class="stat-card text-success"><span class="stat-icon g-mix"><i class="bi bi-graph-up-arrow"></i></span><div class="stat-value <?= $f['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($f['profit'], false) ?></div><div class="stat-label">سود خالص تا امروز<?= $f['renewals_paid'] ? ' (با تمدید پشتیبانی)' : '' ?></div></div></div>
</div>
<?php endif; ?>

<ul class="nav nav-pills nav-pills-mm mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-overview"><i class="bi bi-info-circle me-1"></i>جزئیات</button></li>
    <?php if ($canFin): ?>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-payments"><i class="bi bi-calendar2-check me-1"></i>پرداخت‌ها <span class="badge"><?= fa_digits(count($payments)) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-expenses"><i class="bi bi-tools me-1"></i>هزینه‌ها <span class="badge"><?= fa_digits(count($expenses)) ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-guarantee"><i class="bi bi-shield-check me-1"></i>سفته و ضامن</button></li>
    <?php endif; ?>
    <?php if ($canSup): ?>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-support"><i class="bi bi-headset me-1"></i>پشتیبانی <span class="badge"><?= fa_digits(count($tickets)) ?></span></button></li>
    <?php endif; ?>
    <?php if (Auth::can('finance')): ?><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-shares"><i class="bi bi-pie-chart me-1"></i>سهم‌بندی<?php if ($p['profit_sharing']): ?> <span class="badge"><?= fa_digits(count($sh['rows'])) ?></span><?php endif; ?></button></li><?php endif; ?>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-files"><i class="bi bi-paperclip me-1"></i>پیوست‌ها</button></li>
</ul>

<div class="tab-content">
    <!-- جزئیات -->
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-3">
            <?php $hasText = !empty($p['description']) || !empty($p['details']); ?>
            <?php if ($hasText): ?>
            <div class="col-lg-8">
                <div class="card h-100"><div class="card-body">
                    <?php if (!empty($p['description'])): ?>
                    <h6 class="fw-bold text-primary">توضیحات</h6>
                    <p class="<?= !empty($p['details']) ? 'mb-4' : 'mb-0' ?>"><?= nl2br(e($p['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($p['details'])): ?>
                    <h6 class="fw-bold text-primary">جزئیات کامل</h6>
                    <p class="mb-0"><?= nl2br(e($p['details'])) ?></p>
                    <?php endif; ?>
                </div></div>
            </div>
            <?php endif; ?>
            <div class="<?= $hasText ? 'col-lg-4' : 'col-12' ?>">
                <div class="card mb-3"><div class="card-body">
                    <div class="kv"><span class="k">تاریخ شروع</span><span class="v"><?= jdate($p['start_date']) ?></span></div>
                    <div class="kv"><span class="k">تاریخ تحویل</span><span class="v"><?= jdate($p['delivery_date']) ?></span></div>
                    <div class="kv"><span class="k">مدت پشتیبانی اولیه</span><span class="v"><?= fa_digits($p['support_months']) ?> ماه</span></div>
                    <div class="kv"><span class="k">پایان پشتیبانی</span><span class="v"><?= jdate($p['support_end']) ?></span></div>
                    <div class="kv"><span class="k">ثبت شده در</span><span class="v"><?= jdate($p['created_at'], true) ?></span></div>
                </div></div>
                <?php if ($logs): ?>
                <div class="card"><div class="card-header small">تاریخچه</div><div class="card-body small" style="max-height:260px;overflow:auto">
                    <?php foreach ($logs as $l): ?><div class="timeline-item"><div class="fw-semibold"><?= e($l['details'] ?: $l['action']) ?></div><div class="text-muted"><?= e($l['name'] ?? '—') ?> · <?= jdate($l['created_at'], true) ?></div></div><?php endforeach; ?>
                </div></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($canFin): ?>
    <!-- پرداخت‌ها -->
    <div class="tab-pane fade" id="tab-payments">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <span>برنامه پرداخت (<?= payment_type_label($p['payment_type']) ?>)</span>
                <?php if ($canFinM): ?>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#paymentModal" data-title="افزودن ردیف پرداخت" data-fill='<?= json_attr(['kind' => $p['payment_type'] === 'installment' ? 'installment' : 'stage']) ?>'><i class="bi bi-plus-lg"></i> ردیف</button>
                    <button class="btn btn-sm btn-soft-success" data-bs-toggle="modal" data-bs-target="#scheduleModal" data-fill='<?= json_attr(['payment_type' => $p['payment_type']]) ?>'><i class="bi bi-magic"></i> بازسازی برنامه</button>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($scheduledSum != $p['total_amount']): ?>
                <div class="alert alert-warning m-3 mb-0 py-2 small"><i class="bi bi-exclamation-triangle me-1"></i>جمع برنامه پرداخت (<?= money_plain($scheduledSum) ?>) با مبلغ کل قرارداد (<?= money_plain($p['total_amount']) ?>) برابر نیست.</div>
            <?php endif; ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>عنوان</th><th>مبلغ</th><th>سررسید</th><th>دریافتی</th><th>وضعیت</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($payments as $pay):
                        $left = days_until($pay['due_date']);
                        $isOver = $pay['status'] !== 'paid' && $left !== null && $left < 0;
                        $rc = $receipts[$pay['id']] ?? [];
                        $atts = $attachMap['payment'][$pay['id']] ?? [];
                        $rest = $pay['amount'] - $pay['paid_amount']; ?>
                        <tr class="<?= $isOver ? 'table-row-overdue' : ($pay['status'] === 'paid' ? 'table-row-paid' : '') ?>">
                            <td><span class="badge badge-soft"><?= fa_digits($pay['seq']) ?></span></td>
                            <td><div class="fw-semibold"><?= e($pay['title']) ?></div><div class="small text-muted"><?= $kindLabel[$pay['kind']] ?? '' ?><?= $pay['notes'] ? ' · ' . e($pay['notes']) : '' ?></div></td>
                            <td class="text-nowrap"><?= money($pay['amount']) ?></td>
                            <td class="text-nowrap"><?= jdate($pay['due_date']) ?>
                                <?php if ($pay['status'] !== 'paid' && $left !== null): ?><div class="small <?= $left < 0 ? 'text-danger' : ($left <= 7 ? 'text-warning' : 'text-muted') ?>"><?= $left < 0 ? fa_digits(-$left) . ' روز گذشته' : ($left === 0 ? 'امروز' : fa_digits($left) . ' روز مانده') ?></div><?php endif; ?></td>
                            <td class="text-nowrap"><?= money($pay['paid_amount'], false) ?><?php if ($pay['paid_date']): ?><div class="small text-muted"><?= jdate($pay['paid_date']) ?></div><?php endif; ?></td>
                            <td><?= ['pending' => '<span class="badge bs-' . ($isOver ? 'danger">معوق' : 'secondary">در انتظار') . '</span>', 'partial' => '<span class="badge bs-warning">ناقص</span>', 'paid' => '<span class="badge bs-success">پرداخت شد</span>'][$pay['status']] ?></td>
                            <td class="text-nowrap text-start">
                                <?php if ($canFinM && $pay['status'] !== 'paid'): ?>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal" data-fill='<?= json_attr(['payment_id' => $pay['id'], 'amount' => $rest, '_info' => $pay['title'] . ' — مانده: ' . money_plain($rest)]) ?>'><i class="bi bi-check2"></i> دریافت</button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-soft btn-icon" data-bs-toggle="collapse" data-bs-target="#pd<?= $pay['id'] ?>" title="رسیدها و پیوست‌ها"><i class="bi bi-paperclip"></i><?php if ($atts || $rc): ?><small><?= fa_digits(count($atts) + count($rc)) ?></small><?php endif; ?></button>
                                <?php if ($canFinM): ?>
                                    <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#paymentModal" data-title="ویرایش ردیف" data-fill='<?= json_attr(['id' => $pay['id'], 'title' => $pay['title'], 'kind' => $pay['kind'], 'amount' => $pay['amount'], 'due_date' => jdate_input($pay['due_date']), 'notes' => $pay['notes']]) ?>'><i class="bi bi-pencil"></i></button>
                                    <?php if (!$rc): ?><button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('finance/payment-delete') ?>" data-post='<?= json_attr(['id' => $pay['id']]) ?>' data-confirm="ردیف «<?= e($pay['title']) ?>» حذف شود؟"><i class="bi bi-trash"></i></button><?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr class="collapse" id="pd<?= $pay['id'] ?>"><td colspan="7" class="bg-body-tertiary">
                            <div class="row g-3">
                                <div class="col-lg-5">
                                    <div class="small text-muted mb-2"><i class="bi bi-receipt"></i> دریافتی‌ها</div>
                                    <?php foreach ($rc as $r): ?>
                                        <div class="d-flex justify-content-between align-items-center reply-box py-2 mb-2 small">
                                            <span><b><?= money($r['amount']) ?></b> · <?= jdate($r['paid_date']) ?> · <?= payment_method_label($r['method']) ?><?= $r['ref_no'] ? ' · <span class="ltr">' . e($r['ref_no']) . '</span>' : '' ?><?= $r['notes'] ? '<br><span class="text-muted">' . e($r['notes']) . '</span>' : '' ?></span>
                                            <?php if ($canFinM): ?><button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('finance/receipt-delete') ?>" data-post='<?= json_attr(['id' => $r['id']]) ?>' data-confirm="این دریافتی حذف شود؟"><i class="bi bi-x"></i></button><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (!$rc): ?><div class="small text-muted">دریافتی ثبت نشده</div><?php endif; ?>
                                </div>
                                <div class="col-lg-7"><?php $attEntity = 'payment'; $attId = (int)$pay['id']; $attManage = $canFinM; $attList = $atts; $attTitle = 'پیوست برای ' . $pay['title']; require APP_ROOT . '/app/views/partials/attachments.php'; ?></div>
                            </div>
                        </td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$payments): ?><div class="empty-state"><i class="bi bi-calendar-x"></i>برنامه پرداختی ثبت نشده<?php if ($canFinM): ?><br><button class="btn btn-grad btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#scheduleModal" data-fill='<?= json_attr(['payment_type' => $p['payment_type']]) ?>'>ساخت برنامه پرداخت</button><?php endif; ?></div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- هزینه‌ها -->
    <div class="tab-pane fade" id="tab-expenses">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>هزینه‌ها و ابزارهای این پروژه <span class="text-muted small">— جمع: <?= money_plain($f['expenses']) ?></span></span>
                <?php if ($canFinM): ?><button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#expenseModal" data-title="هزینه جدید" data-fill="{}"><i class="bi bi-plus-lg"></i> هزینه</button><?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>عنوان</th><th>مبلغ</th><th>تاریخ</th><th>توضیحات</th><th>پیوست</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($expenses as $x): $atts = $attachMap['expense'][$x['id']] ?? []; ?>
                        <tr>
                            <td class="fw-semibold"><?= e($x['title']) ?></td>
                            <td class="text-nowrap"><?= money($x['amount']) ?></td>
                            <td><?= jdate($x['expense_date']) ?></td>
                            <td class="small text-muted"><?= e($x['notes']) ?></td>
                            <td><?php foreach ($atts as $a): ?><a href="#" class="js-preview badge badge-soft ms-1" data-src="<?= url('files/view', ['id' => $a['id']]) ?>" data-download="<?= url('files/view', ['id' => $a['id'], 'download' => 1]) ?>" data-mime="<?= e($a['mime']) ?>" data-name="<?= e($a['original_name']) ?>"><i class="bi bi-paperclip"></i></a><?php endforeach; ?>
                                <?php if ($canFinM): ?><button class="btn btn-sm btn-soft btn-icon" data-bs-toggle="modal" data-bs-target="#uploadModal" data-fill='<?= json_attr(['entity' => 'expense', 'entity_id' => $x['id']]) ?>'><i class="bi bi-plus"></i></button><?php endif; ?></td>
                            <td class="text-nowrap text-start"><?php if ($canFinM): ?>
                                <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#expenseModal" data-title="ویرایش هزینه" data-fill='<?= json_attr(['id' => $x['id'], 'title' => $x['title'], 'amount' => $x['amount'], 'expense_date' => jdate_input($x['expense_date']), 'notes' => $x['notes']]) ?>'><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('finance/expense-delete') ?>" data-post='<?= json_attr(['id' => $x['id']]) ?>' data-confirm="هزینه «<?= e($x['title']) ?>» حذف شود؟"><i class="bi bi-trash"></i></button>
                            <?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$expenses): ?><div class="empty-state"><i class="bi bi-tools"></i>هزینه‌ای ثبت نشده — سود خالص برابر کل دریافتی است</div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- سفته و ضامن -->
    <div class="tab-pane fade" id="tab-guarantee">
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-file-earmark-ruled me-1 text-info"></i>سفته‌ها</span>
                        <?php if ($canFinM): ?><button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#promissoryModal" data-title="ثبت سفته" data-fill="{}"><i class="bi bi-plus-lg"></i></button><?php endif; ?></div>
                    <div class="card-body">
                        <?php foreach ($promissory as $pn): ?>
                            <div class="reply-box mb-3">
                                <div class="d-flex justify-content-between">
                                    <div><b><?= money($pn['amount']) ?></b> در <?= fa_digits($pn['count']) ?> فقره
                                        <span class="badge bs-<?= $pn['status'] === 'held' ? 'success' : 'secondary' ?> me-1"><?= $pn['status'] === 'held' ? 'نزد ما' : 'عودت شده' ?></span>
                                        <?php if ($pn['serials']): ?><div class="small text-muted">سریال: <?= e($pn['serials']) ?></div><?php endif; ?>
                                        <?php if ($pn['notes']): ?><div class="small text-muted"><?= e($pn['notes']) ?></div><?php endif; ?>
                                    </div>
                                    <?php if ($canFinM): ?><div class="text-nowrap">
                                        <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#promissoryModal" data-title="ویرایش سفته" data-fill='<?= json_attr($pn) ?>'><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('finance/promissory-delete') ?>" data-post='<?= json_attr(['id' => $pn['id']]) ?>' data-confirm="این سفته حذف شود؟"><i class="bi bi-trash"></i></button></div><?php endif; ?>
                                </div>
                                <?php $attEntity = 'promissory'; $attId = (int)$pn['id']; $attManage = $canFinM; $attList = $attachMap['promissory'][$pn['id']] ?? []; $attTitle = 'تصویر سفته'; require APP_ROOT . '/app/views/partials/attachments.php'; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$promissory): ?><div class="empty-state py-3"><i class="bi bi-file-earmark-ruled"></i>سفته‌ای ثبت نشده</div><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-person-badge me-1 text-success"></i>ضامن‌ها</span>
                        <?php if ($canFinM): ?><button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#guarantorModal" data-title="ثبت ضامن" data-fill="{}"><i class="bi bi-plus-lg"></i></button><?php endif; ?></div>
                    <div class="card-body">
                        <?php foreach ($guarantors as $g): ?>
                            <div class="reply-box mb-3">
                                <div class="d-flex justify-content-between">
                                    <div class="fw-bold"><i class="bi bi-person me-1"></i><?= e($g['full_name']) ?></div>
                                    <?php if ($canFinM): ?><div class="text-nowrap">
                                        <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#guarantorModal" data-title="ویرایش ضامن" data-fill='<?= json_attr($g + ['birth_date' => jdate_input($g['birth_date'])]) ?>'><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('finance/guarantor-delete') ?>" data-post='<?= json_attr(['id' => $g['id']]) ?>' data-confirm="ضامن «<?= e($g['full_name']) ?>» حذف شود؟"><i class="bi bi-trash"></i></button></div><?php endif; ?>
                                </div>
                                <div class="kv small"><span class="k">شماره تماس</span><span class="v ltr"><?= $g['mobile'] ? '<a href="tel:' . e($g['mobile']) . '">' . fa_digits(e($g['mobile'])) . '</a>' : '—' ?></span></div>
                                <div class="kv small"><span class="k">کد ملی</span><span class="v"><?= fa_digits(e($g['national_code'] ?: '—')) ?></span></div>
                                <div class="kv small"><span class="k">تاریخ تولد</span><span class="v"><?= jdate($g['birth_date']) ?></span></div>
                                <?php if ($g['address']): ?><div class="kv small"><span class="k">آدرس</span><span class="v"><?= e($g['address']) ?></span></div><?php endif; ?>
                                <?php $attEntity = 'guarantor'; $attId = (int)$g['id']; $attManage = $canFinM; $attList = $attachMap['guarantor'][$g['id']] ?? []; $attTitle = 'مدارک ضامن'; require APP_ROOT . '/app/views/partials/attachments.php'; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$guarantors): ?><div class="empty-state py-3"><i class="bi bi-person-badge"></i>ضامنی ثبت نشده</div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($canSup): ?>
    <!-- پشتیبانی -->
    <div class="tab-pane fade" id="tab-support">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card mb-3"><div class="card-body text-center">
                    <?php if ($p['support_end']): ?>
                        <div class="small text-muted mb-2" data-cd-label>تا پایان پشتیبانی</div>
                        <div class="countdown justify-content-center" data-countdown="<?= e($p['support_end']) ?> 23:59:59">
                            <div class="cd-box"><div class="cd-num" data-cd="d">۰</div><div class="cd-lbl">روز</div></div>
                            <div class="cd-box"><div class="cd-num" data-cd="h">۰</div><div class="cd-lbl">ساعت</div></div>
                            <div class="cd-box"><div class="cd-num" data-cd="m">۰</div><div class="cd-lbl">دقیقه</div></div>
                            <div class="cd-box"><div class="cd-num" data-cd="s">۰</div><div class="cd-lbl">ثانیه</div></div>
                        </div>
                        <div class="small text-muted mt-3">از <?= jdate($p['support_start']) ?> تا <b><?= jdate($p['support_end']) ?></b></div>
                    <?php else: ?>
                        <div class="empty-state py-3"><i class="bi bi-hourglass"></i>هنوز تاریخ تحویل ثبت نشده.<br>با ثبت تحویل، روزشمار پشتیبانی شروع می‌شود.</div>
                        <?php if ($canManage): ?><button class="btn btn-grad btn-sm" data-bs-toggle="modal" data-bs-target="#deliverModal" data-fill='<?= json_attr(['delivery_date' => jdate_input(today()), 'support_months' => $p['support_months']]) ?>'><i class="bi bi-box-seam me-1"></i>ثبت تحویل و شروع پشتیبانی</button><?php endif; ?>
                    <?php endif; ?>
                    <?php if ($canSupM && $p['support_end']): ?>
                        <div class="mt-3"><button class="btn btn-soft-success btn-sm" data-bs-toggle="modal" data-bs-target="#renewalModal" data-fill='<?= json_attr(['start_date' => jdate_input($sd !== null && $sd >= 0 ? date('Y-m-d', strtotime($p['support_end'] . ' +1 day')) : today())]) ?>'><i class="bi bi-arrow-repeat me-1"></i>تمدید پشتیبانی (پولی)</button></div>
                    <?php endif; ?>
                </div></div>
                <div class="card">
                    <div class="card-header">تمدیدهای پشتیبانی</div>
                    <div class="card-body">
                        <?php foreach ($renewals as $rn): ?>
                            <div class="reply-box mb-2 small d-flex justify-content-between align-items-center">
                                <div><b><?= fa_digits($rn['months']) ?> ماه</b> · <?= jdate($rn['start_date']) ?> تا <?= jdate($rn['end_date']) ?><br>
                                    <?= money($rn['amount']) ?> <span class="badge bs-<?= $rn['is_paid'] ? 'success' : 'warning' ?>"><?= $rn['is_paid'] ? 'پرداخت شد ' . jdate($rn['paid_date']) : 'پرداخت نشده' ?></span></div>
                                <?php if ($canSupM): ?><div class="text-nowrap">
                                    <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#renewalModal" data-title="ویرایش تمدید" data-fill='<?= json_attr($rn + ['start_date' => jdate_input($rn['start_date']), 'paid_date' => jdate_input($rn['paid_date'])]) ?>'><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('support/renewal-delete') ?>" data-post='<?= json_attr(['id' => $rn['id']]) ?>' data-confirm="این تمدید حذف شود؟"><i class="bi bi-trash"></i></button></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$renewals): ?><div class="small text-muted text-center">تمدیدی ثبت نشده</div><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center"><span>تیکت‌ها و درخواست‌های مشتری</span>
                        <?php if ($canSupM): ?><button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#ticketModal" data-title="تیکت جدید" data-fill='<?= json_attr(['status' => 'open', 'priority' => 'normal']) ?>'><i class="bi bi-plus-lg"></i> تیکت</button><?php endif; ?></div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($tickets as $t): ?>
                            <a href="<?= url('support/ticket', ['id' => $t['id']]) ?>" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center">
                                <span><span class="fw-semibold"><?= e($t['subject']) ?></span><br><span class="small text-muted"><?= jdate($t['created_at'], true) ?> · <?= fa_digits($t['replies']) ?> پاسخ</span></span>
                                <span class="text-nowrap"><span class="badge bs-<?= priority_color($t['priority']) ?>"><?= priority_label($t['priority']) ?></span> <span class="badge bs-<?= ticket_status_color($t['status']) ?>"><?= ticket_status_label($t['status']) ?></span></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!$tickets): ?><div class="empty-state"><i class="bi bi-ticket"></i>تیکتی ثبت نشده</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- پیوست‌ها -->
    <?php if (Auth::can('finance')): $canShM = Auth::canManage('projects'); ?>
    <!-- سهم‌بندی سود -->
    <div class="tab-pane fade" id="tab-shares">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <span><i class="bi bi-pie-chart me-1 text-primary"></i>سهم‌بندی سود پروژه</span>
                <form class="ajax-form d-flex align-items-center gap-2" method="post" action="<?= url('shares/toggle') ?>"><?= csrf_field() ?>
                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                    <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="enabled" value="1" id="shEnable" <?= $p['profit_sharing'] ? 'checked' : '' ?> <?= $canShM ? '' : 'disabled' ?> onchange="this.form.requestSubmit()"><label class="form-check-label" for="shEnable">فعال</label></div>
                </form>
            </div>
            <?php if (!$p['profit_sharing']): ?>
                <div class="empty-state"><i class="bi bi-pie-chart"></i>سهم‌بندی برای این پروژه فعال نیست<?= $canShM ? '؛ با کلید بالا فعال کنید' : '' ?></div>
            <?php else: ?>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3"><div class="small text-muted">دریافتی</div><div class="fw-bold"><?= money($f['income']) ?></div></div>
                    <div class="col-6 col-md-3"><div class="small text-muted">هزینه ابزارها</div><div class="fw-bold"><?= money($f['expenses']) ?></div></div>
                    <div class="col-6 col-md-3"><div class="small text-muted">سود قابل تقسیم</div><div class="fw-bold text-success"><?= money($sh['base']) ?></div></div>
                    <div class="col-6 col-md-3"><div class="small text-muted">درصد تخصیص‌یافته</div><div class="fw-bold"><?= fa_digits(rtrim(rtrim(number_format($sh['percent'], 2), '0'), '.')) ?>٪ <span class="small text-muted">(باقی‌مانده <?= fa_digits(rtrim(rtrim(number_format(max(0, 100 - $sh['percent']), 2), '0'), '.')) ?>٪)</span></div></div>
                </div>
                <form class="ajax-form" method="post" action="<?= url('shares/save') ?>"><?= csrf_field() ?>
                    <input type="hidden" name="project_id" value="<?= $p['id'] ?>">
                    <div class="table-responsive"><table class="table align-middle mb-2">
                        <thead><tr><th>ادمین</th><th style="width:140px">درصد</th><th>مبلغ سهم</th><th style="width:50px"></th></tr></thead>
                        <tbody id="shareRows" class="share-rows">
                        <?php foreach ($sh['rows'] as $r): ?>
                            <tr><td><select name="admin_id[]" class="form-select" <?= $canShM ? '' : 'disabled' ?>><?php foreach ($shareAdmins as $a): ?><option value="<?= $a['id'] ?>" <?= $a['id'] == $r['admin_id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></td>
                                <td><input type="text" name="percent[]" class="form-control ltr" inputmode="decimal" value="<?= e(rtrim(rtrim(number_format((float)$r['percent'], 2, '.', ''), '0'), '.')) ?>" data-share-pct <?= $canShM ? '' : 'readonly' ?>></td>
                                <td class="fw-semibold text-success text-nowrap" data-share-amt><?= money($r['amount']) ?></td>
                                <td><?php if ($canShM): ?><button type="button" class="btn btn-sm btn-soft-danger btn-icon" data-rm-share><i class="bi bi-x-lg"></i></button><?php endif; ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                    <?php if ($canShM): ?>
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-soft" data-add-share><i class="bi bi-plus-lg"></i> افزودن نفر</button>
                        <div class="small" data-share-sum></div>
                        <button class="btn btn-grad"><i class="bi bi-check2 me-1"></i>ذخیره سهم‌بندی</button>
                    </div>
                    <?php endif; ?>
                </form>
                <template id="shareRowTpl"><tr><td><select name="admin_id[]" class="form-select"><option value="">انتخاب ادمین...</option><?php foreach ($shareAdmins as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?></select></td>
                    <td><input type="text" name="percent[]" class="form-control ltr" inputmode="decimal" data-share-pct></td><td class="fw-semibold text-success text-nowrap" data-share-amt>۰</td>
                    <td><button type="button" class="btn btn-sm btn-soft-danger btn-icon" data-rm-share><i class="bi bi-x-lg"></i></button></td></tr></template>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const base = <?= (int)$sh['base'] ?>, box = document.getElementById('shareRows'), sum = document.querySelector('[data-share-sum]');
                    function calc() {
                        let t = 0;
                        box.querySelectorAll('tr').forEach(tr => {
                            const v = parseFloat(MM.en(tr.querySelector('[data-share-pct]').value).replace('٫', '.')) || 0;
                            t += v;
                            tr.querySelector('[data-share-amt]').textContent = MM.money(Math.floor(base * v / 100));
                        });
                        if (sum) { sum.textContent = 'جمع درصدها: ' + MM.fa(Math.round(t * 100) / 100) + '٪'; sum.className = 'small ' + (t > 100.001 ? 'text-danger' : 'text-muted'); }
                    }
                    document.addEventListener('input', e => { if (e.target.matches('[data-share-pct]')) calc(); });
                    document.addEventListener('click', e => {
                        if (e.target.closest('[data-add-share]')) { box.appendChild(document.getElementById('shareRowTpl').content.firstElementChild.cloneNode(true)); calc(); }
                        if (e.target.closest('[data-rm-share]')) { e.target.closest('tr').remove(); calc(); }
                    });
                    calc();
                });
                </script>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="tab-pane fade" id="tab-files">
        <div class="card"><div class="card-body">
            <?php $attEntity = 'project'; $attId = (int)$p['id']; $attManage = $canManage; $attTitle = 'پیوست پروژه'; require APP_ROOT . '/app/views/partials/attachments.php'; ?>
        </div></div>
    </div>
</div>

<?php if ($canManage): require __DIR__ . '/_edit.php'; ?>
<?= modal_start('deliverModal', 'تحویل پروژه و پشتیبانی', 'bi-box-seam', url('projects/deliver'), '', ['id' => $p['id']]) ?>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">تاریخ تحویل <span class="req">*</span></label><input type="text" name="delivery_date" class="form-control jdate" required autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label">مدت پشتیبانی (ماه)</label><input type="number" min="0" name="support_months" class="form-control" required></div>
        <div class="col-12"><div class="alert alert-info py-2 small mb-0" data-support-end></div></div>
    </div>
<?= modal_end('ثبت تحویل') ?>
<?php endif; ?>

<?php if ($canFinM):
    $keptSum = 0;
    foreach ($payments as $pay) { if ($pay['status'] !== 'pending' || !empty($receipts[$pay['id']])) $keptSum += $pay['amount']; } ?>
<?= modal_start('scheduleModal', 'بازسازی برنامه پرداخت', 'bi-magic', url('projects/schedule'), 'modal-xl', ['id' => $p['id']]) ?>
    <div class="alert alert-info small py-2"><i class="bi bi-info-circle me-1"></i>ردیف‌هایی که دریافتی دارند حفظ می‌شوند (<?= money_plain($keptSum) ?>). برنامه جدید برای مبلغ باقیمانده ساخته می‌شود: <b><?= money_plain($p['total_amount'] - $keptSum) ?></b></div>
    <?php $bId = 's'; $bTotal = $p['total_amount'] - $keptSum; require __DIR__ . '/_builder.php'; ?>
<?= modal_end('ذخیره برنامه جدید') ?>
<?php endif; ?>

<?php if ($canFin) require APP_ROOT . '/app/views/finance/_modals.php'; ?>
<?php if ($canSup) require APP_ROOT . '/app/views/support/_modals.php'; ?>
