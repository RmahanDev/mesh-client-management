<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Settings;
use App\Models\Report;

defined('APP_ROOT') or exit('No direct access');

class DashboardController extends Controller
{
    public function index(): void
    {
        $days = (int)Settings::get('reminder_days', 7);
        $d = ['title' => 'داشبورد', 'charts' => true, 'days' => $days];
        $d['counts'] = [
            'customers' => Auth::can('customers') ? (int)DB::value('SELECT COUNT(*) FROM customers') : null,
            'active' => Auth::can('projects') ? (int)DB::value("SELECT COUNT(*) FROM projects WHERE status IN ('active','pending')") : null,
            'tickets' => Auth::can('support') ? (int)DB::value("SELECT COUNT(*) FROM tickets WHERE status IN ('open','in_progress')") : null,
            'support' => Auth::can('support') ? (int)DB::value("SELECT COUNT(*) FROM projects WHERE support_end >= CURDATE() AND status <> 'canceled'") : null,
        ];
        if (Auth::can('finance')) {
            [$jy, $jm] = Jalali::yearMonth(today());
            [$ms, $me] = Jalali::monthRange($jy, $jm);
            $income = (int)DB::value('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE paid_date BETWEEN ? AND ?', [$ms, $me])
                + (int)DB::value('SELECT COALESCE(SUM(amount),0) FROM support_renewals WHERE is_paid=1 AND paid_date BETWEEN ? AND ?', [$ms, $me]);
            $expense = (int)DB::value('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?', [$ms, $me]);
            $d['month'] = ['name' => Jalali::monthName($jm) . ' ' . fa_digits($jy), 'income' => $income, 'expense' => $expense, 'profit' => $income - $expense];
            $d['receivable'] = (int)DB::value("SELECT COALESCE(SUM(p.amount-p.paid_amount),0) FROM payments p JOIN projects pr ON pr.id=p.project_id WHERE pr.status<>'canceled'");
            $d['overdue'] = DB::all("SELECT p.*, pr.title AS project_title, pr.id AS pid, CONCAT(c.first_name,' ',c.last_name) AS customer
                FROM payments p JOIN projects pr ON pr.id=p.project_id JOIN customers c ON c.id=pr.customer_id
                WHERE p.status<>'paid' AND p.due_date < CURDATE() AND pr.status<>'canceled' ORDER BY p.due_date LIMIT 8");
            $d['upcoming'] = DB::all("SELECT p.*, pr.title AS project_title, pr.id AS pid, CONCAT(c.first_name,' ',c.last_name) AS customer
                FROM payments p JOIN projects pr ON pr.id=p.project_id JOIN customers c ON c.id=pr.customer_id
                WHERE p.status<>'paid' AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) AND pr.status<>'canceled' ORDER BY p.due_date LIMIT 8");
            $d['monthly'] = array_values(Report::last12());
            $d['totals'] = DB::one("SELECT (SELECT COALESCE(SUM(amount),0) FROM receipts) + (SELECT COALESCE(SUM(amount),0) FROM support_renewals WHERE is_paid=1) AS income,
                                           (SELECT COALESCE(SUM(amount),0) FROM expenses) AS expense");
        }
        if (Auth::can('support')) {
            $d['ending'] = DB::all("SELECT p.id, p.title, p.support_end, CONCAT(c.first_name,' ',c.last_name) AS customer FROM projects p JOIN customers c ON c.id=p.customer_id
                WHERE p.support_end BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND p.status<>'canceled' ORDER BY p.support_end LIMIT 6");
        }
        if (Auth::can('projects')) {
            $d['recent'] = DB::all("SELECT p.id, p.title, p.status, p.total_amount, p.created_at, CONCAT(c.first_name,' ',c.last_name) AS customer FROM projects p JOIN customers c ON c.id=p.customer_id ORDER BY p.id DESC LIMIT 6");
        }
        $d['myShares'] = \App\Models\Project::myShares((int)Auth::id());
        $this->render('dashboard/index', $d);
    }
}
