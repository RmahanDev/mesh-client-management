<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canM = Auth::canManage('support');
$active = array_filter($projects, fn($p) => days_until($p['support_end']) >= 0);
$expired = array_filter($projects, fn($p) => days_until($p['support_end']) < 0);
?>
<div class="row g-3">
    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-hourglass-split me-1 text-success"></i>روزشمار پشتیبانی فعال (<?= fa_digits(count($active)) ?>)</span>
                <?php if ($canM): ?><button class="btn btn-sm btn-soft-success" data-bs-toggle="modal" data-bs-target="#renewalModal" data-title="تمدید پشتیبانی" data-fill="{}"><i class="bi bi-arrow-repeat"></i> تمدید</button><?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($active as $p): $d = days_until($p['support_end']);
                    $total = max(1, (strtotime($p['support_end']) - strtotime($p['support_start'])) / 86400);
                    $pct = max(0, min(100, round($d / $total * 100))); ?>
                    <a href="<?= url('projects/show', ['id' => $p['id'], 'tab' => 'support']) ?>" class="list-group-item list-group-item-action bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="min-w-0"><div class="fw-semibold text-truncate"><?= e($p['title']) ?></div><div class="small text-muted"><?= e($p['customer']) ?> · تا <?= jdate($p['support_end']) ?></div></div>
                            <div class="text-center ms-2"><div class="fs-4 fw-black <?= $d <= 7 ? 'text-warning' : 'text-success' ?>"><?= fa_digits($d) ?></div><div class="small text-muted">روز</div></div>
                        </div>
                        <div class="progress mt-2" style="height:5px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                        <?php if ($p['open_tickets']): ?><span class="badge bs-warning mt-2"><?= fa_digits($p['open_tickets']) ?> تیکت باز</span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (!$active): ?><div class="empty-state"><i class="bi bi-hourglass"></i>پشتیبانی فعالی وجود ندارد</div><?php endif; ?>
        </div>
        <?php if ($expired): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-hourglass-bottom me-1 text-warning"></i>پشتیبانی‌های تمام‌شده — فرصت تمدید (<?= fa_digits(count($expired)) ?>)</div>
            <div class="list-group list-group-flush">
                <?php foreach ($expired as $p): ?>
                    <div class="list-group-item bg-transparent d-flex justify-content-between align-items-center">
                        <a href="<?= url('projects/show', ['id' => $p['id'], 'tab' => 'support']) ?>" class="min-w-0 text-reset"><div class="fw-semibold text-truncate"><?= e($p['title']) ?></div><div class="small text-muted"><?= e($p['customer']) ?> · پایان: <?= jdate($p['support_end']) ?> (<?= fa_digits(-days_until($p['support_end'])) ?> روز پیش)</div></a>
                        <?php if ($canM): ?><button class="btn btn-sm btn-soft-warning" data-bs-toggle="modal" data-bs-target="#renewalModal" data-title="تمدید: <?= e($p['title']) ?>" data-fill='<?= json_attr(['project_id' => $p['id'], 'start_date' => jdate_input(today())]) ?>'>تمدید</button><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <span><i class="bi bi-ticket-detailed me-1 text-primary"></i>تیکت‌ها</span>
                <div class="d-flex gap-2">
                    <div class="btn-group btn-group-sm">
                        <?php foreach (['active' => 'جاری', 'closed' => 'بسته', 'all' => 'همه'] as $k => $v): ?><a href="<?= url('support', ['t' => $k]) ?>" class="btn <?= $tf === $k ? 'btn-primary' : 'btn-soft' ?>"><?= $v ?></a><?php endforeach; ?>
                    </div>
                    <?php if ($canM): ?><button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#ticketModal" data-title="تیکت جدید" data-fill='<?= json_attr(['status' => 'open', 'priority' => 'normal']) ?>'><i class="bi bi-plus-lg"></i> تیکت</button><?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>موضوع</th><th>پروژه</th><th>اولویت</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                    <tbody>
                    <?php foreach ($tickets as $t): ?>
                        <tr onclick="location.href='<?= url('support/ticket', ['id' => $t['id']]) ?>'" style="cursor:pointer">
                            <td><span class="fw-semibold"><?= e($t['subject']) ?></span><div class="small text-muted"><?= fa_digits($t['replies']) ?> پاسخ</div></td>
                            <td class="small"><?= e($t['project_title']) ?><div class="text-muted"><?= e($t['customer']) ?></div></td>
                            <td><span class="badge bs-<?= priority_color($t['priority']) ?>"><?= priority_label($t['priority']) ?></span></td>
                            <td><span class="badge bs-<?= ticket_status_color($t['status']) ?>"><?= ticket_status_label($t['status']) ?></span></td>
                            <td class="small text-nowrap"><?= jdate($t['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$tickets): ?><div class="empty-state"><i class="bi bi-ticket"></i>تیکتی وجود ندارد</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($canM) { $p = null; require __DIR__ . '/_modals.php'; } ?>
