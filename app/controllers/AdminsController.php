<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;

defined('APP_ROOT') or exit('No direct access');

/** مدیریت ادمین‌ها و دسترسی‌های قابل تنظیم (فقط مدیر کل) */
class AdminsController extends Controller
{
    public function index(): void
    {
        $this->requireSuper();
        $admins = DB::all('SELECT a.id, a.name, a.username, a.mobile, a.department_id, d.name AS dept_name, a.is_super, is_active, a.last_login_at, a.created_at FROM admins a LEFT JOIN departments d ON d.id = a.department_id ORDER BY a.is_super DESC, a.id');
        $perms = [];
        foreach (DB::all('SELECT * FROM admin_permissions') as $p) {
            $perms[$p['admin_id']][$p['section']] = (int)$p['level'];
        }
        $this->render('admins/index', ['title' => 'ادمین‌ها و دسترسی‌ها', 'admins' => $admins, 'perms' => $perms,
            'departments' => DB::all('SELECT d.*, (SELECT COUNT(*) FROM admins a WHERE a.department_id = d.id) AS members FROM departments d ORDER BY sort_order, id')]);
    }

    public function save(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $id = input_int('id');
        $name = (string)input('name');
        $username = strtolower(to_en_digits((string)input('username')));
        if ($name === '' || !preg_match('/^[a-z0-9_.\-]{3,60}$/', $username)) {
            json_error('نام و نام کاربری (حداقل ۳ حرف انگلیسی/عدد) الزامی است');
        }
        if (DB::value('SELECT id FROM admins WHERE username = ? AND id <> ?', [$username, $id])) {
            json_error('این نام کاربری قبلاً استفاده شده است');
        }
        $password = (string)($_POST['password'] ?? '');
        if (!$id && mb_strlen($password) < 8) {
            json_error('رمز عبور حداقل ۸ کاراکتر باشد');
        }
        if ($id && $password !== '' && mb_strlen($password) < 8) {
            json_error('رمز عبور حداقل ۸ کاراکتر باشد');
        }
        $data = ['name' => mb_substr($name, 0, 120), 'username' => $username, 'mobile' => to_en_digits((string)input('mobile')) ?: null,
            'department_id' => (input_int('department_id') && DB::value('SELECT id FROM departments WHERE id = ?', [input_int('department_id')])) ? input_int('department_id') : null];
        $isSelf = $id === Auth::id();
        if (!$isSelf) {
            $data['is_active'] = input('is_active') ? 1 : 0;
            $data['is_super'] = input('is_super') ? 1 : 0;
        }
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        DB::transaction(function () use (&$id, $data) {
            if ($id) {
                $this->findOr404('admins', $id);
                DB::update('admins', $data, 'id = ?', [$id]);
            } else {
                $id = DB::insert('admins', $data + ['created_at' => now()]);
            }
            DB::delete('admin_permissions', 'admin_id = ?', [$id]);
            foreach (Auth::SECTIONS as $sec => $_) {
                $lvl = (int)($_POST['perm'][$sec] ?? 0);
                if ($lvl > 0) {
                    DB::insert('admin_permissions', ['admin_id' => $id, 'section' => $sec, 'level' => min(2, $lvl)]);
                }
            }
        });
        json_ok('اطلاعات ادمین ذخیره شد');
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $id = input_int('id');
        if ($id === Auth::id()) {
            json_error('نمی‌توانید حساب خودتان را حذف کنید');
        }
        $a = $this->findOr404('admins', $id);
        if ($a['is_super'] && (int)DB::value('SELECT COUNT(*) FROM admins WHERE is_super = 1') <= 1) {
            json_error('حداقل یک مدیر کل باید وجود داشته باشد');
        }
        DB::delete('admins', 'id = ?', [$id]);
        json_ok('ادمین حذف شد');
    }
}
