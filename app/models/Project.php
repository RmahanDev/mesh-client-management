<?php
namespace App\Models;

use App\Core\DB;
use App\Core\Jalali;

defined('APP_ROOT') or exit('No direct access');

class Project
{
    /** خلاصه مالی یک یا چند پروژه: [project_id => [...]] */
    public static function finance(array $ids): array
    {
        $out = [];
        if (!$ids) {
            return $out;
        }
        foreach ($ids as $id) {
            $out[$id] = ['scheduled' => 0, 'paid' => 0, 'expenses' => 0, 'renewals' => 0, 'renewals_paid' => 0, 'overdue' => 0, 'next_due' => null];
        }
        $in = DB::in($ids);
        foreach (DB::all("SELECT project_id, SUM(amount) s, SUM(paid_amount) p,
                            SUM(CASE WHEN status<>'paid' AND due_date < CURDATE() THEN amount-paid_amount ELSE 0 END) o,
                            MIN(CASE WHEN status<>'paid' AND due_date IS NOT NULL THEN due_date END) nd
                          FROM payments WHERE project_id IN ($in) GROUP BY project_id", $ids) as $r) {
            $out[$r['project_id']]['scheduled'] = (int)$r['s'];
            $out[$r['project_id']]['paid'] = (int)$r['p'];
            $out[$r['project_id']]['overdue'] = (int)$r['o'];
            $out[$r['project_id']]['next_due'] = $r['nd'];
        }
        foreach (DB::all("SELECT project_id, SUM(amount) s FROM expenses WHERE project_id IN ($in) GROUP BY project_id", $ids) as $r) {
            $out[$r['project_id']]['expenses'] = (int)$r['s'];
        }
        foreach (DB::all("SELECT project_id, SUM(amount) s, SUM(CASE WHEN is_paid=1 THEN amount ELSE 0 END) p FROM support_renewals WHERE project_id IN ($in) GROUP BY project_id", $ids) as $r) {
            $out[$r['project_id']]['renewals'] = (int)$r['s'];
            $out[$r['project_id']]['renewals_paid'] = (int)$r['p'];
        }
        foreach ($out as &$f) {
            $f['income'] = $f['paid'] + $f['renewals_paid'];
            $f['profit'] = $f['income'] - $f['expenses'];
        }
        return $out;
    }

    /** سهم از سود؛ در صورت زیان صفر است */
    public static function shareAmount(int $profit, $percent): int
    {
        return intdiv(max(0, $profit) * (int)round((float)$percent * 100), 10000);
    }

    /** سود خالص در صورت دریافت کامل قرارداد */
    public static function expectedProfit(int $totalAmount, array $finance): int
    {
        return $totalAmount + $finance['renewals_paid'] - $finance['expenses'];
    }

    /** سهم‌های تعریف‌شده یک ادمین در پروژه‌هایی که سهم‌بندی‌شان فعال است */
    public static function myShares(int $adminId): array
    {
        $rows = DB::all("SELECT s.percent, p.id, p.title, p.status, p.total_amount, CONCAT(c.first_name,' ',c.last_name) AS customer
                         FROM project_shares s JOIN projects p ON p.id = s.project_id JOIN customers c ON c.id = p.customer_id
                         WHERE s.admin_id = ? AND p.shares_enabled = 1 ORDER BY p.id DESC", [$adminId]);
        $finance = self::finance(array_column($rows, 'id'));
        $total = $expected = 0;
        foreach ($rows as &$r) {
            $f = $finance[$r['id']];
            $r['profit'] = $f['profit'];
            $r['amount'] = self::shareAmount($f['profit'], $r['percent']);
            $r['expected_amount'] = self::shareAmount(self::expectedProfit((int)$r['total_amount'], $f), $r['percent']);
            $total += $r['amount'];
            $expected += $r['expected_amount'];
        }
        unset($r);
        return ['rows' => $rows, 'total' => $total, 'expected' => $expected];
    }

    public static function categories(array $ids): array
    {
        $out = array_fill_keys($ids, []);
        if (!$ids) {
            return $out;
        }
        foreach (DB::all('SELECT pc.project_id, c.id, c.name, c.color, c.icon, c.parent_id, p.name AS parent_name
                          FROM project_categories pc JOIN categories c ON c.id = pc.category_id
                          LEFT JOIN categories p ON p.id = c.parent_id
                          WHERE pc.project_id IN (' . DB::in($ids) . ') ORDER BY c.sort_order', $ids) as $r) {
            $out[$r['project_id']][] = $r;
        }
        return $out;
    }

    /** به‌روزرسانی وضعیت یک ردیف پرداخت بر اساس دریافتی‌ها */
    public static function recalcPayment(int $paymentId): void
    {
        $p = DB::one('SELECT id, amount FROM payments WHERE id = ?', [$paymentId]);
        if (!$p) {
            return;
        }
        $r = DB::one('SELECT COALESCE(SUM(amount),0) s, MAX(paid_date) d FROM receipts WHERE payment_id = ?', [$paymentId]);
        $paid = (int)$r['s'];
        $status = $paid <= 0 ? 'pending' : ($paid >= (int)$p['amount'] ? 'paid' : 'partial');
        DB::update('payments', ['paid_amount' => $paid, 'paid_date' => $r['d'], 'status' => $status], 'id = ?', [$paymentId]);
    }

    /** محاسبه پایان پشتیبانی = تاریخ تحویل + ماه‌ها + ماه‌های تمدید */
    public static function recalcSupport(int $projectId): void
    {
        $p = DB::one('SELECT delivery_date, support_months FROM projects WHERE id = ?', [$projectId]);
        if (!$p || !$p['delivery_date']) {
            DB::update('projects', ['support_start' => null, 'support_end' => null], 'id = ?', [$projectId]);
            return;
        }
        $end = Jalali::addMonths($p['delivery_date'], (int)$p['support_months']);
        $lastRenewal = DB::value('SELECT MAX(end_date) FROM support_renewals WHERE project_id = ?', [$projectId]);
        if ($lastRenewal && $lastRenewal > $end) {
            $end = $lastRenewal;
        }
        DB::update('projects', ['support_start' => $p['delivery_date'], 'support_end' => $end], 'id = ?', [$projectId]);
    }

    /** لیست پروژه‌ها برای select */
    public static function options(): array
    {
        return DB::all("SELECT p.id, p.title, CONCAT(c.first_name,' ',c.last_name) AS customer
                        FROM projects p JOIN customers c ON c.id = p.customer_id ORDER BY p.id DESC");
    }
}
