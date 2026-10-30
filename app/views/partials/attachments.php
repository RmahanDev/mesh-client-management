<?php
defined('APP_ROOT') or exit;
/** @var string $attEntity @var int $attId @var bool $attManage @var array|null $attList */
$attList = $attList ?? \App\Core\Upload::list($attEntity, $attId);
$attCompact = $attCompact ?? false;
?>
<div class="<?= $attCompact ? '' : 'mt-2' ?>">
    <?php if (!$attCompact || $attManage): ?>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="small text-muted"><i class="bi bi-paperclip"></i> پیوست‌ها (<?= fa_digits(count($attList)) ?>)</span>
        <?php if ($attManage): ?>
            <button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#uploadModal"
                    data-fill='<?= json_attr(['entity' => $attEntity, 'entity_id' => $attId]) ?>' data-title="<?= e($attTitle ?? 'افزودن پیوست') ?>">
                <i class="bi bi-plus-lg"></i> پیوست
            </button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($attList): ?>
        <div class="att-grid">
            <?php foreach ($attList as $a):
                $src = url('files/view', ['id' => $a['id']]);
                $dl = url('files/view', ['id' => $a['id'], 'download' => 1]);
                $isImg = \App\Core\Upload::isImage($a['mime']);
                $icon = $a['mime'] === 'application/pdf' ? 'bi-file-earmark-pdf text-danger' : (str_contains($a['mime'], 'sheet') || str_contains($a['mime'], 'excel') ? 'bi-file-earmark-excel text-success' : (str_contains($a['mime'], 'word') ? 'bi-file-earmark-word text-primary' : 'bi-file-earmark'));
            ?>
                <div class="att-item">
                    <div class="att-thumb js-preview" data-src="<?= e($src) ?>" data-download="<?= e($dl) ?>" data-mime="<?= e($a['mime']) ?>" data-name="<?= e($a['title'] ?: $a['original_name']) ?>">
                        <?php if ($isImg): ?><img src="<?= e($src) ?>" alt="" loading="lazy"><?php else: ?><i class="bi <?= $icon ?>"></i><?php endif; ?>
                    </div>
                    <div class="att-meta">
                        <div class="n" title="<?= e($a['original_name']) ?>"><?= e($a['title'] ?: $a['original_name']) ?></div>
                        <div class="text-muted"><?= human_size((int)$a['size']) ?> · <?= jdate($a['created_at']) ?></div>
                    </div>
                    <?php if ($attManage): ?>
                        <button type="button" class="btn btn-sm btn-soft-danger btn-icon att-del" data-confirm-url="<?= url('files/delete') ?>" data-post='<?= json_attr(['id' => $a['id']]) ?>' data-confirm="این پیوست حذف شود؟"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!$attCompact): ?>
        <div class="small text-muted border rounded-3 p-3 text-center" style="border-style:dashed!important">پیوستی ثبت نشده</div>
    <?php endif; ?>
</div>
<?php unset($attList, $attTitle, $attCompact); ?>
