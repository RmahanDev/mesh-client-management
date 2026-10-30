<?php defined('APP_ROOT') or exit;
$total = array_sum(array_column($mine, 'amount'));
?>
<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-4"><div class="stat-card text-success"><span class="stat-icon g-teal"><i class="bi bi-wallet2"></i></span>
        <div class="stat-value text-body"><?= money($total, false) ?></div><div class="stat-label">جمع سهم من از سود پروژه‌ها (تومان)</div></div></div>
    <div class="col-md-6 col-xl-4"><div class="stat-card text-primary"><span class="stat-icon g-blue"><i class="bi bi-kanban"></i></span>
        <div class="stat-value text-body"><?= fa_digits(count($mine)) ?></div><div class="stat-label">پروژه دارای سهم</div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-pie-chart me-1 text-primary"></i>سهم من از هر پروژه</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>پروژه</th><th>دریافتی</th><th>هزینه ابزارها</th><th>سود</th><th>درصد من</th><th>سهم من</th></tr></thead>
            <tbody>
            <?php foreach ($mine as $r): ?>
                <tr>
                    <td><span class="fw-semibold"><?= e($r['title']) ?></span><div class="small text-muted"><?= e($r['customer']) ?></div></td>
                    <td class="text-nowrap"><?= money($r['income']) ?></td>
                    <td class="text-nowrap"><?= money($r['expenses']) ?></td>
                    <td class="text-nowrap <?= $r['profit'] < 0 ? 'text-danger' : '' ?>"><?= money($r['profit']) ?></td>
                    <td><span class="badge bs-primary"><?= fa_digits(rtrim(rtrim(number_format((float)$r['percent'], 2), '0'), '.')) ?>٪</span></td>
                    <td class="fw-bold text-success text-nowrap"><?= money($r['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <?php if ($mine): ?><tfoot><tr><th colspan="5">جمع سهم من</th><th class="text-success text-nowrap"><?= money($total) ?></th></tr></tfoot><?php endif; ?>
        </table>
        <?php if (!$mine): ?><div class="empty-state"><i class="bi bi-pie-chart"></i>هنوز سهمی برای شما در پروژه‌ای ثبت نشده است</div><?php endif; ?>
    </div>
</div>
<div class="small text-muted mb-3"><i class="bi bi-info-circle me-1"></i>سهم هر نفر = درصد او × (دریافتی‌های پروژه − هزینه ابزارها). با ثبت دریافتی یا هزینه جدید، مبلغ‌ها خودکار به‌روز می‌شوند.</div>

<?php if ($everyone): ?>
<div class="card">
    <div class="card-header"><i class="bi bi-people me-1 text-primary"></i>جمع سهم همه افراد (فقط مدیر کل)</div>
    <div class="table-responsive"><table class="table">
        <thead><tr><th>ادمین</th><th>تعداد پروژه</th><th>جمع سهم</th></tr></thead>
        <tbody><?php foreach ($everyone as $e): ?><tr><td class="fw-semibold"><?= e($e['name']) ?></td><td><?= fa_digits($e['projects']) ?></td><td class="text-success fw-bold"><?= money($e['amount']) ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
</div>
<?php endif; ?>
