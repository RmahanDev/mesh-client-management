<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Upload;
use App\Models\Project;

defined('APP_ROOT') or exit('No direct access');

/** بخش مالی: سررسیدها، دریافت‌ها، هزینه‌ها، سفته و ضامن */
class FinanceController extends Controller
{
    public function index(): void
    {
        $this->authorize('finance');
        $filter = (string)input('f', 'upcoming');
        $days = (int)\App\Core\Settings::get('reminder_days', 7);
        $where = "pr.status <> 'canceled'";
        $params = [];
        switch ($filter) {
            case 'overdue':
                $where .= " AND p.status <> 'paid' AND p.due_date < CURDATE()";
                break;
            case 'upcoming':
                $where .= " AND p.status <> 'paid' AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'unpaid':
                $where .= " AND p.status <> 'paid'";
                break;
            case 'nodate':
                $where .= " AND p.status <> 'paid' AND p.due_date IS NULL";
                break;
            case 'paid':
                $where .= " AND p.status = 'paid'";
                break;
            default:
                $filter = 'all';
        }
        $order = $filter === 'paid' ? 'p.paid_date DESC' : 'p.due_date IS NULL, p.due_date ASC';
        $rows = DB::all("SELECT p.*, pr.title AS project_title, pr.id AS pid, CONCAT(c.first_name,' ',c.last_name) AS customer, c.id AS cid
                         FROM payments p JOIN projects pr ON pr.id = p.project_id JOIN customers c ON c.id = pr.customer_id
                         WHERE $where ORDER BY $order LIMIT 500", $params);

        $stats = DB::one("SELECT
                COALESCE(SUM(CASE WHEN p.status<>'paid' AND p.due_date < CURDATE() THEN p.amount-p.paid_amount END),0) AS overdue,
                COUNT(CASE WHEN p.status<>'paid' AND p.due_date < CURDATE() THEN 1 END) AS overdue_count,
                COALESCE(SUM(CASE WHEN p.status<>'paid' AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) THEN p.amount-p.paid_amount END),0) AS soon,
                COUNT(CASE WHEN p.status<>'paid' AND p.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) THEN 1 END) AS soon_count,
                COALESCE(SUM(p.amount-p.paid_amount),0) AS receivable
            FROM payments p JOIN projects pr ON pr.id = p.project_id WHERE pr.status <> 'canceled'");
        [$jy, $jm] = \App\Core\Jalali::yearMonth(today());
        [$ms, $me] = \App\Core\Jalali::monthRange($jy, $jm);
        $stats['month_income'] = (int)DB::value('SELECT COALESCE(SUM(amount),0) FROM receipts WHERE paid_date BETWEEN ? AND ?', [$ms, $me])
            + (int)DB::value('SELECT COALESCE(SUM(amount),0) FROM support_renewals WHERE is_paid = 1 AND paid_date BETWEEN ? AND ?', [$ms, $me]);

        $recentReceipts = DB::all("SELECT r.*, pa.title AS payment_title, pr.title AS project_title, pr.id AS pid, CONCAT(c.first_name,' ',c.last_name) AS customer
                                   FROM receipts r JOIN payments pa ON pa.id = r.payment_id JOIN projects pr ON pr.id = r.project_id JOIN customers c ON c.id = pr.customer_id
                                   ORDER BY r.paid_date DESC, r.id DESC LIMIT 15");
        $expenses = DB::all("SELECT e.*, pr.title AS project_title, pr.id AS pid FROM expenses e JOIN projects pr ON pr.id = e.project_id ORDER BY e.expense_date DESC, e.id DESC LIMIT 15");

        $this->render('finance/index', [
            'title' => 'پرداخت‌ها و اقساط',
            'rows' => $rows, 'filter' => $filter, 'stats' => $stats,
            'recentReceipts' => $recentReceipts, 'expenses' => $expenses,
            'projectOptions' => Project::options(),
        ]);
    }

    /** ثبت دریافت وجه برای یک ردیف پرداخت (پرداخت کامل یا بخشی) */
    public function receive(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $pay = $this->findOr404('payments', input_int('payment_id'));
        $amount = parse_money(input('amount'));
        $date = input_date('paid_date');
        if ($amount <= 0) {
            json_error('مبلغ دریافتی را وارد کنید');
        }
        if (!$date) {
            json_error('تاریخ دریافت معتبر نیست');
        }
        $rest = (int)$pay['amount'] - (int)$pay['paid_amount'];
        if ($amount > $rest) {
            json_error('مبلغ دریافتی (' . money_plain($amount) . ') از مانده این ردیف (' . money_plain($rest) . ') بیشتر است');
        }
        $method = (string)input('method');
        DB::insert('receipts', [
            'payment_id' => $pay['id'], 'project_id' => $pay['project_id'], 'amount' => $amount, 'paid_date' => $date,
            'method' => in_array($method, ['card', 'transfer', 'cash', 'pos', 'cheque', 'online', 'other'], true) ? $method : null,
            'ref_no' => input('ref_no') ?: null, 'notes' => input('notes') ?: null,
            'created_by' => Auth::id(), 'created_at' => now(),
        ]);
        Project::recalcPayment((int)$pay['id']);
        $up = Upload::saveMany('files', 'payment', (int)$pay['id'], 'رسید ' . $pay['title']);
        $this->log('receive', 'project', (int)$pay['project_id'], 'دریافت ' . money_plain($amount) . ' بابت ' . $pay['title']);
        json_ok('دریافت ' . money_plain($amount) . ' ثبت شد' . ($up['errors'] ? ' (خطای آپلود: ' . implode(' | ', $up['errors']) . ')' : ''));
    }

    public function receiptDelete(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $r = $this->findOr404('receipts', input_int('id'));
        DB::delete('receipts', 'id = ?', [$r['id']]);
        Project::recalcPayment((int)$r['payment_id']);
        $this->log('receipt_delete', 'project', (int)$r['project_id'], 'حذف دریافتی ' . money_plain($r['amount']));
        json_ok('دریافتی حذف شد');
    }

    public function paymentSave(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $id = input_int('id');
        $title = (string)input('title');
        $amount = parse_money(input('amount'));
        if ($title === '' || $amount <= 0) {
            json_error('عنوان و مبلغ الزامی است');
        }
        if (input('due_date') && !input_date('due_date')) {
            json_error('تاریخ سررسید معتبر نیست');
        }
        $kind = (string)input('kind');
        $data = [
            'title' => mb_substr($title, 0, 150), 'amount' => $amount, 'due_date' => input_date('due_date'),
            'kind' => in_array($kind, ['single', 'stage', 'prepayment', 'installment'], true) ? $kind : 'stage',
            'notes' => input('notes') ?: null,
        ];
        if ($id) {
            $pay = $this->findOr404('payments', $id);
            if ($amount < (int)$pay['paid_amount']) {
                json_error('مبلغ ردیف نمی‌تواند از مقدار دریافت‌شده کمتر باشد');
            }
            DB::update('payments', $data, 'id = ?', [$id]);
            Project::recalcPayment($id);
            $pid = (int)$pay['project_id'];
        } else {
            $pid = input_int('project_id');
            $this->findOr404('projects', $pid);
            $seq = (int)DB::value('SELECT COALESCE(MAX(seq),0)+1 FROM payments WHERE project_id = ?', [$pid]);
            $id = DB::insert('payments', $data + ['project_id' => $pid, 'seq' => $seq, 'created_at' => now()]);
        }
        $this->log('payment', 'project', $pid, 'ردیف پرداخت: ' . $title);
        json_ok('ردیف پرداخت ذخیره شد');
    }

    public function paymentDelete(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $pay = $this->findOr404('payments', input_int('id'));
        if (DB::value('SELECT COUNT(*) FROM receipts WHERE payment_id = ?', [$pay['id']])) {
            json_error('این ردیف دریافتی دارد؛ ابتدا دریافتی‌ها را حذف کنید');
        }
        Upload::deleteFor('payment', [(int)$pay['id']]);
        DB::delete('payments', 'id = ?', [$pay['id']]);
        $this->log('payment_delete', 'project', (int)$pay['project_id'], 'حذف ردیف ' . $pay['title']);
        json_ok('ردیف حذف شد');
    }

    public function expenseSave(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $id = input_int('id');
        $title = (string)input('title');
        $amount = parse_money(input('amount'));
        $date = input_date('expense_date');
        if ($title === '' || $amount <= 0 || !$date) {
            json_error('عنوان، مبلغ و تاریخ هزینه الزامی است');
        }
        $data = ['title' => mb_substr($title, 0, 200), 'amount' => $amount, 'expense_date' => $date, 'notes' => input('notes') ?: null];
        if ($id) {
            $this->findOr404('expenses', $id);
            DB::update('expenses', $data, 'id = ?', [$id]);
        } else {
            $pid = input_int('project_id');
            $this->findOr404('projects', $pid);
            $id = DB::insert('expenses', $data + ['project_id' => $pid, 'created_by' => Auth::id(), 'created_at' => now()]);
            Upload::saveMany('files', 'expense', $id, 'فاکتور');
        }
        json_ok('هزینه ذخیره شد');
    }

    public function expenseDelete(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $x = $this->findOr404('expenses', input_int('id'));
        Upload::deleteFor('expense', [(int)$x['id']]);
        DB::delete('expenses', 'id = ?', [$x['id']]);
        json_ok('هزینه حذف شد');
    }

    public function promissorySave(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $id = input_int('id');
        $data = [
            'amount' => parse_money(input('amount')), 'count' => max(1, input_int('count', 1)),
            'serials' => input('serials') ?: null, 'notes' => input('notes') ?: null,
            'status' => input('status') === 'returned' ? 'returned' : 'held',
        ];
        if ($id) {
            $this->findOr404('promissory_notes', $id);
            DB::update('promissory_notes', $data, 'id = ?', [$id]);
        } else {
            $pid = input_int('project_id');
            $this->findOr404('projects', $pid);
            $id = DB::insert('promissory_notes', $data + ['project_id' => $pid, 'created_at' => now()]);
            Upload::saveMany('files', 'promissory', $id, 'تصویر سفته');
        }
        json_ok('اطلاعات سفته ذخیره شد');
    }

    public function promissoryDelete(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $x = $this->findOr404('promissory_notes', input_int('id'));
        Upload::deleteFor('promissory', [(int)$x['id']]);
        DB::delete('promissory_notes', 'id = ?', [$x['id']]);
        json_ok('سفته حذف شد');
    }

    public function guarantorSave(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $id = input_int('id');
        $name = (string)input('full_name');
        if ($name === '') {
            json_error('نام ضامن الزامی است');
        }
        $nc = to_en_digits((string)input('national_code'));
        if ($nc !== '' && !preg_match('/^\d{10}$/', $nc)) {
            json_error('کد ملی ضامن باید ۱۰ رقم باشد');
        }
        if (input('birth_date') && !input_date('birth_date')) {
            json_error('تاریخ تولد معتبر نیست');
        }
        $data = [
            'full_name' => mb_substr($name, 0, 150), 'mobile' => to_en_digits((string)input('mobile')) ?: null,
            'national_code' => $nc ?: null, 'birth_date' => input_date('birth_date'),
            'address' => input('address') ?: null, 'notes' => input('notes') ?: null,
        ];
        if ($id) {
            $this->findOr404('guarantors', $id);
            DB::update('guarantors', $data, 'id = ?', [$id]);
        } else {
            $pid = input_int('project_id');
            $this->findOr404('projects', $pid);
            $id = DB::insert('guarantors', $data + ['project_id' => $pid, 'created_at' => now()]);
            Upload::saveMany('files', 'guarantor', $id, 'مدارک ضامن');
        }
        json_ok('اطلاعات ضامن ذخیره شد');
    }

    public function guarantorDelete(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $x = $this->findOr404('guarantors', input_int('id'));
        Upload::deleteFor('guarantor', [(int)$x['id']]);
        DB::delete('guarantors', 'id = ?', [$x['id']]);
        json_ok('ضامن حذف شد');
    }
}
