<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

defined('APP_ROOT') or exit('No direct access');

/** مدیریت دپارتمان‌ها (فقط مدیر کل) */
class DepartmentsController extends Controller
{
    public function save(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $id = input_int('id');
        $name = (string)input('name');
        if ($name === '') {
            json_error('نام دپارتمان الزامی است');
        }
        if (DB::value('SELECT id FROM departments WHERE name = ? AND id <> ?', [$name, $id])) {
            json_error('این دپارتمان قبلاً تعریف شده است');
        }
        $color = (string)input('color', '#3b82f6');
        $data = ['name' => mb_substr($name, 0, 100), 'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#3b82f6', 'sort_order' => input_int('sort_order')];
        if ($id) {
            $this->findOr404('departments', $id);
            DB::update('departments', $data, 'id = ?', [$id]);
        } else {
            DB::insert('departments', $data);
        }
        json_ok('دپارتمان ذخیره شد');
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $id = input_int('id');
        $this->findOr404('departments', $id);
        DB::delete('departments', 'id = ?', [$id]);
        json_ok('دپارتمان حذف شد؛ ادمین‌ها و تیکت‌های آن بدون دپارتمان ماندند');
    }
}
