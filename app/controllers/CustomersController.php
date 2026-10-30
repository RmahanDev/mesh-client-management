<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Upload;
use App\Models\Project;

defined('APP_ROOT') or exit('No direct access');

class CustomersController extends Controller
{
    public function index(): void
    {
        $this->authorize('customers');
        $q = (string)input('q', '');
        $params = [];
        $where = '1';
        if ($q !== '') {
            $like = '%' . to_en_digits($q) . '%';
            $where = "(CONCAT(c.first_name,' ',c.last_name) LIKE ? OR c.company LIKE ? OR c.national_code LIKE ?
                      OR EXISTS (SELECT 1 FROM customer_contacts cc WHERE cc.customer_id = c.id AND cc.value LIKE ?))";
            $params = ['%' . $q . '%', '%' . $q . '%', $like, $like];
        }
        $customers = DB::all("SELECT c.*,
                (SELECT COUNT(*) FROM projects p WHERE p.customer_id = c.id) AS projects_count,
                (SELECT COALESCE(SUM(p.total_amount),0) FROM projects p WHERE p.customer_id = c.id AND p.status<>'canceled') AS total_amount,
                (SELECT COALESCE(SUM(pa.amount - pa.paid_amount),0) FROM payments pa JOIN projects p ON p.id = pa.project_id WHERE p.customer_id = c.id AND p.status<>'canceled') AS debt
            FROM customers c WHERE $where ORDER BY c.id DESC", $params);
        $contacts = $this->contactsFor(array_column($customers, 'id'));
        $this->render('customers/index', ['title' => 'مشتریان', 'customers' => $customers, 'contacts' => $contacts, 'q' => $q]);
    }

    public function show(): void
    {
        $this->authorize('customers');
        $c = $this->findOr404('customers', input_int('id'));
        $contacts = $this->contactsFor([$c['id']])[$c['id']] ?? [];
        $projects = DB::all('SELECT * FROM projects WHERE customer_id = ? ORDER BY id DESC', [$c['id']]);
        $ids = array_column($projects, 'id');
        $this->render('customers/show', [
            'title' => $c['first_name'] . ' ' . $c['last_name'],
            'subtitle' => '<a href="' . url('customers') . '">مشتریان</a> / پرونده مشتری',
            'c' => $c, 'contacts' => $contacts, 'projects' => $projects,
            'finance' => Project::finance($ids), 'cats' => Project::categories($ids),
        ]);
    }

    public function save(): void
    {
        $this->requirePost();
        $this->authorize('customers', Auth::MANAGE);
        $id = input_int('id');
        $data = [
            'first_name' => (string)input('first_name'),
            'last_name' => (string)input('last_name'),
            'company' => input('company') ?: null,
            'national_code' => to_en_digits((string)input('national_code')) ?: null,
            'notes' => input('notes') ?: null,
        ];
        if ($data['first_name'] === '' || $data['last_name'] === '') {
            json_error('نام و نام خانوادگی الزامی است', 422, ['errors' => ['first_name' => 'الزامی', 'last_name' => 'الزامی']]);
        }
        if ($data['national_code'] && !preg_match('/^\d{10}$/', $data['national_code'])) {
            json_error('کد ملی باید ۱۰ رقم باشد');
        }
        $contacts = [];
        $types = $_POST['contact_type'] ?? [];
        $values = $_POST['contact_value'] ?? [];
        $labels = $_POST['contact_label'] ?? [];
        foreach ((array)$types as $i => $t) {
            $v = trim((string)($values[$i] ?? ''));
            if ($v === '' || !array_key_exists($t, contact_types())) {
                continue;
            }
            if (in_array($t, ['mobile', 'phone'], true)) {
                $v = to_en_digits($v);
            }
            $contacts[] = ['type' => $t, 'value' => mb_substr($v, 0, 255), 'label' => mb_substr(trim((string)($labels[$i] ?? '')), 0, 80) ?: null];
        }
        if (!$contacts) {
            json_error('حداقل یک راه ارتباطی وارد کنید');
        }

        $id = DB::transaction(function () use ($id, $data, $contacts) {
            if ($id) {
                $this->findOr404('customers', $id);
                DB::update('customers', $data + ['updated_at' => now()], 'id = ?', [$id]);
                DB::delete('customer_contacts', 'customer_id = ?', [$id]);
            } else {
                $id = DB::insert('customers', $data + ['created_by' => Auth::id(), 'created_at' => now()]);
            }
            foreach ($contacts as $ct) {
                DB::insert('customer_contacts', $ct + ['customer_id' => $id]);
            }
            return $id;
        });
        Upload::saveMany('files', 'customer', $id);
        $this->log(input_int('id') ? 'update' : 'create', 'customer', $id, $data['first_name'] . ' ' . $data['last_name']);
        json_ok('اطلاعات مشتری ذخیره شد', input_int('id') ? [] : ['redirect' => url('customers/show', ['id' => $id])]);
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->authorize('customers', Auth::MANAGE);
        $id = input_int('id');
        if (DB::value('SELECT COUNT(*) FROM projects WHERE customer_id = ?', [$id])) {
            json_error('این مشتری پروژه دارد. ابتدا پروژه‌های او را حذف کنید.');
        }
        Upload::deleteFor('customer', [$id]);
        DB::delete('customers', 'id = ?', [$id]);
        $this->log('delete', 'customer', $id);
        json_ok('مشتری حذف شد', ['redirect' => url('customers')]);
    }

    private function contactsFor(array $ids): array
    {
        $out = [];
        if (!$ids) {
            return $out;
        }
        foreach (DB::all('SELECT * FROM customer_contacts WHERE customer_id IN (' . DB::in($ids) . ') ORDER BY id', $ids) as $r) {
            $out[$r['customer_id']][] = $r;
        }
        return $out;
    }
}
