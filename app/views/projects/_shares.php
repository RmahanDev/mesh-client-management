<?php defined('APP_ROOT') or exit;
$shareSum = array_sum(array_column($shares, 'percent'));
$shareRow = function (?array $s = null) use ($shareAdmins, $canFinM) { ob_start(); ?>
    <div class="row g-2 mb-2 align-items-center repeater-row" data-share-row>
        <div class="col-12 col-md-5"><select name="share_admin[]" class="form-select form-select-sm" <?= $canFinM ? '' : 'disabled' ?> required>
            <option value="">انتخاب ادمین...</option>
            <?php foreach ($shareAdmins as $a): ?><option value="<?= $a['id'] ?>" <?= (int)($s['admin_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?><?= $a['is_active'] ? '' : ' (غیرفعال)' ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-4 col-md-2"><input type="text" name="share_percent[]" class="form-control form-control-sm ltr" inputmode="decimal" placeholder="درصد" value="<?= $s ? e(rtrim(rtrim($s['percent'], '0'), '.')) : '' ?>" <?= $canFinM ? '' : 'readonly' ?> required></div>
        <div class="col-6 col-md-4 small"><b data-share-amount>۰</b> تومان<div class="text-muted">با دریافت کامل: <span data-share-expected>۰</span></div></div>
        <?php if ($canFinM): ?><div class="col-2 col-md-1"><button type="button" class="btn btn-sm btn-soft-danger btn-icon" data-repeater-remove><i class="bi bi-x"></i></button></div><?php endif; ?>
    </div>
<?php return ob_get_clean(); }; ?>
<div class="card">
    <div class="card-header">سهم‌بندی سود پروژه بین ادمین‌ها</div>
    <div class="card-body">
        <form class="ajax-form" method="post" action="<?= url('projects/shares') ?>" id="sharesForm" data-base="<?= max(0, $f['profit']) ?>" data-expected="<?= max(0, $expectedProfit) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <div class="alert alert-danger form-alert d-none"></div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="shares_enabled" value="1" id="sharesOn" data-toggle-box="#sharesBox" <?= $p['shares_enabled'] ? 'checked' : '' ?> <?= $canFinM ? '' : 'disabled' ?>>
                <label class="form-check-label" for="sharesOn">سهم‌بندی این پروژه فعال باشد</label>
            </div>
            <div id="sharesBox" class="<?= $p['shares_enabled'] ? '' : 'd-none' ?>">
                <div class="kv"><span class="k">سود خالص تا امروز (دریافتی منهای هزینه‌ها و ابزار)</span><span class="v"><?= money($f['profit']) ?></span></div>
                <div class="kv mb-3"><span class="k">سود خالص با دریافت کامل قرارداد</span><span class="v"><?= money($expectedProfit) ?></span></div>
                <div id="shareList" data-template="shareTpl"><?php foreach ($shares as $s) echo $shareRow($s); ?></div>
                <?php if ($canFinM): ?><button type="button" class="btn btn-sm btn-soft-primary mb-3" data-repeater-add="#shareList"><i class="bi bi-plus-lg"></i> افزودن نفر</button><?php endif; ?>
                <div class="small">جمع درصدها: <b data-share-sum><?= percent_label($shareSum) ?></b> <span class="text-muted">· باقی‌مانده: <span data-share-left><?= percent_label(100 - $shareSum) ?></span></span></div>
            </div>
            <?php if ($canFinM): ?><div class="text-start mt-3"><button type="submit" class="btn btn-grad"><i class="bi bi-check2 me-1"></i>ذخیره سهم‌بندی</button></div><?php endif; ?>
        </form>
    </div>
</div>
<template id="shareTpl"><?= $shareRow() ?></template>
