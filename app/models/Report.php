<?php
namespace App\Models;

use App\Core\DB;
use App\Core\Jalali;

defined('APP_ROOT') or exit('No direct access');

class Report
{
    /** آمار ماهانه (شمسی) بین دو تاریخ میلادی */
    public static function monthly(string $from, string $to): array
    {
        [$fy, $fm] = Jalali::yearMonth($from);
        [$ty, $tm] = Jalali::yearMonth($to);
        $months = [];
        for ($y = $fy, $m = $fm; $y < $ty || ($y === $ty && $m <= $tm); $m++) {
            if ($m > 12) {
                $m = 1;
                $y++;
                if ($y > $ty || ($y === $ty && $m > $tm)) {
                    break;
                }
            }
            $months[sprintf('%04d-%02d', $y, $m)] = ['label' => Jalali::monthName($m) . ' ' . $y, 'income' => 0, 'expense' => 0, 'profit' => 0, 'contracts' => 0];
            if (count($months) > 60) {
                break;
            }
        }
        $add = function (array $rows, string $dateKey, string $field) use (&$months) {
            foreach ($rows as $r) {
                [$y, $m] = Jalali::yearMonth($r[$dateKey]);
                $k = sprintf('%04d-%02d', $y, $m);
                if (isset($months[$k])) {
                    $months[$k][$field] += (int)$r['amount'];
                }
            }
        };
        $add(DB::all('SELECT paid_date, amount FROM receipts WHERE paid_date BETWEEN ? AND ?', [$from, $to]), 'paid_date', 'income');
        $add(DB::all('SELECT paid_date, amount FROM support_renewals WHERE is_paid = 1 AND paid_date BETWEEN ? AND ?', [$from, $to]), 'paid_date', 'income');
        $add(DB::all('SELECT expense_date, amount FROM expenses WHERE expense_date BETWEEN ? AND ?', [$from, $to]), 'expense_date', 'expense');
        $add(DB::all("SELECT DATE(created_at) AS d, total_amount AS amount FROM projects WHERE status <> 'canceled' AND DATE(created_at) BETWEEN ? AND ?", [$from, $to]), 'd', 'contracts');
        foreach ($months as &$m) {
            $m['profit'] = $m['income'] - $m['expense'];
        }
        return $months;
    }

    /** ۱۲ ماه شمسی اخیر */
    public static function last12(): array
    {
        [$jy, $jm] = Jalali::yearMonth(today());
        $sy = $jy;
        $sm = $jm - 11;
        if ($sm < 1) {
            $sm += 12;
            $sy--;
        }
        [$from] = Jalali::monthRange($sy, $sm);
        [, $to] = Jalali::monthRange($jy, $jm);
        return self::monthly($from, $to);
    }

    /** گزارش به تفکیک پروژه در بازه */
    public static function byProject(string $from, string $to, int $categoryId = 0, int $customerId = 0): array
    {
        $where = "p.status <> 'canceled'";
        $params = [$from, $to, $from, $to, $from, $to];
        if ($categoryId) {
            $where .= ' AND EXISTS (SELECT 1 FROM project_categories pc JOIN categories cc ON cc.id = pc.category_id WHERE pc.project_id = p.id AND (cc.id = ? OR cc.parent_id = ?))';
            array_push($params, $categoryId, $categoryId);
        }
        if ($customerId) {
            $where .= ' AND p.customer_id = ?';
            $params[] = $customerId;
        }
        $rows = DB::all("SELECT p.id, p.title, p.total_amount, p.payment_type, p.status, CONCAT(c.first_name,' ',c.last_name) AS customer,
                (SELECT COALESCE(SUM(r.amount),0) FROM receipts r WHERE r.project_id = p.id AND r.paid_date BETWEEN ? AND ?) AS income,
                (SELECT COALESCE(SUM(s.amount),0) FROM support_renewals s WHERE s.project_id = p.id AND s.is_paid = 1 AND s.paid_date BETWEEN ? AND ?) AS renewals,
                (SELECT COALESCE(SUM(e.amount),0) FROM expenses e WHERE e.project_id = p.id AND e.expense_date BETWEEN ? AND ?) AS expense,
                (SELECT COALESCE(SUM(pa.amount - pa.paid_amount),0) FROM payments pa WHERE pa.project_id = p.id) AS receivable
            FROM projects p JOIN customers c ON c.id = p.customer_id WHERE $where ORDER BY p.id DESC", $params);
        foreach ($rows as &$r) {
            $r['income'] = (int)$r['income'] + (int)$r['renewals'];
            $r['profit'] = $r['income'] - (int)$r['expense'];
        }
        return array_values(array_filter($rows, fn($r) => $r['income'] || $r['expense'] || $r['receivable']));
    }

    /** درآمد به تفکیک دسته اصلی */
    public static function byCategory(string $from, string $to): array
    {
        return DB::all("SELECT COALESCE(par.id, c.id) AS id, COALESCE(par.name, c.name) AS name, COALESCE(par.color, c.color) AS color,
                               SUM(r.amount) AS income
                        FROM receipts r JOIN project_categories pc ON pc.project_id = r.project_id
                        JOIN categories c ON c.id = pc.category_id LEFT JOIN categories par ON par.id = c.parent_id
                        WHERE r.paid_date BETWEEN ? AND ?
                        GROUP BY COALESCE(par.id, c.id), COALESCE(par.name, c.name), COALESCE(par.color, c.color) ORDER BY income DESC", [$from, $to]);
    }
}
