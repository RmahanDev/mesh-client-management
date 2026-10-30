<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canM = Auth::canManage('finance');
$days = (int)\App\Core\Settings::get('reminder_days', 7);
$tabs = ['upcoming' => 'سررسید ۳۰ روز آینده', 'overdue' => 'معوق', 'unpaid' => 'همه پرداخت‌نشده‌ها', 'nodate' => 'بدون تاریخ', 'paid' => 'پرداخت‌شده', 'all' => 'همه'];
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><div class="stat-card text-danger"><span class="stat-icon g-orange"><i class="bi bi-exclamation-octagon"></i></span><div class="stat-value text-body"><?= money($stats['overdue'], false) ?></div><div class="stat-label">معوق (<?= fa_digits($stats['overdue_count']) ?> مورد)</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card text-primary"><span class="stat-icon g-blue"><i class="bi bi-alarm"></i></span><div class="stat-value text-body"><?= money($stats['soon'], false) ?></div><div class="stat-label">سررسید <?= fa_digits($days) ?> روز آینده (<?= fa_digits($stats['soon_count']) ?>)</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card text-info"><span class="stat-icon g-cyan"><i class="bi bi-hourglass-split"></i></span><div class="stat-value text-body"><?= money($stats['receivable'], false) ?></div><div class="stat-label">کل مطالبات</div></div></div>
    <div class="col-6 col-xl-3"><div class="stat-card text-success"><span class="stat-icon g-green"><i class="bi bi-wallet2"></i></span><div class="stat-value text-body"><?= money($stats['month_income'], false) ?></div><div class="stat-label">دریافتی این ماه</div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <ul class="nav nav-pills nav-pills-mm flex-grow-1">
        <?php foreach ($tabs as $k => $v): ?><li class="nav-item"><a class="nav-link <?= $filter === $k ? 'active' : '' ?>" href="<?= url('finance', ['f' => $k]) ?>"><?= $v ?></a></li><?php endforeach; ?>
    </ul>
    <input type="search" class="form-control" style="max-width:240px" placeholder="فیلتر سریع..." data-table-filter="#payTable">
</div>

<div class="card mb-3">
    <div class="table-responsive">
        <table class="table table-hover" id="payTable">
            <thead><tr><th>سررسید</th><th>مشتری / پروژه</th><th>عنوان</th><th>مبلغ</th><th>مانده</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $left = days_until($r['due_date']); $rest = $r['amount'] - $r['paid_amount']; $over = $r['status'] !== 'paid' && $left !== null && $left < 0; ?>
                <tr class="<?= $over ? 'table-row-overdue' : '' ?>">
                    <td class="text-nowrap"><?= jdate($r['due_date']) ?>
                        <?php if ($r['status'] !== 'paid' && $left !== null): ?><div class="small <?= $left < 0 ? 'text-danger' : ($left <= $days ? 'text-warning' : 'text-muted') ?>"><?= $left < 0 ? fa_digits(-$left) . ' روز گذشته' : ($left === 0 ? 'امروز' : fa_digits($left) . ' روز مانده') ?></div><?php endif; ?></td>
                    <td><a href="<?= url('customers/show', ['id' => $r['cid']]) ?>" class="text-reset fw-semibold"><?= e($r['customer']) ?></a><div class="small"><a href="<?= url('projects/show', ['id' => $r['pid'], 'tab' => 'payments']) ?>"><?= e($r['project_title']) ?></a></div></td>
                    <td><?= e($r['title']) ?></td>
                    <td class="text-nowrap"><?= money($r['amount']) ?></td>
                    <td class="text-nowrap"><?= $rest > 0 ? money($rest, false) : '—' ?></td>
                    <td><?= ['pending' => '<span class="badge bs-' . ($over ? 'danger">معوق' : 'secondary">در انتظار') . '</span>', 'partial' => '<span class="badge bs-warning">ناقص</span>', 'paid' => '<span class="badge bs-success">پرداخت شد ' . jdate($r['paid_date']) . '</span>'][$r['status']] ?></td>
                    <td class="text-start text-nowrap">
                        <?php if ($canM && $r['status'] !== 'paid'): ?>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal" data-fill='<?= json_attr(['payment_id' => $r['id'], 'amount' => $rest, '_info' => $r['customer'] . ' — ' . $r['project_title'] . ' — ' . $r['title'] . ' (مانده: ' . money_plain($rest) . ')']) ?>'><i class="bi bi-check2"></i> دریافت</button>
                        <?php endif; ?>
                        <a href="<?= url('projects/show', ['id' => $r['pid'], 'tab' => 'payments']) ?>" class="btn btn-sm btn-soft btn-icon"><i class="bi bi-box-arrow-up-left"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$rows): ?><div class="empty-state"><i class="bi bi-check2-all"></i>موردی در این فیلتر وجود ندارد</div><?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">آخرین دریافتی‌ها</div>
            <div class="table-responsive"><table class="table table-sm">
                <thead><tr><th>تاریخ</th><th>پروژه</th><th>بابت</th><th>مبلغ</th><th>روش</th></tr></thead>
                <tbody><?php foreach ($recentReceipts as $r): ?>
                    <tr><td><?= jdate($r['paid_date']) ?></td><td><a href="<?= url('projects/show', ['id' => $r['pid'], 'tab' => 'payments']) ?>"><?= e($r['project_title']) ?></a><div class="small text-muted"><?= e($r['customer']) ?></div></td><td class="small"><?= e($r['payment_title']) ?></td><td class="text-success text-nowrap"><?= money($r['amount'], false) ?></td><td class="small"><?= payment_method_label($r['method']) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table><?php if (!$recentReceipts): ?><div class="empty-state py-3"><i class="bi bi-receipt"></i>دریافتی ثبت نشده</div><?php endif; ?></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center"><span>آخرین هزینه‌ها</span>
                <?php if ($canM): ?><button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#expenseModal" data-title="هزینه جدید" data-fill="{}"><i class="bi bi-plus-lg"></i> هزینه</button><?php endif; ?></div>
            <div class="table-responsive"><table class="table table-sm">
                <thead><tr><th>تاریخ</th><th>عنوان</th><th>مبلغ</th></tr></thead>
                <tbody><?php foreach ($expenses as $x): ?>
                    <tr><td><?= jdate($x['expense_date']) ?></td><td><?= e($x['title']) ?><div class="small"><a href="<?= url('projects/show', ['id' => $x['pid'], 'tab' => 'expenses']) ?>"><?= e($x['project_title']) ?></a></div></td><td class="text-warning text-nowrap"><?= money($x['amount'], false) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table><?php if (!$expenses): ?><div class="empty-state py-3"><i class="bi bi-tools"></i>هزینه‌ای ثبت نشده</div><?php endif; ?></div>
        </div>
    </div>
</div>
<?php if ($canM) { $p = null; require __DIR__ . '/_modals.php'; } ?>
