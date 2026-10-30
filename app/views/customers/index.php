<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canManage = Auth::canManage('customers');
$ct = contact_types(); ?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <form class="flex-grow-1" method="get" style="max-width:420px">
        <input type="hidden" name="r" value="customers">
        <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="q" value="<?= e($q) ?>" class="form-control" placeholder="جستجو نام، شرکت، موبایل، ایمیل، کد ملی..."></div>
    </form>
    <?php if ($canManage): ?>
        <button class="btn btn-grad me-auto" data-bs-toggle="modal" data-bs-target="#customerModal" data-title="مشتری جدید" data-fill="{}"><i class="bi bi-person-plus me-1"></i>مشتری جدید</button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>مشتری</th><th>راه‌های ارتباطی</th><th class="text-center">پروژه‌ها</th><th>جمع قراردادها</th><th>مانده بدهی</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($customers as $c): $cs = $contacts[$c['id']] ?? []; ?>
                <tr>
                    <td>
                        <a href="<?= url('customers/show', ['id' => $c['id']]) ?>" class="d-flex align-items-center gap-2 text-reset">
                            <span class="avatar"><?= e(mb_substr($c['first_name'], 0, 1)) ?></span>
                            <span><span class="fw-bold d-block"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></span>
                            <span class="small text-muted"><?= e($c['company'] ?: 'شخصی') ?></span></span>
                        </a>
                    </td>
                    <td>
                        <?php foreach (array_slice($cs, 0, 3) as $x): ?>
                            <span class="badge badge-soft fw-normal mb-1"><i class="bi <?= $ct[$x['type']][1] ?? 'bi-dot' ?> me-1"></i><span class="ltr"><?= e(fa_digits($x['value'])) ?></span></span>
                        <?php endforeach; ?>
                        <?php if (count($cs) > 3): ?><span class="small text-muted">+<?= fa_digits(count($cs) - 3) ?></span><?php endif; ?>
                    </td>
                    <td class="text-center"><span class="badge bs-primary"><?= fa_digits($c['projects_count']) ?></span></td>
                    <td><?= money($c['total_amount']) ?></td>
                    <td><?= $c['debt'] > 0 ? '<span class="text-warning fw-bold">' . money($c['debt']) . '</span>' : '<span class="badge bs-success">تسویه</span>' ?></td>
                    <td class="text-nowrap text-start">
                        <a href="<?= url('customers/show', ['id' => $c['id']]) ?>" class="btn btn-sm btn-soft btn-icon" title="پرونده"><i class="bi bi-eye"></i></a>
                        <?php if ($canManage): ?>
                            <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#customerModal" data-title="ویرایش مشتری"
                                    data-fill='<?= json_attr($c + ['contacts' => $cs]) ?>' title="ویرایش"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$customers): ?><div class="empty-state"><i class="bi bi-people"></i><?= $q ? 'مشتری با این مشخصات پیدا نشد' : 'هنوز مشتری ثبت نشده است' ?></div><?php endif; ?>
    </div>
</div>
<?php if ($canManage) require __DIR__ . '/_form.php'; ?>
