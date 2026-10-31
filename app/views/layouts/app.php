<?php
defined('APP_ROOT') or exit;
use App\Core\Auth;
use App\Core\Notifier;

$route   = strtolower($_GET['r'] ?? 'dashboard');
$section = explode('/', $route)[0];
$user    = Auth::user();
$unread  = Notifier::unreadCount();
$notifs  = Notifier::latest(7);
$openTickets = Auth::can('support') ? (int)\App\Core\DB::value("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')") : 0;
$internalPending = \App\Models\InternalTicket::pendingCount();
$overdue = Auth::can('finance') ? (int)\App\Core\DB::value("SELECT COUNT(*) FROM payments p JOIN projects pr ON pr.id=p.project_id WHERE p.status<>'paid' AND p.due_date < CURDATE() AND pr.status<>'canceled'") : 0;

$nav = [
    ['اصلی', [
        ['dashboard', 'داشبورد', 'bi-grid-1x2', null, 0],
    ]],
    ['مدیریت', [
        ['customers', 'مشتریان', 'bi-people', 'customers', 0],
        ['projects', 'پروژه‌ها', 'bi-kanban', 'projects', 0],
        ['finance', 'پرداخت‌ها و اقساط', 'bi-cash-coin', 'finance', $overdue],
        ['support', 'پشتیبانی و تیکت', 'bi-headset', 'support', $openTickets],
        ['internal', 'تیکت داخلی', 'bi-chat-square-text', 'internal', $internalPending],
        ['shares', 'سهم من', 'bi-pie-chart', 'shares', 0],
    ]],
    ['تحلیل و تنظیمات', [
        ['reports', 'گزارش‌ها', 'bi-graph-up-arrow', 'reports', 0],
        ['categories', 'دسته‌بندی‌ها', 'bi-tags', 'categories', 0],
        ['admins', 'ادمین‌ها و دسترسی', 'bi-shield-check', '__super__', 0],
        ['settings', 'تنظیمات', 'bi-gear', '__super__', 0],
    ]],
];
$notifIcon = ['payment_due' => ['bi-alarm', 'g-blue'], 'payment_overdue' => ['bi-exclamation-octagon', 'g-orange'], 'support_ending' => ['bi-hourglass-split', 'g-teal'], 'internal_ticket' => ['bi-chat-square-text', 'g-blue'], 'share' => ['bi-pie-chart', 'g-teal']];
?>
<!doctype html>
<html lang="fa" dir="rtl" data-bs-theme="dark">
<head>
<?php require __DIR__ . '/head.php'; ?>
</head>
<body>
<aside class="mm-sidebar">
    <a href="<?= url('dashboard') ?>" class="mm-brand">
        <span class="mm-brand-logo"><svg viewBox="0 0 64 64" width="100%" height="100%" aria-hidden="true"><path d="M18 44V20l14 14 14-14v24" fill="none" stroke="#fff" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        <span>
            <span class="mm-brand-name d-block"><?= e(\App\Core\Settings::get('company_name', config('app_name'))) ?></span>
            <span class="mm-brand-sub">مدیریت مشتری، پرداخت و پشتیبانی</span>
        </span>
    </a>
    <nav class="mm-nav">
        <?php foreach ($nav as [$groupTitle, $items]):
            $visible = array_filter($items, fn($i) => $i[3] === null || ($i[3] === '__super__' ? Auth::isSuper() : Auth::can($i[3])));
            if (!$visible) continue; ?>
            <div class="mm-nav-title"><?= e($groupTitle) ?></div>
            <?php foreach ($visible as [$r, $label, $icon, $perm, $badge]): ?>
                <a href="<?= url($r) ?>" class="<?= $section === $r ? 'active' : '' ?>">
                    <i class="bi <?= $icon ?>"></i><span><?= e($label) ?></span>
                    <?php if ($badge): ?><span class="badge <?= $r === 'finance' ? 'bs-warning' : 'bs-primary' ?>"><?= fa_digits($badge) ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <div class="mm-sidebar-foot">
        <div class="d-flex align-items-center gap-2">
            <span class="avatar"><?= e(mb_substr($user['name'], 0, 1)) ?></span>
            <div class="flex-grow-1 small lh-sm">
                <div class="fw-bold"><?= e($user['name']) ?></div>
                <div class="text-muted"><?= $user['is_super'] ? 'مدیر کل' : 'ادمین' ?></div>
            </div>
            <a href="<?= url('auth/logout') ?>" class="icon-btn" title="خروج"><i class="bi bi-box-arrow-left"></i></a>
        </div>
    </div>
