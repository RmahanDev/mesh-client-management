<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$user = Auth::user();
$canFin = Auth::can('finance');
$hour = (int)date('G');
$greet = $hour < 12 ? 'صبح بخیر' : ($hour < 17 ? 'روز بخیر' : 'عصر بخیر');
?>
<div class="hero-card mb-3">
    <div class="row align-items-center position-relative">
        <div class="col-md-7">
            <div class="opacity-75 small"><?= jdate(today()) ?></div>
            <h2 class="h4 fw-black my-1"><?= $greet ?>، <?= e($user['name']) ?> 👋</h2>
            <div class="opacity-75">خلاصه وضعیت مشتری‌ها، پرداخت‌ها و پشتیبانی</div>
        </div>
        <?php if ($canFin): ?>
        <div class="col-md-5 mt-3 mt-md-0">
            <div class="d-flex gap-3 justify-content-md-end">
                <div><div class="small opacity-75">سود خالص <?= $month['name'] ?></div><div class="fs-4 fw-black"><?= money($month['profit'], false) ?></div></div>
                <div class="vr opacity-50"></div>
                <div><div class="small opacity-75">کل مطالبات</div><div class="fs-4 fw-black"><?= money($receivable, false) ?></div></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <?php if ($counts['customers'] !== null): ?><div class="col-6 col-xl-3"><a href="<?= url('customers') ?>" class="stat-card d-block text-primary"><span class="stat-icon g-blue"><i class="bi bi-people"></i></span><div class="stat-value text-body"><?= num($counts['customers']) ?></div><div class="stat-label">مشتری</div></a></div><?php endif; ?>
    <?php if ($counts['active'] !== null): ?><div class="col-6 col-xl-3"><a href="<?= url('projects', ['status' => 'active']) ?>" class="stat-card d-block text-info"><span class="stat-icon g-cyan"><i class="bi bi-kanban"></i></span><div class="stat-value text-body"><?= num($counts['active']) ?></div><div class="stat-label">پروژه در جریان</div></a></div><?php endif; ?>
    <?php if ($counts['support'] !== null): ?><div class="col-6 col-xl-3"><a href="<?= url('support') ?>" class="stat-card d-block text-success"><span class="stat-icon g-teal"><i class="bi bi-hourglass-split"></i></span><div class="stat-value text-body"><?= num($counts['support']) ?></div><div class="stat-label">پشتیبانی فعال</div></a></div><?php endif; ?>
    <?php if ($counts['tickets'] !== null): ?><div class="col-6 col-xl-3"><a href="<?= url('support') ?>" class="stat-card d-block text-warning"><span class="stat-icon g-orange"><i class="bi bi-ticket-detailed"></i></span><div class="stat-value text-body"><?= num($counts['tickets']) ?></div><div class="stat-label">تیکت باز</div></a></div><?php endif; ?>
</div>

<?php $myShares = \App\Models\Project::sharesOf((int)Auth::id()); if ($myShares): ?>
<div class="mb-3"><a href="<?= url('shares') ?>" class="stat-card stat-card-wide d-flex text-success">
    <span class="stat-icon g-teal"><i class="bi bi-pie-chart"></i></span>
    <span class="stat-wide-body"><span class="stat-value d-block text-body"><?= money(array_sum(array_column($myShares, 'amount')), false) ?> <small class="fs-6 fw-normal text-muted">تومان</small></span><span class="stat-label d-block">جمع سهم من از <?= fa_digits(count($myShares)) ?> پروژه</span></span>
    <span class="stat-wide-go"><span class="d-none d-sm-inline">مشاهده جزئیات</span> <i class="bi bi-arrow-left"></i></span>
