<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Settings;
use App\Core\Upload;
use App\Models\Category;
use App\Models\Project;

defined('APP_ROOT') or exit('No direct access');

class ProjectsController extends Controller
{
    public function index(): void
    {
        $this->authorize('projects');
        $where = ['1'];
        $params = [];
        $q = (string)input('q', '');
        if ($q !== '') {
            $where[] = "(p.title LIKE ? OR CONCAT(c.first_name,' ',c.last_name) LIKE ? OR c.company LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        $status = (string)input('status', '');
        if ($status !== '' && in_array($status, ['pending', 'active', 'delivered', 'canceled'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        $type = (string)input('type', '');
        if (in_array($type, ['single', 'stages', 'installment'], true)) {
            $where[] = 'p.payment_type = ?';
            $params[] = $type;
        }
        $cat = input_int('cat');
        if ($cat) {
            // دسته انتخابی یا زیردسته‌هایش
            $where[] = 'EXISTS (SELECT 1 FROM project_categories pc JOIN categories cc ON cc.id = pc.category_id WHERE pc.project_id = p.id AND (cc.id = ? OR cc.parent_id = ?))';
            array_push($params, $cat, $cat);
        }
        $projects = DB::all("SELECT p.*, CONCAT(c.first_name,' ',c.last_name) AS customer, c.company
                             FROM projects p JOIN customers c ON c.id = p.customer_id
                             WHERE " . implode(' AND ', $where) . ' ORDER BY p.id DESC', $params);
        $ids = array_column($projects, 'id');
        $this->render('projects/index', [
            'title' => 'پروژه‌ها',
            'projects' => $projects,
            'finance' => Project::finance($ids),
            'cats' => Project::categories($ids),
            'categories' => Category::flat(),
            'customers' => DB::all('SELECT id, first_name, last_name, company FROM customers ORDER BY first_name, last_name'),
            'filters' => compact('q', 'status', 'type', 'cat'),
            'scripts' => ['js/projects.js'],
        ]);
    }

    public function show(): void
    {
        $this->authorize('projects');
        $p = DB::one("SELECT p.*, c.first_name, c.last_name, c.company FROM projects p JOIN customers c ON c.id = p.customer_id WHERE p.id = ?", [input_int('id')]);
        if (!$p) {
            \App\Core\Router::notFound();
        }
        $id = (int)$p['id'];
        $data = [
            'title' => $p['title'],
            'subtitle' => '<a href="' . url('projects') . '">پروژه‌ها</a> / <a href="' . url('customers/show', ['id' => $p['customer_id']]) . '">' . e($p['first_name'] . ' ' . $p['last_name']) . '</a>',
            'p' => $p,
            'cats' => Project::categories([$id])[$id],
            'finance' => Project::finance([$id])[$id],
            'categories' => Category::flat(),
            'customers' => DB::all('SELECT id, first_name, last_name, company FROM customers ORDER BY first_name, last_name'),
            'scripts' => ['js/projects.js'],
        ];
        if (Auth::can('finance')) {
            $data['payments'] = DB::all('SELECT * FROM payments WHERE project_id = ? ORDER BY seq, id', [$id]);
            $data['receipts'] = [];
            foreach (DB::all('SELECT * FROM receipts WHERE project_id = ? ORDER BY paid_date, id', [$id]) as $r) {
                $data['receipts'][$r['payment_id']][] = $r;
            }
            $data['expenses'] = DB::all('SELECT * FROM expenses WHERE project_id = ? ORDER BY expense_date DESC, id DESC', [$id]);
            $data['promissory'] = DB::all('SELECT * FROM promissory_notes WHERE project_id = ? ORDER BY id', [$id]);
            $data['guarantors'] = DB::all('SELECT * FROM guarantors WHERE project_id = ? ORDER BY id', [$id]);
            $data['attachMap'] = $this->attachmentsMap([
                'payment' => array_column($data['payments'], 'id'),
                'expense' => array_column($data['expenses'], 'id'),
                'promissory' => array_column($data['promissory'], 'id'),
                'guarantor' => array_column($data['guarantors'], 'id'),
            ]);
        }
        if (Auth::can('support')) {
            $data['renewals'] = DB::all('SELECT * FROM support_renewals WHERE project_id = ? ORDER BY start_date DESC', [$id]);
            $data['tickets'] = DB::all('SELECT t.*, (SELECT COUNT(*) FROM ticket_replies r WHERE r.ticket_id = t.id) AS replies FROM tickets t WHERE project_id = ? ORDER BY id DESC', [$id]);
        }
        if (Auth::can('finance')) {
            $data['sh'] = Project::shares($id, $data['finance']['profit']);
            $data['shareAdmins'] = DB::all('SELECT id, name FROM admins WHERE is_active = 1 ORDER BY name');
        }
        $data['logs'] = DB::all('SELECT l.*, a.name FROM activity_log l LEFT JOIN admins a ON a.id = l.admin_id WHERE l.entity_type = ? AND l.entity_id = ? ORDER BY l.id DESC LIMIT 20', ['project', $id]);
        $this->render('projects/show', $data);
    }

    /** ثبت پروژه جدید به همراه برنامه پرداخت، سفته، ضامن، هزینه‌ها و پیوست‌ها */
    public function store(): void
    {
        $this->requirePost();
        $this->authorize('projects', Auth::MANAGE);
        $data = $this->projectInput();
        $type = (string)input('payment_type');
        if (!in_array($type, ['single', 'stages', 'installment'], true)) {
            json_error('نوع پرداخت را انتخاب کنید');
        }
        $data['payment_type'] = $type;
        $canFinance = Auth::canManage('finance');
        $schedule = $canFinance ? $this->scheduleInput($type, (int)$data['total_amount']) : [];
        if ($type === 'installment' && input('has_guarantor') && (string)input('guarantor_name') === '') {
            json_error('نام ضامن را وارد کنید', 422, ['errors' => ['guarantor_name' => 'الزامی']]);
        }

        $shares = [];
        if (input('profit_sharing') && Auth::can('finance')) {
            try {
                $shares = Project::parseShares((array)($_POST['share_admin'] ?? []), (array)($_POST['share_percent'] ?? []));
            } catch (\InvalidArgumentException $e) {
                json_error($e->getMessage());
            }
            $data['profit_sharing'] = 1;
        }

        $id = DB::transaction(function () use ($data, $schedule, $type, $canFinance, $shares) {
            $id = DB::insert('projects', $data + ['created_by' => Auth::id(), 'created_at' => now()]);
            $this->syncCategories($id);
            if ($shares) {
                Project::replaceShares($id, $shares);
            }
            if (!$canFinance) {
                return $id;
            }
            foreach ($schedule as $i => $row) {
                DB::insert('payments', $row + ['project_id' => $id, 'seq' => $i + 1, 'created_at' => now()]);
            }
            // سفته و ضامن (برای اقساطی)
            if ($type === 'installment' && input('has_promissory')) {
                $pid = DB::insert('promissory_notes', [
                    'project_id' => $id, 'amount' => parse_money(input('promissory_amount')),
                    'count' => max(1, input_int('promissory_count', 1)), 'serials' => input('promissory_serials') ?: null,
                    'notes' => input('promissory_notes') ?: null, 'created_at' => now(),
                ]);
                Upload::saveMany('promissory_files', 'promissory', $pid, 'تصویر سفته');
            }
            if ($type === 'installment' && input('has_guarantor')) {
                $name = (string)input('guarantor_name');
                $gid = DB::insert('guarantors', [
                    'project_id' => $id, 'full_name' => $name, 'mobile' => to_en_digits((string)input('guarantor_mobile')) ?: null,
                    'national_code' => to_en_digits((string)input('guarantor_national_code')) ?: null,
                    'birth_date' => input_date('guarantor_birth_date'), 'address' => input('guarantor_address') ?: null,
                    'created_at' => now(),
                ]);
                Upload::saveMany('guarantor_files', 'guarantor', $gid, 'مدارک ضامن');
            }
            // هزینه‌های اولیه
            foreach ((array)($_POST['exp_title'] ?? []) as $i => $t) {
                $t = trim((string)$t);
                $amount = parse_money($_POST['exp_amount'][$i] ?? 0);
                if ($t === '' || $amount <= 0) {
                    continue;
                }
                DB::insert('expenses', [
                    'project_id' => $id, 'title' => mb_substr($t, 0, 200), 'amount' => $amount,
                    'expense_date' => \App\Core\Jalali::parse((string)($_POST['exp_date'][$i] ?? '')) ?? today(),
                    'created_by' => Auth::id(), 'created_at' => now(),
                ]);
            }
            return $id;
        });
        if ($shares) {
            Project::notifyShares(['id' => $id, 'title' => $data['title']], $shares, [], Auth::id());
        }
        Project::recalcSupport($id);
        Upload::saveMany('files', 'project', $id);
        $this->log('create', 'project', $id, $data['title']);
        json_ok('پروژه ثبت شد', ['redirect' => url('projects/show', ['id' => $id])]);
    }

    public function update(): void
    {
        $this->requirePost();
        $this->authorize('projects', Auth::MANAGE);
        $p = $this->findOr404('projects', input_int('id'));
        $data = $this->projectInput();
        if (!Auth::canManage('finance')) {
            $data['total_amount'] = $p['total_amount'];
        }
        DB::transaction(function () use ($p, $data) {
            DB::update('projects', $data + ['updated_at' => now()], 'id = ?', [$p['id']]);
            $this->syncCategories((int)$p['id']);
        });
        Project::recalcSupport((int)$p['id']);
        $this->log('update', 'project', (int)$p['id'], $data['title']);
        json_ok('پروژه به‌روزرسانی شد');
    }

    /** ثبت تحویل پروژه + تعیین مدت پشتیبانی */
    public function deliver(): void
    {
        $this->requirePost();
        $this->authorize('projects', Auth::MANAGE);
        $p = $this->findOr404('projects', input_int('id'));
        $date = input_date('delivery_date');
        if (!$date) {
            json_error('تاریخ تحویل را درست وارد کنید');
        }
        $months = max(0, input_int('support_months'));
        DB::update('projects', ['delivery_date' => $date, 'support_months' => $months, 'status' => 'delivered', 'updated_at' => now()], 'id = ?', [$p['id']]);
        Project::recalcSupport((int)$p['id']);
        $this->log('deliver', 'project', (int)$p['id'], 'تحویل با ' . $months . ' ماه پشتیبانی');
        json_ok('تحویل پروژه ثبت شد و روزشمار پشتیبانی فعال شد');
    }

    /** بازسازی برنامه پرداخت (ردیف‌های پرداخت‌نشده جایگزین می‌شوند) */
    public function schedule(): void
    {
        $this->requirePost();
        $this->authorize('finance', Auth::MANAGE);
        $p = $this->findOr404('projects', input_int('id'));
        $type = (string)input('payment_type');
        if (!in_array($type, ['single', 'stages', 'installment'], true)) {
            json_error('نوع پرداخت نامعتبر است');
        }
        $kept = DB::all("SELECT * FROM payments WHERE project_id = ? AND (status <> 'pending' OR EXISTS (SELECT 1 FROM receipts r WHERE r.payment_id = payments.id)) ORDER BY seq", [$p['id']]);
        $keptSum = array_sum(array_map(fn($r) => (int)$r['amount'], $kept));
        $remaining = (int)$p['total_amount'] - $keptSum;
        $schedule = $this->scheduleInput($type, $remaining);
        DB::transaction(function () use ($p, $kept, $schedule, $type) {
            $keptIds = array_column($kept, 'id');
            $old = DB::all('SELECT id FROM payments WHERE project_id = ?' . ($keptIds ? ' AND id NOT IN (' . DB::in($keptIds) . ')' : ''), array_merge([$p['id']], $keptIds));
            Upload::deleteFor('payment', array_column($old, 'id'));
            DB::query('DELETE FROM payments WHERE project_id = ?' . ($keptIds ? ' AND id NOT IN (' . DB::in($keptIds) . ')' : ''), array_merge([$p['id']], $keptIds));
            $seq = 0;
            foreach ($kept as $k) {
                DB::update('payments', ['seq' => ++$seq], 'id = ?', [$k['id']]);
            }
            foreach ($schedule as $row) {
                DB::insert('payments', $row + ['project_id' => $p['id'], 'seq' => ++$seq, 'created_at' => now()]);
            }
            DB::update('projects', ['payment_type' => $type, 'updated_at' => now()], 'id = ?', [$p['id']]);
        });
        $this->log('schedule', 'project', (int)$p['id'], 'بازسازی برنامه پرداخت');
        json_ok('برنامه پرداخت به‌روزرسانی شد');
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->authorize('projects', Auth::MANAGE);
        $p = $this->findOr404('projects', input_int('id'));
        $id = (int)$p['id'];
        if (DB::value('SELECT COUNT(*) FROM receipts WHERE project_id = ?', [$id]) && !Auth::canManage('finance')) {
            json_error('این پروژه پرداختی ثبت‌شده دارد و فقط ادمین مالی می‌تواند آن را حذف کند');
        }
        // حذف پیوست‌های وابسته
        Upload::deleteFor('project', [$id]);
        Upload::deleteFor('payment', array_column(DB::all('SELECT id FROM payments WHERE project_id = ?', [$id]), 'id'));
        Upload::deleteFor('expense', array_column(DB::all('SELECT id FROM expenses WHERE project_id = ?', [$id]), 'id'));
        Upload::deleteFor('promissory', array_column(DB::all('SELECT id FROM promissory_notes WHERE project_id = ?', [$id]), 'id'));
        Upload::deleteFor('guarantor', array_column(DB::all('SELECT id FROM guarantors WHERE project_id = ?', [$id]), 'id'));
        Upload::deleteFor('ticket', array_column(DB::all('SELECT id FROM tickets WHERE project_id = ?', [$id]), 'id'));
        Upload::deleteFor('renewal', array_column(DB::all('SELECT id FROM support_renewals WHERE project_id = ?', [$id]), 'id'));
        DB::delete('projects', 'id = ?', [$id]);
        DB::query("DELETE FROM notifications WHERE (ref_type = 'project' AND ref_id = ?) OR (ref_type = 'payment' AND ref_id NOT IN (SELECT id FROM payments))", [$id]);
        $this->log('delete', 'project', $id, $p['title']);
        json_ok('پروژه حذف شد', ['redirect' => url('projects')]);
    }

    /* ---------------- کمکی‌ها ---------------- */

    private function projectInput(): array
    {
        $customerId = input_int('customer_id');
        if (!$customerId || !DB::value('SELECT id FROM customers WHERE id = ?', [$customerId])) {
            json_error('مشتری را انتخاب کنید', 422, ['errors' => ['customer_id' => 'الزامی']]);
        }
        $title = (string)input('title');
        if ($title === '') {
            json_error('عنوان پروژه الزامی است', 422, ['errors' => ['title' => 'الزامی']]);
        }
        $total = parse_money(input('total_amount'));
        if ($total < 0) {
            json_error('مبلغ کل نامعتبر است');
        }
        $status = (string)input('status', 'active');
        if (!in_array($status, ['pending', 'active', 'delivered', 'canceled'], true)) {
            $status = 'active';
        }
        foreach (['start_date', 'delivery_date'] as $f) {
            if (input($f) && !input_date($f)) {
                json_error('تاریخ وارد شده معتبر نیست', 422, ['errors' => [$f => 'نامعتبر']]);
            }
        }
        return [
            'customer_id' => $customerId,
            'title' => mb_substr($title, 0, 200),
            'description' => input('description') ?: null,
            'details' => input('details') ?: null,
            'total_amount' => $total,
            'status' => $status,
            'start_date' => input_date('start_date'),
            'delivery_date' => input_date('delivery_date'),
            'support_months' => max(0, input_int('support_months', (int)Settings::get('default_support_months', 3))),
        ];
    }

    private function syncCategories(int $projectId): void
    {
        DB::delete('project_categories', 'project_id = ?', [$projectId]);
        $ids = array_unique(array_map('intval', (array)($_POST['categories'] ?? [])));
        foreach ($ids as $cid) {
            if ($cid > 0) {
                DB::query('INSERT IGNORE INTO project_categories (project_id, category_id) SELECT ?, id FROM categories WHERE id = ?', [$projectId, $cid]);
            }
        }
    }

    /**
     * خواندن ردیف‌های برنامه پرداخت از فرم (sched_title[], sched_amount[], sched_due[], sched_kind[])
     * جمع ردیف‌ها باید برابر مبلغ مورد نظر باشد.
     */
    private function scheduleInput(string $type, int $expected): array
    {
        $titles = (array)($_POST['sched_title'] ?? []);
        $rows = [];
        foreach ($titles as $i => $t) {
            $amount = parse_money($_POST['sched_amount'][$i] ?? 0);
            $dueRaw = (string)($_POST['sched_due'][$i] ?? '');
            $due = Jalali::parse($dueRaw);
            if ($dueRaw !== '' && !$due) {
                json_error('تاریخ ردیف ' . fa_digits($i + 1) . ' معتبر نیست');
            }
            $kind = (string)($_POST['sched_kind'][$i] ?? '');
            if (!in_array($kind, ['single', 'stage', 'prepayment', 'installment'], true)) {
                $kind = ['single' => 'single', 'stages' => 'stage', 'installment' => 'installment'][$type];
            }
            if ($amount <= 0) {
                continue;
            }
            $rows[] = [
                'kind' => $kind,
                'title' => mb_substr(trim((string)$t) ?: ('ردیف ' . ($i + 1)), 0, 150),
                'amount' => $amount,
                'due_date' => $due,
            ];
        }
        if ($expected <= 0 && !$rows) {
            return [];
        }
        if (!$rows) {
            json_error('برنامه پرداخت خالی است');
        }
        if ($type === 'stages' && count($rows) < 2) {
            json_error('پرداخت چندمرحله‌ای حداقل ۲ مرحله لازم دارد');
        }
        if ($type === 'installment') {
            foreach ($rows as $r) {
                if ($r['kind'] === 'installment' && !$r['due_date']) {
                    json_error('برای همه اقساط تاریخ سررسید لازم است');
                }
            }
        }
        $sum = array_sum(array_column($rows, 'amount'));
        if ($sum !== $expected) {
            json_error('جمع ردیف‌های پرداخت (' . money_plain($sum) . ') با مبلغ مورد انتظار (' . money_plain($expected) . ') برابر نیست');
        }
        return $rows;
    }

    private function attachmentsMap(array $groups): array
    {
        $map = [];
        foreach ($groups as $entity => $ids) {
            if (!$ids) {
                continue;
            }
            foreach (DB::all('SELECT * FROM attachments WHERE entity_type = ? AND entity_id IN (' . DB::in($ids) . ') ORDER BY id DESC', array_merge([$entity], $ids)) as $a) {
                $map[$entity][$a['entity_id']][] = $a;
            }
        }
        return $map;
    }
}
