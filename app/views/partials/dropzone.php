<?php defined('APP_ROOT') or exit;
$dzName = $dzName ?? 'files';
$maxMb = (int)\App\Core\Settings::get('upload_max_mb', config('upload.max_size_mb')); ?>
<label class="dropzone d-block">
    <input type="file" name="<?= e($dzName) ?>[]" multiple accept=".<?= implode(',.', config('upload.allowed_ext')) ?>">
    <i class="bi bi-cloud-arrow-up fs-3 d-block"></i>
    <span class="small"><?= e($dzLabel ?? 'فایل‌ها را اینجا رها کنید یا کلیک کنید (چند فایل)') ?></span>
    <span class="d-block small opacity-75">حداکثر <?= fa_digits($maxMb) ?> مگابایت — تصویر، PDF، Word، Excel، ZIP</span>
    <div class="files"></div>
</label>
<?php unset($dzLabel); ?>
