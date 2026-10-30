<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canProjects = Auth::can('projects'); ?>
<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="stat-card text-success"><span class="stat-icon g-green"><i class="bi bi-pie-chart"></i></span><div class="stat-value text-body"><?= money($shares['total'], false) ?></div><div class="stat-label">جمع سهم من تا امروز (تومان)</div></div></div>
    <div class="col-md-6"><div class="stat-card text-info"><span class="stat-icon g-cyan"><i class="bi bi-graph-up-arrow"></i></span><div class="stat-value text-body"><?= money($shares['expected'], false) ?></div><div class="stat-label">جمع سهم با دریافت کامل قراردادها (تومان)</div></div></div>
</div>
<div class="card">
    <div class="card-header">سهم من به تفکیک پروژه</div>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>پروژه</th><th>درصد من</th><th>سود خالص تا امروز</th><th>سهم من تا امروز</th><th>سهم با دریافت کامل</th></tr></thead>
            <tbody>
            <?php foreach ($shares['rows'] as $s): ?>
                <tr>
                    <td><?php if ($canProjects): ?><a class="fw-semibold" href="<?= url('projects/show', ['id' => $s['id']]) ?>"><?= e($s['title']) ?></a><?php else: ?><span class="fw-semibold"><?= e($s['title']) ?></span><?php endif; ?>
                        <div class="small text-muted"><?= e($s['customer']) ?> · <?= project_status_label($s['status']) ?></div></td>
                    <td><?= percent_label($s['percent']) ?></td>
                    <td class="text-nowrap <?= $s['profit'] < 0 ? 'text-danger' : '' ?>"><?= money($s['profit']) ?></td>
                    <td class="text-nowrap fw-bold text-success"><?= money($s['amount']) ?></td>
                    <td class="text-nowrap"><?= money($s['expected_amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$shares['rows']): ?><div class="empty-state"><i class="bi bi-pie-chart"></i>هنوز در پروژه‌ای سهمی برای شما تعریف نشده</div><?php endif; ?>
    </div>
</div>
