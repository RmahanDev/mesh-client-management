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

    /** سهم‌های ثبت‌شده یک پروژه همراه با مبلغ محاسبه‌شده از سود: [rows, base, totalPercent] */
    public static function shares(int $projectId, int $profit): array
    {
        $base = max(0, $profit);
        $rows = DB::all('SELECT s.*, a.name, a.is_active FROM project_shares s JOIN admins a ON a.id = s.admin_id WHERE s.project_id = ? ORDER BY s.percent DESC, s.id', [$projectId]);
        $total = 0.0;
        foreach ($rows as &$r) {
            $r['amount'] = (int)floor($base * (float)$r['percent'] / 100);
            $total += (float)$r['percent'];
        }
        return ['rows' => $rows, 'base' => $base, 'percent' => $total];
    }

    /** سهم‌های یک ادمین از همه پروژه‌های دارای سهم‌بندی فعال */
    public static function sharesOf(int $adminId): array
    {
        $rows = DB::all("SELECT s.percent, p.id AS project_id, p.title, p.status, CONCAT(c.first_name,' ',c.last_name) AS customer
                         FROM project_shares s JOIN projects p ON p.id = s.project_id AND p.profit_sharing = 1
                         JOIN customers c ON c.id = p.customer_id WHERE s.admin_id = ? ORDER BY p.id DESC", [$adminId]);
        $fin = self::finance(array_map('intval', array_column($rows, 'project_id')));
        foreach ($rows as &$r) {
            $f = $fin[$r['project_id']];
            $r['income'] = $f['income'];
            $r['expenses'] = $f['expenses'];
            $r['profit'] = $f['profit'];
            $r['amount'] = (int)floor(max(0, $f['profit']) * (float)$r['percent'] / 100);
        }
        return $rows;
    }
}
