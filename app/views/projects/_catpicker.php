<?php defined('APP_ROOT') or exit; ?>
<div class="cat-picker section-box p-2">
    <input type="search" class="form-control form-control-sm mb-2" placeholder="جستجوی دسته..." data-cat-search>
    <div style="max-height:170px;overflow:auto" class="px-1">
        <?php foreach ($categories as $cat): ?>
            <div class="form-check" style="margin-right:<?= $cat['depth'] * 1.2 ?>rem">
                <input class="form-check-input" type="checkbox" name="categories[]" value="<?= $cat['id'] ?>" id="cat<?= $cpId . '_' . $cat['id'] ?>">
                <label class="form-check-label small" for="cat<?= $cpId . '_' . $cat['id'] ?>"><i class="bi <?= e($cat['icon']) ?> me-1" style="color:<?= e($cat['color']) ?>"></i><?= e($cat['name']) ?></label>
            </div>
        <?php endforeach; ?>
        <?php if (!$categories): ?><div class="small text-muted">دسته‌ای تعریف نشده</div><?php endif; ?>
    </div>
</div>
