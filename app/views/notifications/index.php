<?php defined('APP_ROOT') or exit;
$icon = ['payment_due' => ['bi-alarm', 'g-blue'], 'payment_overdue' => ['bi-exclamation-octagon', 'g-orange'], 'support_ending' => ['bi-hourglass-split', 'g-teal'], 'internal_ticket' => ['bi-chat-square-text', 'g-blue']]; ?>
<div class="row justify-content-center"><div class="col-xl-8">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><span>همه اعلان‌ها</span>
        <a href="#" class="btn btn-sm btn-soft" data-mark-all-read="<?= url('notifications/read-all') ?>"><i class="bi bi-check2-all"></i> خواندن همه</a></div>
    <div class="card-body">
        <?php foreach ($items as $n): [$ic, $bg] = $icon[$n['type']] ?? ['bi-info-circle', 'g-slate']; ?>
            <a href="<?= url('notifications/open', ['id' => $n['id']]) ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?> mb-1">
                <span class="ni <?= $bg ?>"><i class="bi <?= $ic ?>"></i></span>
                <span><span class="nt d-block"><?= e($n['title']) ?></span><span class="small text-muted d-block"><?= e($n['body']) ?></span><span class="small text-muted"><?= jdate($n['created_at'], true) ?></span></span>
            </a>
        <?php endforeach; ?>
        <?php if (!$items): ?><div class="empty-state"><i class="bi bi-bell-slash"></i>اعلانی وجود ندارد</div><?php endif; ?>
    </div>
</div>
</div></div>
