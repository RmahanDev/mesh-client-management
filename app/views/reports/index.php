<?php defined('APP_ROOT') or exit;
$q = ['from' => jdate_input($from), 'to' => jdate_input($to), 'cat' => $cat ?: '', 'customer' => $customer ?: ''];
$presets = [];
[$jy, $jm] = \App\Core\Jalali::yearMonth(today());
[$ms, $me] = \App\Core\Jalali::monthRange($jy, $jm);
$presets['این ماه'] = [$ms, $me];
[$ys] = \App\Core\Jalali::monthRange($jy, 1); [, $ye] = \App\Core\Jalali::monthRange($jy, 12);
$presets['امسال (' . fa_digits($jy) . ')'] = [$ys, $ye];
[$ls] = \App\Core\Jalali::monthRange($jy - 1, 1); [, $le] = \App\Core\Jalali::monthRange($jy - 1, 12);
$presets['پارسال (' . fa_digits($jy - 1) . ')'] = [$ls, $le];
?>
<div class="card mb-3 no-print">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="small text-muted ms-2"><i class="bi bi-funnel"></i> بازه گزارش: <b class="text-body"><?= jdate($from) ?></b> تا <b class="text-body"><?= jdate($to) ?></b></span>
            <?php foreach ($presets as $label => [$a, $b]): ?><a class="btn btn-sm btn-soft" href="<?= url('reports', ['from' => jdate_input($a), 'to' => jdate_input($b)]) ?>"><?= $label ?></a><?php endforeach; ?>
            <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#filterModal" data-keep><i class="bi bi-sliders"></i> فیلتر دلخواه</button>
            <div class="me-auto d-flex gap-2">
                <a href="<?= url('reports/excel', $q) ?>" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>خروجی Excel</a>
                <a href="<?= url('reports/pdf', $q) ?>" target="_blank" class="btn btn-sm btn-soft-danger"><i class="bi bi-file-earmark-pdf me-1"></i>خروجی PDF</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl"><div class="stat-card text-primary"><span class="stat-icon g-blue"><i class="bi bi-wallet2"></i></span><div class="stat-value text-body"><?= money($sum['income'], false) ?></div><div class="stat-label">دریافتی (تومان)</div></div></div>
    <div class="col-6 col-xl"><div class="stat-card text-warning"><span class="stat-icon g-orange"><i class="bi bi-tools"></i></span><div class="stat-value text-body"><?= money($sum['expense'], false) ?></div><div class="stat-label">هزینه‌ها</div></div></div>
    <div class="col-6 col-xl"><div class="stat-card text-success"><span class="stat-icon g-mix"><i class="bi bi-graph-up-arrow"></i></span><div class="stat-value <?= $sum['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($sum['profit'], false) ?></div><div class="stat-label">سود خالص</div></div></div>
    <div class="col-6 col-xl"><div class="stat-card text-info"><span class="stat-icon g-cyan"><i class="bi bi-file-earmark-plus"></i></span><div class="stat-value text-body"><?= money($sum['contracts'], false) ?></div><div class="stat-label">قراردادهای جدید</div></div></div>
    <div class="col-12 col-xl"><div class="stat-card text-secondary"><span class="stat-icon g-slate"><i class="bi bi-hourglass-split"></i></span><div class="stat-value text-body"><?= money($sum['receivable'], false) ?></div><div class="stat-label">مانده مطالبات پروژه‌ها</div></div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8"><div class="card h-100"><div class="card-header">نمودار سود ماهانه</div><div class="card-body"><div style="height:330px"><canvas id="repChart"></canvas></div></div></div></div>
    <div class="col-xl-4"><div class="card h-100"><div class="card-header">دریافتی به تفکیک دسته</div><div class="card-body">
        <?php if ($byCategory): ?><div style="height:300px"><canvas id="catChart"></canvas></div><?php else: ?><div class="empty-state"><i class="bi bi-pie-chart"></i>داده‌ای نیست</div><?php endif; ?>
    </div></div></div>
</div>

<div class="card mb-3">
    <div class="card-header">جدول ماهانه</div>
    <div class="table-responsive"><table class="table table-sm">
        <thead><tr><th>ماه</th><th>قرارداد جدید</th><th>دریافتی</th><th>هزینه</th><th>سود خالص</th></tr></thead>
        <tbody><?php foreach ($monthly as $m): ?><tr><td><?= fa_digits($m['label']) ?></td><td><?= money($m['contracts'], false) ?></td><td class="text-primary"><?= money($m['income'], false) ?></td><td class="text-warning"><?= money($m['expense'], false) ?></td><td class="fw-bold <?= $m['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($m['profit'], false) ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><span>سود به تفکیک پروژه</span><input type="search" class="form-control form-control-sm" style="max-width:220px" placeholder="فیلتر..." data-table-filter="#repProjects"></div>
    <div class="table-responsive"><table class="table table-hover" id="repProjects">
        <thead><tr><th>پروژه</th><th>مشتری</th><th>مبلغ کل</th><th>دریافتی بازه</th><th>هزینه بازه</th><th>سود بازه</th><th>مانده</th></tr></thead>
        <tbody><?php foreach ($projects as $r): ?>
            <tr><td><a href="<?= url('projects/show', ['id' => $r['id']]) ?>"><?= e($r['title']) ?></a></td><td class="small"><?= e($r['customer']) ?></td><td><?= money($r['total_amount'], false) ?></td><td class="text-primary"><?= money($r['income'], false) ?></td><td class="text-warning"><?= money($r['expense'], false) ?></td><td class="fw-bold <?= $r['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($r['profit'], false) ?></td><td><?= money($r['receivable'], false) ?></td></tr>
        <?php endforeach; ?></tbody>
    </table><?php if (!$projects): ?><div class="empty-state"><i class="bi bi-inbox"></i>داده‌ای در این بازه نیست</div><?php endif; ?></div>
</div>

<div class="modal fade" id="filterModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="get">
        <input type="hidden" name="r" value="reports">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-sliders"></i><span>فیلتر گزارش</span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-6"><label class="form-label">از تاریخ</label><input type="text" name="from" class="form-control jdate" value="<?= e($q['from']) ?>" autocomplete="off"></div>
            <div class="col-6"><label class="form-label">تا تاریخ</label><input type="text" name="to" class="form-control jdate" value="<?= e($q['to']) ?>" autocomplete="off"></div>
            <div class="col-12"><label class="form-label">دسته‌بندی</label><select name="cat" class="form-select"><option value="">همه</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= str_repeat('— ', $c['depth']) . e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-12"><label class="form-label">مشتری</label><select name="customer" class="form-select"><option value="">همه</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $customer == $c['id'] ? 'selected' : '' ?>><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option><?php endforeach; ?></select></div>
        </div></div>
        <div class="modal-footer"><button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button><button class="btn btn-grad">اعمال فیلتر</button></div>
    </form>
</div></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    MM.profitChart(document.getElementById('repChart'), <?= json_encode(array_values($monthly), JSON_UNESCAPED_UNICODE) ?>);
    const cc = document.getElementById('catChart');
    if (cc) MM.donutChart(cc, <?= json_encode($byCategory, JSON_UNESCAPED_UNICODE) ?>);
});
</script>
