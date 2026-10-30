<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canManage = Auth::canManage('customers');
$canFin = Auth::can('finance');
$ct = contact_types();
$sum = ['total' => 0, 'paid' => 0, 'profit' => 0];
foreach ($projects as $p) { if ($p['status'] === 'canceled') continue; $sum['total'] += $p['total_amount']; $sum['paid'] += $finance[$p['id']]['income']; $sum['profit'] += $finance[$p['id']]['profit']; }
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center">
                <span class="avatar avatar-lg mb-2"><?= e(mb_substr($c['first_name'], 0, 1)) ?></span>
                <h2 class="h5 fw-bold mb-0"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></h2>
                <div class="text-muted small"><?= e($c['company'] ?: 'مشتری شخصی') ?></div>
                <?php if ($canManage): ?>
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#customerModal" data-title="ویرایش مشتری" data-fill='<?= json_attr($c + ['contacts' => $contacts]) ?>'><i class="bi bi-pencil me-1"></i>ویرایش</button>
                    <button class="btn btn-sm btn-soft-danger" data-confirm-url="<?= url('customers/delete') ?>" data-post='<?= json_attr(['id' => $c['id']]) ?>' data-confirm="مشتری «<?= e($c['first_name'] . ' ' . $c['last_name']) ?>» حذف شود؟"><i class="bi bi-trash me-1"></i>حذف</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body border-top">
                <?php foreach ($contacts as $x): [$lbl, $ic] = $ct[$x['type']] ?? ['سایر', 'bi-dot']; ?>
                    <div class="kv"><span class="k"><i class="bi <?= $ic ?> me-1"></i><?= e($x['label'] ?: $lbl) ?></span>
                        <span class="v ltr"><?php
                            $v = e($x['value']);
                            echo match ($x['type']) {
                                'mobile', 'phone' => '<a href="tel:' . $v . '">' . fa_digits($v) . '</a>',
                                'email' => '<a href="mailto:' . $v . '">' . $v . '</a>',
                                'website' => '<a href="' . (str_starts_with($x['value'], 'http') ? $v : 'https://' . $v) . '" target="_blank" rel="noopener">' . $v . '</a>',
                                'telegram' => '<a href="https://t.me/' . e(ltrim($x['value'], '@')) . '" target="_blank" rel="noopener">' . $v . '</a>',
                                default => $v,
                            }; ?></span></div>
                <?php endforeach; ?>
                <?php if ($c['national_code']): ?><div class="kv"><span class="k"><i class="bi bi-person-vcard me-1"></i>کد ملی</span><span class="v"><?= fa_digits(e($c['national_code'])) ?></span></div><?php endif; ?>
                <div class="kv"><span class="k"><i class="bi bi-calendar-plus me-1"></i>تاریخ ثبت</span><span class="v"><?= jdate($c['created_at']) ?></span></div>
            </div>
            <?php if ($c['notes']): ?><div class="card-body border-top small"><div class="text-muted mb-1">یادداشت</div><?= nl2br(e($c['notes'])) ?></div><?php endif; ?>
        </div>
        <div class="card"><div class="card-body">
            <?php $attEntity = 'customer'; $attId = (int)$c['id']; $attManage = $canManage; $attTitle = 'افزودن مدرک مشتری'; require APP_ROOT . '/app/views/partials/attachments.php'; ?>
        </div></div>
    </div>

    <div class="col-lg-8">
        <?php if ($canFin): ?>
        <div class="row g-3 mb-3">
            <div class="col-sm-4"><div class="stat-card text-primary"><span class="stat-icon g-blue"><i class="bi bi-file-earmark-text"></i></span><div class="stat-value text-body"><?= money($sum['total'], false) ?></div><div class="stat-label">جمع قراردادها (تومان)</div></div></div>
            <div class="col-sm-4"><div class="stat-card text-success"><span class="stat-icon g-green"><i class="bi bi-wallet2"></i></span><div class="stat-value text-body"><?= money($sum['paid'], false) ?></div><div class="stat-label">دریافت شده (تومان)</div></div></div>
            <div class="col-sm-4"><div class="stat-card text-info"><span class="stat-icon g-teal"><i class="bi bi-graph-up"></i></span><div class="stat-value text-body"><?= money($sum['profit'], false) ?></div><div class="stat-label">سود خالص (تومان)</div></div></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-kanban me-1 text-primary"></i>پروژه‌های این مشتری</span>
                <?php if (Auth::canManage('projects')): ?>
                    <a href="<?= url('projects', ['new' => 1, 'customer_id' => $c['id']]) ?>" class="btn btn-sm btn-grad"><i class="bi bi-plus-lg me-1"></i>پروژه جدید</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>پروژه</th><th>وضعیت</th><th>نوع پرداخت</th><?php if ($canFin): ?><th>مبلغ</th><th>پیشرفت پرداخت</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($projects as $p): $f = $finance[$p['id']]; $pct = $p['total_amount'] ? min(100, round($f['paid'] / $p['total_amount'] * 100)) : 0; ?>
                        <tr>
                            <td><a href="<?= url('projects/show', ['id' => $p['id']]) ?>" class="fw-bold"><?= e($p['title']) ?></a>
                                <div><?php foreach ($cats[$p['id']] as $cat): ?><span class="cat-chip ms-1" style="--c:<?= e($cat['color']) ?>"><?= e($cat['name']) ?></span><?php endforeach; ?></div></td>
                            <td><span class="badge bs-<?= project_status_color($p['status']) ?>"><?= project_status_label($p['status']) ?></span></td>
                            <td><?= payment_type_label($p['payment_type']) ?></td>
                            <?php if ($canFin): ?>
                            <td><?= money($p['total_amount']) ?></td>
                            <td style="min-width:130px"><div class="progress"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div><span class="small text-muted"><?= fa_digits($pct) ?>٪</span></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!$projects): ?><div class="empty-state"><i class="bi bi-kanban"></i>پروژه‌ای ثبت نشده</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if ($canManage) require __DIR__ . '/_form.php'; ?>