</aside>
<div class="mm-backdrop"></div>

<div class="mm-main">
    <header class="mm-topbar">
        <button class="icon-btn d-lg-none" data-toggle-sidebar><i class="bi bi-list"></i></button>
        <div class="flex-grow-1 min-w-0">
            <h1 class="text-truncate"><?= e($title ?? '') ?></h1>
            <?php if (!empty($subtitle)): ?><div class="small text-muted text-truncate"><?= $subtitle ?></div><?php endif; ?>
        </div>
        <span class="small text-muted d-none d-md-inline"><i class="bi bi-calendar3 me-1"></i><?= jdate(today()) ?></span>
        <button class="icon-btn d-none" data-install-app title="نصب برنامه روی دستگاه"><i class="bi bi-download"></i></button>
        <button class="icon-btn" data-toggle-theme title="تغییر تم"><i class="bi bi-circle-half"></i></button>
        <div class="dropdown">
            <button class="icon-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" title="اعلان‌ها">
                <i class="bi bi-bell"></i><?php if ($unread): ?><span class="dot"><?= fa_digits(min($unread, 99)) ?></span><?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-start notif-menu">
                <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-1">
                    <strong>اعلان‌ها</strong>
                    <?php if ($unread): ?><a href="#" class="small" data-mark-all-read="<?= url('notifications/read-all') ?>">خواندن همه</a><?php endif; ?>
                </div>
                <?php if (!$notifs): ?>
                    <div class="empty-state py-4"><i class="bi bi-bell-slash"></i>اعلانی ندارید</div>
                <?php endif; ?>
                <?php foreach ($notifs as $n): [$ic, $bg] = $notifIcon[$n['type']] ?? ['bi-info-circle', 'g-slate']; ?>
                    <a href="<?= url('notifications/open', ['id' => $n['id']]) ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                        <span class="ni <?= $bg ?>"><i class="bi <?= $ic ?>"></i></span>
                        <span class="min-w-0">
                            <span class="nt d-block"><?= e($n['title']) ?></span>
                            <span class="nb"><?= e($n['body']) ?></span>
                            <span class="small text-muted"><?= jdate($n['created_at'], true) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
                <a href="<?= url('notifications') ?>" class="dropdown-item text-center small mt-1">مشاهده همه اعلان‌ها</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="icon-btn" data-bs-toggle="dropdown"><i class="bi bi-person"></i></button>
            <ul class="dropdown-menu dropdown-menu-start">
                <li class="px-3 py-2 small"><strong><?= e($user['name']) ?></strong><br><span class="text-muted ltr">@<?= e($user['username']) ?></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#passwordModal"><i class="bi bi-key me-2"></i>تغییر رمز عبور</a></li>
                <li><a class="dropdown-item text-danger" href="<?= url('auth/logout') ?>"><i class="bi bi-box-arrow-left me-2"></i>خروج</a></li>
            </ul>
        </div>
    </header>

    <main class="mm-content">
        <?php foreach (get_flashes() as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show"><?= e($f['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div>

<?php require APP_ROOT . '/app/views/partials/global-modals.php'; ?>

<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($charts)): ?><script src="<?= asset('vendor/chartjs/chart.umd.js') ?>"></script><script src="<?= asset('js/charts.js') ?>"></script><?php endif; ?>
<?php foreach (($scripts ?? []) as $s): ?><script src="<?= asset($s) ?>"></script><?php endforeach; ?>
<?php if (!empty($inlineScript)): ?><script><?= $inlineScript ?></script><?php endif; ?>
</body>
</html>
