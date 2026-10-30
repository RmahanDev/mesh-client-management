<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\View;
use App\Core\Xlsx;
use App\Models\Category;
use App\Models\Report;

defined('APP_ROOT') or exit('No direct access');

/** گزارش‌ها + خروجی Excel و PDF (چاپ) */
class ReportsController extends Controller
{
    private function params(): array
    {
        [$jy] = Jalali::yearMonth(today());
        [$defFrom] = Jalali::monthRange($jy, 1);
        $from = input_date('from') ?? $defFrom;
        $to = input_date('to') ?? today();
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $cat = input_int('cat');
        $customer = input_int('customer');
        $monthly = Report::monthly($from, $to);
        $projects = Report::byProject($from, $to, $cat, $customer);
        $sum = ['income' => 0, 'expense' => 0, 'profit' => 0, 'contracts' => 0];
        foreach ($monthly as $m) {
            foreach ($sum as $k => $_) {
                $sum[$k] += $m[$k];
            }
        }
        if ($cat || $customer) {
            $sum['income'] = array_sum(array_column($projects, 'income'));
            $sum['expense'] = array_sum(array_column($projects, 'expense'));
            $sum['profit'] = $sum['income'] - $sum['expense'];
        }
        $sum['receivable'] = array_sum(array_column($projects, 'receivable'));
        return compact('from', 'to', 'cat', 'customer', 'monthly', 'projects', 'sum');
    }

    public function index(): void
    {
        $this->authorize('reports');
        $p = $this->params();
        $this->render('reports/index', $p + [
            'title' => 'گزارش‌ها', 'charts' => true,
            'byCategory' => Report::byCategory($p['from'], $p['to']),
            'categories' => Category::flat(),
            'customers' => DB::all('SELECT id, first_name, last_name FROM customers ORDER BY first_name'),
        ]);
    }

    public function excel(): void
    {
        $this->authorize('reports');
        $p = $this->params();
        $monthRows = [];
        foreach ($p['monthly'] as $m) {
            $monthRows[] = [$m['label'], $m['contracts'], $m['income'], $m['expense'], $m['profit']];
        }
        $projRows = [];
        foreach ($p['projects'] as $r) {
            $projRows[] = [$r['title'], $r['customer'], payment_type_label($r['payment_type']), project_status_label($r['status']), (int)$r['total_amount'], $r['income'], (int)$r['expense'], $r['profit'], (int)$r['receivable']];
        }
        $rec = [];
        foreach (DB::all("SELECT r.*, pa.title AS pt, pr.title AS project, CONCAT(c.first_name,' ',c.last_name) AS customer FROM receipts r JOIN payments pa ON pa.id=r.payment_id JOIN projects pr ON pr.id=r.project_id JOIN customers c ON c.id=pr.customer_id WHERE r.paid_date BETWEEN ? AND ? ORDER BY r.paid_date", [$p['from'], $p['to']]) as $r) {
            $rec[] = [Jalali::format($r['paid_date']), $r['customer'], $r['project'], $r['pt'], (int)$r['amount'], payment_method_label($r['method']), (string)$r['ref_no']];
        }
        $exp = [];
        foreach (DB::all('SELECT e.*, pr.title AS project FROM expenses e JOIN projects pr ON pr.id=e.project_id WHERE e.expense_date BETWEEN ? AND ? ORDER BY e.expense_date', [$p['from'], $p['to']]) as $x) {
            $exp[] = [Jalali::format($x['expense_date']), $x['project'], $x['title'], (int)$x['amount'], (string)$x['notes']];
        }
        $rem = [];
        foreach (DB::all("SELECT pa.*, pr.title AS project, CONCAT(c.first_name,' ',c.last_name) AS customer FROM payments pa JOIN projects pr ON pr.id=pa.project_id JOIN customers c ON c.id=pr.customer_id WHERE pa.status<>'paid' AND pr.status<>'canceled' ORDER BY pa.due_date IS NULL, pa.due_date") as $x) {
            $rem[] = [$x['due_date'] ? Jalali::format($x['due_date']) : '-', $x['customer'], $x['project'], $x['title'], (int)$x['amount'], (int)$x['paid_amount'], (int)$x['amount'] - (int)$x['paid_amount']];
        }
        Xlsx::download('report-' . str_replace('/', '-', Jalali::format($p['from'])) . '_' . str_replace('/', '-', Jalali::format($p['to'])), [
            'خلاصه ماهانه' => [['ماه', 'قرارداد جدید (تومان)', 'دریافتی (تومان)', 'هزینه (تومان)', 'سود خالص (تومان)'], $monthRows],
            'پروژه‌ها' => [['پروژه', 'مشتری', 'نوع پرداخت', 'وضعیت', 'مبلغ کل', 'دریافتی بازه', 'هزینه بازه', 'سود بازه', 'مانده مطالبات'], $projRows],
            'دریافتی‌ها' => [['تاریخ', 'مشتری', 'پروژه', 'بابت', 'مبلغ', 'روش', 'شماره پیگیری'], $rec],
            'هزینه‌ها' => [['تاریخ', 'پروژه', 'عنوان', 'مبلغ', 'توضیحات'], $exp],
            'مطالبات' => [['سررسید', 'مشتری', 'پروژه', 'عنوان', 'مبلغ', 'دریافت‌شده', 'مانده'], $rem],
        ]);
    }

    /** نسخه چاپی؛ از پنجره چاپ مرورگر «Save as PDF» را انتخاب کنید */
    public function pdf(): void
    {
        $this->authorize('reports');
        $p = $this->params();
        View::render('reports/print', $p + ['title' => 'گزارش مالی'], 'layouts/auth');
    }
}
