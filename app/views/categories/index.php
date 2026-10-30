<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canM = Auth::canManage('categories');
$icons = ['bi-tag', 'bi-window', 'bi-search', 'bi-shield-lock', 'bi-android2', 'bi-apple', 'bi-palette', 'bi-server', 'bi-database', 'bi-pc-display', 'bi-mortarboard', 'bi-headset', 'bi-wordpress', 'bi-cart3', 'bi-building', 'bi-code-slash', 'bi-filetype-php', 'bi-link-45deg', 'bi-megaphone', 'bi-camera', 'bi-brush', 'bi-cloud', 'bi-cpu', 'bi-robot', 'bi-phone', 'bi-hdd-network', 'bi-bug', 'bi-three-dots'];
$render = function ($nodes) use (&$render, $canM) {
    foreach ($nodes as $n): ?>
        <div>
            <div class="cat-row">
                <span class="cat-icon" style="background:<?= e($n['color']) ?>"><i class="bi <?= e($n['icon']) ?>"></i></span>
                <div class="flex-grow-1"><span class="fw-semibold"><?= e($n['name']) ?></span> <span class="small text-muted">· <?= fa_digits($n['projects_count']) ?> پروژه</span></div>
                <?php if ($canM): ?>
                    <button class="btn btn-sm btn-soft btn-icon" title="زیردسته" data-bs-toggle="modal" data-bs-target="#catModal" data-title="زیردسته برای «<?= e($n['name']) ?>»" data-fill='<?= json_attr(['parent_id' => $n['id'], 'color' => $n['color'], 'icon' => $n['icon']]) ?>'><i class="bi bi-diagram-2"></i></button>
                    <button class="btn btn-sm btn-soft-primary btn-icon" data-bs-toggle="modal" data-bs-target="#catModal" data-title="ویرایش دسته" data-fill='<?= json_attr(array_diff_key($n, ['children' => 1])) ?>'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-soft-danger btn-icon" data-confirm-url="<?= url('categories/delete') ?>" data-post='<?= json_attr(['id' => $n['id']]) ?>' data-confirm="دسته «<?= e($n['name']) ?>»<?= $n['children'] ? ' و همه زیردسته‌هایش' : '' ?> حذف شود؟ (پروژه‌ها حذف نمی‌شوند)"><i class="bi bi-trash"></i></button>
                <?php endif; ?>
            </div>
            <?php if ($n['children']): ?><div class="cat-children"><?php $render($n['children']); ?></div><?php endif; ?>
        </div>
    <?php endforeach;
}; ?>
<div class="row justify-content-center"><div class="col-xl-8">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>درخت دسته‌بندی پروژه‌ها <span class="small text-muted">— هر پروژه می‌تواند چند دسته داشته باشد</span></span>
        <?php if ($canM): ?><button class="btn btn-sm btn-grad" data-bs-toggle="modal" data-bs-target="#catModal" data-title="دسته جدید" data-fill='<?= json_attr(['color' => '#3b82f6', 'icon' => 'bi-tag']) ?>'><i class="bi bi-plus-lg"></i> دسته جدید</button><?php endif; ?>
    </div>
    <div class="card-body cat-tree"><?php $render($tree); ?><?php if (!$tree): ?><div class="empty-state"><i class="bi bi-tags"></i>دسته‌ای تعریف نشده</div><?php endif; ?></div>
</div>
</div></div>

<?php if ($canM): ?>
<?= modal_start('catModal', 'دسته‌بندی', 'bi-tags', url('categories/save'), '', ['id' => '']) ?>
    <div class="row g-3">
        <div class="col-12"><label class="form-label">نام دسته <span class="req">*</span></label><input type="text" name="name" class="form-control" required></div>
        <div class="col-md-8"><label class="form-label">دسته والد</label>
            <select name="parent_id" class="form-select"><option value="">— دسته اصلی —</option><?php foreach ($flat as $c): ?><option value="<?= $c['id'] ?>"><?= str_repeat('— ', $c['depth']) . e($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-4"><label class="form-label">ترتیب</label><input type="number" name="sort_order" class="form-control" value="0"></div>
        <div class="col-md-4"><label class="form-label">رنگ</label><input type="color" name="color" class="form-control form-control-color w-100" value="#3b82f6"></div>
        <div class="col-md-8"><label class="form-label">آیکون</label>
            <select name="icon" class="form-select"><?php foreach ($icons as $i): ?><option value="<?= $i ?>"><?= $i ?></option><?php endforeach; ?></select>
            <div class="mt-2 fs-4" data-icon-preview><i class="bi bi-tag"></i></div></div>
    </div>
<?= modal_end() ?>
<script>
document.addEventListener('change', e => { if (e.target.matches('#catModal [name=icon]')) document.querySelector('[data-icon-preview]').innerHTML = '<i class="bi ' + e.target.value + '"></i>'; });
</script>
<?php endif; ?>
