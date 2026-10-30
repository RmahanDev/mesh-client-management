<?php defined('APP_ROOT') or exit; ?>
<style>
    body { background: #fff !important; color: #0f172a; }
    .print-wrap { max-width: 1000px; margin: 0 auto; padding: 24px; }
    .print-wrap table { width: 100%; border-collapse: collapse; font-size: 12px; margin-bottom: 18px; }
    .print-wrap th, .print-wrap td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; }
    .print-wrap th { background: #e0ecff; }
    .print-head { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #2563eb; padding-bottom: 12px; margin-bottom: 18px; }
    .sum-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 18px; }
    .sum-grid div { border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px; text-align: center; font-size: 12px; }
    .sum-grid b { display: block; font-size: 15px; margin-top: 4px; }
    h3 { font-size: 15px; margin: 14px 0 8px; color: #1d4ed8; }
</style>
<div class="print-wrap" data-bs-theme="light">
    <div class="no-print" style="margin-bottom:16px;text-align:left">
        <button onclick="window.print()" class="btn btn-primary btn-sm">چاپ / ذخیره PDF</button>
    </div>
    <div class="print-head">
        <div><h2 style="margin:0;font-size:20px;font-weight:800"><?= e(\App\Core\Settings::get('company_name')) ?></h2><div style="font-size:12px;color:#475569">گزارش مالی از <?= jdate($from) ?> تا <?= jdate($to) ?></div></div>
        <div style="font-size:12px;color:#475569">تاریخ تهیه: <?= jdate(now(), true) ?></div>
    </div>
    <div class="sum-grid">
        <div>دریافتی<b><?= money_plain($sum['income']) ?></b></div>
        <div>هزینه‌ها<b><?= money_plain($sum['expense']) ?></b></div>
        <div>سود خالص<b><?= money_plain($sum['profit']) ?></b></div>
        <div>قرارداد جدید<b><?= money_plain($sum['contracts']) ?></b></div>
        <div>مانده مطالبات<b><?= money_plain($sum['receivable']) ?></b></div>
    </div>
    <h3>خلاصه ماهانه</h3>
    <table><thead><tr><th>ماه</th><th>قرارداد جدید</th><th>دریافتی</th><th>هزینه</th><th>سود خالص</th></tr></thead><tbody>
        <?php foreach ($monthly as $m): ?><tr><td><?= fa_digits($m['label']) ?></td><td><?= num($m['contracts']) ?></td><td><?= num($m['income']) ?></td><td><?= num($m['expense']) ?></td><td><b><?= num($m['profit']) ?></b></td></tr><?php endforeach; ?>
    </tbody></table>
    <h3>به تفکیک پروژه</h3>
    <table><thead><tr><th>پروژه</th><th>مشتری</th><th>مبلغ کل</th><th>دریافتی</th><th>هزینه</th><th>سود</th><th>مانده</th></tr></thead><tbody>
        <?php foreach ($projects as $r): ?><tr><td><?= e($r['title']) ?></td><td><?= e($r['customer']) ?></td><td><?= num($r['total_amount']) ?></td><td><?= num($r['income']) ?></td><td><?= num($r['expense']) ?></td><td><b><?= num($r['profit']) ?></b></td><td><?= num($r['receivable']) ?></td></tr><?php endforeach; ?>
        <?php if (!$projects): ?><tr><td colspan="7" style="text-align:center">داده‌ای نیست</td></tr><?php endif; ?>
    </tbody></table>
    <div style="font-size:11px;color:#64748b">مبالغ به تومان است.</div>
</div>
<script>document.documentElement.setAttribute('data-bs-theme', 'light'); window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
