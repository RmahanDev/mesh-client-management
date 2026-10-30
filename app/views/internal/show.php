<?php defined('APP_ROOT') or exit;
use App\Core\Auth;
$isSuper = Auth::isSuper();
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3"><div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                <div>
                    <span class="badge bs-<?= internal_status_color($t['status']) ?>"><?= internal_status_label($t['status']) ?></span>
                    <span class="badge bs-<?= priority_color($t['priority']) ?>">اولویت <?= priority_label($t['priority']) ?></span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#itAssignModal" data-fill='<?= json_attr(['id' => $t['id'], 'to_department_id' => $t['to_department_id'], 'assigned_to' => $t['assigned_to']]) ?>'><i class="bi bi-arrow-left-right"></i> ارجاع</button>
                    <?php if ($isSuper): ?><button class="btn btn-sm btn-soft-danger" data-confirm-url="<?= url('internal/delete') ?>" data-post='<?= json_attr(['id' => $t['id']]) ?>' data-confirm="این تیکت حذف شود؟"><i class="bi bi-trash"></i></button><?php endif; ?>
                </div>
            </div>
            <h2 class="h5 fw-bold"><?= e($t['subject']) ?></h2>
            <div class="small text-muted mb-3">از <?= e($t['creator_name'] ?? '—') ?><?= $t['from_dept_name'] ? ' (' . e($t['from_dept_name']) . ')' : '' ?> · <?= jdate($t['created_at'], true) ?></div>
            <div><?= $t['description'] ? nl2br(e($t['description'])) : '<span class="text-muted">بدون شرح</span>' ?></div>
        </div></div>

        <h6 class="fw-bold mb-3">گفتگو و پیگیری</h6>
        <?php foreach ($replies as $r): ?>
            <?php if ($r['kind'] === 'system'): ?>
                <div class="text-center text-muted small my-2"><i class="bi bi-info-circle me-1"></i><?= e($r['body']) ?> · <?= jdate($r['created_at'], true) ?></div>
            <?php else: ?>
                <div class="timeline-item"><div class="reply-box">
                    <div class="small text-muted mb-1"><b class="text-body"><?= e($r['name'] ?? '—') ?></b><?= $r['dept_name'] ? ' (' . e($r['dept_name']) . ')' : '' ?> · <?= jdate($r['created_at'], true) ?></div>
                    <?= nl2br(e($r['body'])) ?>
                </div></div>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$replies): ?><div class="text-muted small mb-3">هنوز پاسخی ثبت نشده</div><?php endif; ?>
        <button class="btn btn-grad mt-2" data-bs-toggle="modal" data-bs-target="#itReplyModal" data-fill='<?= json_attr(['ticket_id' => $t['id'], 'status' => $t['status'] === 'open' && (int)$t['assigned_to'] === Auth::id() ? 'in_progress' : $t['status']]) ?>'><i class="bi bi-reply me-1"></i>ثبت پاسخ / پیگیری</button>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3"><div class="card-body">
            <div class="kv"><span class="k">دپارتمان مقصد</span><span class="v"><?= e($t['to_dept_name'] ?? '—') ?></span></div>
            <div class="kv"><span class="k">ادمین مسئول</span><span class="v"><?= $t['assignee_name'] ? e($t['assignee_name']) : '<span class="text-muted">مشخص نشده</span>' ?></span></div>
            <div class="kv"><span class="k">ایجادکننده</span><span class="v"><?= e($t['creator_name'] ?? '—') ?></span></div>
            <?php if ($t['project_title']): ?><div class="kv"><span class="k">پروژه</span><span class="v"><a href="<?= url('projects/show', ['id' => $t['project_id']]) ?>"><?= e($t['project_title']) ?></a></span></div><?php endif; ?>
            <?php if ($t['closed_at']): ?><div class="kv"><span class="k">پایان</span><span class="v"><?= jdate($t['closed_at'], true) ?></span></div><?php endif; ?>
        </div></div>
        <div class="card"><div class="card-body">
            <?php $attEntity = 'internal_ticket'; $attId = (int)$t['id']; $attManage = true; require APP_ROOT . '/app/views/partials/attachments.php'; ?>
        </div></div>
    </div>
</div>
<?= modal_start('itReplyModal', 'ثبت پاسخ / پیگیری', 'bi-reply', url('internal/reply'), 'modal-lg', ['ticket_id' => $t['id']]) ?>
    <div class="mb-3"><label class="form-label">متن <span class="req">*</span></label><textarea name="body" class="form-control" rows="5" required></textarea></div>
    <div class="row g-3">
        <div class="col-md-5"><label class="form-label">تغییر وضعیت به</label><select name="status" class="form-select"><?php foreach (['open', 'in_progress', 'done', 'closed'] as $s): ?><option value="<?= $s ?>"><?= internal_status_label($s) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-7"><label class="form-label">فایل پیوست</label><?php $dzName = 'files'; require APP_ROOT . '/app/views/partials/dropzone.php'; ?></div>
    </div>
<?= modal_end('ثبت') ?>
<?php require_once __DIR__ . '/_modals.php'; ?>
<?= modal_start('itAssignModal', 'ارجاع تیکت', 'bi-arrow-left-right', url('internal/assign'), '', ['id' => $t['id']]) ?>
    <div class="row g-3"><?php it_target_fields($departments, $admins, false); ?></div>
<?= modal_end('ارجاع و اعلان') ?>
