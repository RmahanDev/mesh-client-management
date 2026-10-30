<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$boxes = ['inbox' => ['میز من', 'bi-inbox'], 'dept' => ['دپارتمان من', 'bi-diagram-3'], 'sent' => ['ارسال‌شده‌ها', 'bi-send']];
if (Auth::isSuper()) $boxes['all'] = ['همه تیکت‌ها', 'bi-collection'];
require __DIR__ . '/_modals.php';
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="btn-group btn-group-sm">
            <?php foreach ($boxes as $k => [$l, $i]): ?><a href="<?= url('internal', ['box' => $k, 'st' => $st]) ?>" class="btn <?= $box === $k ? 'btn-primary' : 'btn-soft' ?>"><i class="bi <?= $i ?> me-1"></i><?= $l ?><?php if ($k === 'inbox' && $pending): ?> <span class="badge bs-warning"><?= fa_digits($pending) ?></span><?php endif; ?></a><?php endforeach; ?>
        </div>
        <div class="d-flex gap-2">
            <div class="btn-group btn-group-sm">
                <?php foreach (['active' => 'جاری', 'done' => 'انجام‌شده', 'all' => 'همه'] as $k => $v): ?><a href="<?= url('internal', ['box' => $box, 'st' => $k]) ?>" class="btn <?= $st === $k ? 'btn-primary' : 'btn-soft' ?>"><?= $v ?></a><?php endforeach; ?>
            </div>
            <button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#itModal" data-title="تیکت داخلی جدید" data-fill='<?= json_attr(['priority' => 'normal']) ?>'><i class="bi bi-plus-lg"></i> تیکت جدید</button>
        </div>
    </div>
    <?php if (!$myDept && !Auth::isSuper()): ?><div class="alert alert-warning small m-3 mb-0">شما هنوز به دپارتمانی تعلق ندارید؛ تیکت‌های دپارتمانی را نمی‌بینید. از مدیر کل بخواهید دپارتمان شما را در بخش «ادمین‌ها» تعیین کند.</div><?php endif; ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>موضوع</th><th>از</th><th>به</th><th>اولویت</th><th>وضعیت</th><th>آخرین تغییر</th></tr></thead>
            <tbody>
            <?php foreach ($tickets as $t): $mine = (int)$t['assigned_to'] === Auth::id(); ?>
                <tr onclick="location.href='<?= url('internal/show', ['id' => $t['id']]) ?>'" style="cursor:pointer">
                    <td><span class="fw-semibold"><?= e($t['subject']) ?></span><?php if ($mine): ?> <span class="badge bs-primary">برای شما</span><?php endif; ?>
                        <div class="small text-muted">#<?= fa_digits($t['id']) ?> · <?= fa_digits($t['replies']) ?> پاسخ</div></td>
                    <td class="small"><?= e($t['creator_name']) ?><div class="text-muted"><?= e($t['from_dept_name'] ?? '—') ?></div></td>
                    <td class="small"><?= e($t['assignee_name'] ?? 'بدون مسئول') ?><div class="text-muted"><?= e($t['to_dept_name'] ?? '—') ?></div></td>
                    <td><span class="badge bs-<?= priority_color($t['priority']) ?>"><?= priority_label($t['priority']) ?></span></td>
                    <td><span class="badge bs-<?= internal_status_color($t['status']) ?>"><?= internal_status_label($t['status']) ?></span></td>
                    <td class="small text-nowrap"><?= jdate($t['updated_at'] ?: $t['created_at'], true) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$tickets): ?><div class="empty-state"><i class="bi bi-inbox"></i>تیکتی وجود ندارد</div><?php endif; ?>
    </div>
</div>