</a></div>
<?php endif; ?>
<?php $itInbox = \App\Models\InternalTicket::inbox(5); if ($itInbox): ?>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-chat-square-text me-1 text-primary"></i>تیکت‌های داخلی روی میز شما (<?= fa_digits(\App\Models\InternalTicket::pendingCount()) ?>)</span><a href="<?= url('internal') ?>" class="small">همه</a></div>
    <div class="list-group list-group-flush">
        <?php foreach ($itInbox as $it): ?>
            <a href="<?= url('internal/show', ['id' => $it['id']]) ?>" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center">
                <span class="min-w-0 text-truncate"><?= e($it['subject']) ?> <span class="small text-muted">از <?= e($it['creator_name']) ?></span></span>
                <span class="badge bs-<?= priority_color($it['priority']) ?>"><?= priority_label($it['priority']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($canFin): ?>
<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center"><span><i class="bi bi-bar-chart-line me-1 text-primary"></i>سود ماهانه — ۱۲ ماه اخیر</span>
                <?php if (Auth::can('reports')): ?><a href="<?= url('reports') ?>" class="small">گزارش کامل</a><?php endif; ?></div>
            <div class="card-body"><div style="height:320px"><canvas id="profitChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calendar3 me-1 text-success"></i><?= $month['name'] ?></div>
            <div class="card-body">
                <div class="kv"><span class="k">دریافتی</span><span class="v text-primary"><?= money($month['income']) ?></span></div>
                <div class="kv"><span class="k">هزینه‌ها</span><span class="v text-warning"><?= money($month['expense']) ?></span></div>
                <div class="kv"><span class="k">سود خالص</span><span class="v <?= $month['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($month['profit']) ?></span></div>
                <hr>
                <div class="small text-muted mb-1">از ابتدا</div>
                <div class="kv"><span class="k">کل دریافتی</span><span class="v"><?= money($totals['income']) ?></span></div>
                <div class="kv"><span class="k">کل هزینه</span><span class="v"><?= money($totals['expense']) ?></span></div>
                <div class="kv"><span class="k">سود خالص کل</span><span class="v text-success"><?= money($totals['income'] - $totals['expense']) ?></span></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-alarm me-1 text-primary"></i>سررسید <?= fa_digits($days) ?> روز آینده</span><a href="<?= url('finance', ['f' => 'upcoming']) ?>" class="small">همه</a></div>
            <div class="list-group list-group-flush">
                <?php foreach ($upcoming as $r): $l = days_until($r['due_date']); ?>
                    <a href="<?= url('projects/show', ['id' => $r['pid'], 'tab' => 'payments']) ?>" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center">
                        <span class="min-w-0"><span class="fw-semibold d-block text-truncate"><?= e($r['customer']) ?> — <?= e($r['title']) ?></span><span class="small text-muted"><?= e($r['project_title']) ?> · <?= jdate($r['due_date']) ?></span></span>
                        <span class="text-nowrap text-start"><span class="d-block fw-bold"><?= money($r['amount'] - $r['paid_amount'], false) ?></span><span class="badge bs-<?= $l <= 2 ? 'warning' : 'primary' ?>"><?= $l === 0 ? 'امروز' : fa_digits($l) . ' روز' ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!$upcoming): ?><div class="empty-state py-4"><i class="bi bi-calendar-check"></i>سررسیدی در این بازه نیست</div><?php endif; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-exclamation-octagon me-1 text-warning"></i>پرداخت‌های معوق</span><a href="<?= url('finance', ['f' => 'overdue']) ?>" class="small">همه</a></div>
            <div class="list-group list-group-flush">
                <?php foreach ($overdue as $r): ?>
                    <a href="<?= url('projects/show', ['id' => $r['pid'], 'tab' => 'payments']) ?>" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center">
                        <span class="min-w-0"><span class="fw-semibold d-block text-truncate"><?= e($r['customer']) ?> — <?= e($r['title']) ?></span><span class="small text-muted"><?= e($r['project_title']) ?> · <?= jdate($r['due_date']) ?></span></span>
                        <span class="text-nowrap text-start"><span class="d-block fw-bold text-warning"><?= money($r['amount'] - $r['paid_amount'], false) ?></span><span class="badge bs-danger"><?= fa_digits(-days_until($r['due_date'])) ?> روز تأخیر</span></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!$overdue): ?><div class="empty-state py-4"><i class="bi bi-emoji-smile"></i>هیچ پرداخت معوقی ندارید</div><?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <?php if (isset($ending)): ?>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-hourglass-bottom me-1 text-success"></i>پشتیبانی‌های رو به پایان (۳۰ روز)</div>
            <div class="list-group list-group-flush">
                <?php foreach ($ending as $r): $l = days_until($r['support_end']); ?>
                    <a href="<?= url('projects/show', ['id' => $r['id'], 'tab' => 'support']) ?>" class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center">
                        <span><span class="fw-semibold d-block"><?= e($r['title']) ?></span><span class="small text-muted"><?= e($r['customer']) ?></span></span>
                        <span class="badge bs-<?= $l <= 7 ? 'warning' : 'success' ?>"><?= fa_digits($l) ?> روز</span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!$ending): ?><div class="empty-state py-4"><i class="bi bi-shield-check"></i>موردی نیست</div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (isset($recent)): ?>
    <div class="col-lg-<?= isset($ending) ? 7 : 12 ?>">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between"><span><i class="bi bi-clock-history me-1 text-primary"></i>آخرین پروژه‌ها</span><a href="<?= url('projects') ?>" class="small">همه</a></div>
            <div class="table-responsive"><table class="table table-hover">
                <tbody><?php foreach ($recent as $r): ?>
                    <tr><td><a href="<?= url('projects/show', ['id' => $r['id']]) ?>" class="fw-semibold"><?= e($r['title']) ?></a><div class="small text-muted"><?= e($r['customer']) ?></div></td>
                        <td><span class="badge bs-<?= project_status_color($r['status']) ?>"><?= project_status_label($r['status']) ?></span></td>
                        <?php if ($canFin): ?><td class="text-nowrap"><?= money($r['total_amount']) ?></td><?php endif; ?>
                        <td class="small text-muted text-nowrap"><?= jdate($r['created_at']) ?></td></tr>
                <?php endforeach; ?></tbody>
            </table><?php if (!$recent): ?><div class="empty-state py-4"><i class="bi bi-kanban"></i>هنوز پروژه‌ای ثبت نشده</div><?php endif; ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php if ($canFin): ?>
<script>document.addEventListener('DOMContentLoaded', () => MM.profitChart(document.getElementById('profitChart'), <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>));</script>
<?php endif; ?>
