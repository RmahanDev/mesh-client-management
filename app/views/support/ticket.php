<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$canM = Auth::canManage('support');
$channels = ['phone' => 'تماس تلفنی', 'telegram' => 'تلگرام', 'whatsapp' => 'واتس‌اپ', 'email' => 'ایمیل', 'inperson' => 'حضوری', 'other' => 'سایر'];
$sd = days_until($t['support_end']);
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <span class="badge bs-<?= ticket_status_color($t['status']) ?>"><?= ticket_status_label($t['status']) ?></span>
                        <span class="badge bs-<?= priority_color($t['priority']) ?>">اولویت <?= priority_label($t['priority']) ?></span>
                        <?php if ($t['channel']): ?><span class="badge badge-soft"><?= $channels[$t['channel']] ?? e($t['channel']) ?></span><?php endif; ?>
                    </div>
                    <?php if ($canM): ?><div class="d-flex gap-2">
                        <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#ticketModal" data-title="ویرایش تیکت" data-fill='<?= json_attr($t) ?>'><i class="bi bi-pencil"></i> ویرایش</button>
                        <button class="btn btn-sm btn-soft-danger" data-confirm-url="<?= url('support/ticket-delete') ?>" data-post='<?= json_attr(['id' => $t['id']]) ?>' data-confirm="این تیکت حذف شود؟"><i class="bi bi-trash"></i></button>
                    </div><?php endif; ?>
                </div>
                <h2 class="h5 fw-bold"><?= e($t['subject']) ?></h2>
                <div class="small text-muted mb-3">ثبت توسط <?= e($t['creator'] ?? '—') ?> · <?= jdate($t['created_at'], true) ?></div>
                <div><?= $t['description'] ? nl2br(e($t['description'])) : '<span class="text-muted">بدون شرح</span>' ?></div>
            </div>
        </div>

        <h6 class="fw-bold mb-3">پاسخ‌ها و پیگیری‌ها</h6>
        <?php foreach ($replies as $r): ?>
            <div class="timeline-item">
                <div class="reply-box">
                    <div class="small text-muted mb-1"><b class="text-body"><?= e($r['name'] ?? '—') ?></b> · <?= jdate($r['created_at'], true) ?></div>
                    <?= nl2br(e($r['body'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$replies): ?><div class="text-muted small mb-3">هنوز پاسخی ثبت نشده</div><?php endif; ?>

        <?php if ($canM): ?>
            <button class="btn btn-grad mt-2" data-bs-toggle="modal" data-bs-target="#replyModal" data-fill='<?= json_attr(['status' => $t['status'] === 'open' ? 'in_progress' : $t['status']]) ?>'><i class="bi bi-reply me-1"></i>ثبت پاسخ / پیگیری</button>
        <?php endif; ?>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <div class="kv"><span class="k">مشتری</span><span class="v"><a href="<?= url('customers/show', ['id' => $t['cid']]) ?>"><?= e($t['customer']) ?></a></span></div>
            <div class="kv"><span class="k">پروژه</span><span class="v"><a href="<?= url('projects/show', ['id' => $t['project_id'], 'tab' => 'support']) ?>"><?= e($t['project_title']) ?></a></span></div>
            <div class="kv"><span class="k">وضعیت پشتیبانی</span><span class="v"><?= $sd === null ? '—' : ($sd < 0 ? '<span class="text-danger">تمام شده</span>' : '<span class="text-success">' . fa_digits($sd) . ' روز مانده</span>') ?></span></div>
            <?php if ($t['closed_at']): ?><div class="kv"><span class="k">بسته شده</span><span class="v"><?= jdate($t['closed_at'], true) ?></span></div><?php endif; ?>
        </div></div>
        <div class="card"><div class="card-body">
            <?php $attEntity = 'ticket'; $attId = (int)$t['id']; $attManage = $canM; require APP_ROOT . '/app/views/partials/attachments.php'; ?>
        </div></div>
    </div>
</div>
<?php if ($canM): $p = ['id' => $t['project_id']]; require __DIR__ . '/_modals.php'; ?>
<?= modal_start('replyModal', 'ثبت پاسخ / پیگیری', 'bi-reply', url('support/reply'), 'modal-lg', ['ticket_id' => $t['id']]) ?>
    <div class="mb-3"><label class="form-label">متن <span class="req">*</span></label><textarea name="body" class="form-control" rows="5" required placeholder="پاسخ داده‌شده به مشتری یا کاری که انجام شد..."></textarea></div>
    <div class="row g-3">
        <div class="col-md-5"><label class="form-label">تغییر وضعیت به</label><select name="status" class="form-select"><?php foreach (['open', 'in_progress', 'answered', 'closed'] as $s): ?><option value="<?= $s ?>"><?= ticket_status_label($s) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-7"><label class="form-label">فایل پیوست</label><?php $dzName = 'files'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end('ثبت') ?>
<?php endif; ?>
