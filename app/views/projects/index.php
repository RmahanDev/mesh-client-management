<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canManage = Auth::canManage('projects');
$canFin = Auth::can('finance'); ?>
<form class="card mb-3" method="get">
    <div class="card-body py-3">
        <input type="hidden" name="r" value="projects">
        <div class="row g-2 align-items-center">
            <div class="col-md-4"><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input type="search" name="q" value="<?= e($filters['q']) ?>" class="form-control" placeholder="عنوان پروژه یا نام مشتری..."></div></div>
            <div class="col-6 col-md-2"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">همه وضعیت‌ها</option><?php foreach (['pending', 'active', 'delivered', 'canceled'] as $s): ?><option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= project_status_label($s) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-md-2"><select name="type" class="form-select" onchange="this.form.submit()"><option value="">همه انواع پرداخت</option><?php foreach (['single', 'stages', 'installment'] as $t): ?><option value="<?= $t ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= payment_type_label($t) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><select name="cat" class="form-select" onchange="this.form.submit()"><option value="">همه دسته‌ها</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $filters['cat'] == $c['id'] ? 'selected' : '' ?>><?= str_repeat('— ', $c['depth']) . e($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2 text-md-start">
                <?php if ($canManage): ?><button type="button" class="btn btn-grad w-100" data-bs-toggle="modal" data-bs-target="#projectCreateModal" data-fill="{}"><i class="bi bi-plus-lg me-1"></i>پروژه جدید</button><?php endif; ?>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>پروژه</th><th>مشتری</th><th>وضعیت</th><th>پرداخت</th><?php if ($canFin): ?><th>مبلغ کل</th><th>دریافتی</th><th>سود خالص</th><?php endif; ?><th>پشتیبانی</th></tr></thead>
            <tbody>
            <?php foreach ($projects as $p): $f = $finance[$p['id']]; $pct = $p['total_amount'] ? min(100, round($f['paid'] / $p['total_amount'] * 100)) : 0; $sd = days_until($p['support_end']); ?>
                <tr onclick="if(!event.target.closest('a,button'))location.href='<?= url('projects/show', ['id' => $p['id']]) ?>'" style="cursor:pointer">
                    <td>
                        <a href="<?= url('projects/show', ['id' => $p['id']]) ?>" class="fw-bold"><?= e($p['title']) ?></a>
                        <div class="mt-1"><?php foreach ($cats[$p['id']] as $c): ?><span class="cat-chip ms-1 mb-1" style="--c:<?= e($c['color']) ?>"><i class="bi <?= e($c['icon']) ?>"></i><?= e($c['name']) ?></span><?php endforeach; ?></div>
                    </td>
                    <td><a href="<?= url('customers/show', ['id' => $p['customer_id']]) ?>" class="text-reset"><?= e($p['customer']) ?></a></td>
                    <td><span class="badge bs-<?= project_status_color($p['status']) ?>"><?= project_status_label($p['status']) ?></span></td>
                    <td><span class="small"><?= payment_type_label($p['payment_type']) ?></span>
                        <?php if ($canFin && $f['overdue'] > 0): ?><div><span class="badge bs-danger">معوق <?= money_plain($f['overdue']) ?></span></div><?php endif; ?></td>
                    <?php if ($canFin): ?>
                        <td class="text-nowrap"><?= money($p['total_amount']) ?></td>
                        <td style="min-width:120px"><div class="small mb-1"><?= money($f['paid'], false) ?></div><div class="progress" style="height:5px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div></td>
                        <td class="text-nowrap <?= $f['profit'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($f['profit'], false) ?></td>
                    <?php endif; ?>
                    <td class="text-nowrap small">
                        <?php if ($sd === null): ?><span class="text-muted">—</span>
                        <?php elseif ($sd < 0): ?><span class="badge bs-secondary">تمام شده</span>
                        <?php else: ?><span class="badge bs-<?= $sd <= 7 ? 'warning' : 'success' ?>"><?= fa_digits($sd) ?> روز</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$projects): ?><div class="empty-state"><i class="bi bi-kanban"></i>پروژه‌ای پیدا نشد</div><?php endif; ?>
    </div>
</div>

<?php if ($canManage): require __DIR__ . '/_create.php';
    if (input('new')): ?>
    <button type="button" class="d-none" id="autoNewProject" data-bs-toggle="modal" data-bs-target="#projectCreateModal" data-fill='<?= json_attr(['customer_id' => input_int('customer_id')]) ?>'></button>
    <script>document.addEventListener('DOMContentLoaded', () => setTimeout(() => document.getElementById('autoNewProject').click(), 150));</script>
<?php endif; endif; ?>
