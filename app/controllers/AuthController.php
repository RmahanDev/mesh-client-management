<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\View;

defined('APP_ROOT') or exit('No direct access');

class AuthController extends Controller
{
    protected bool $requiresAuth = false;

    public function login(): void
    {
        if (Auth::check()) {
            redirect('dashboard');
        }
        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            verify_csrf();
            $lock = $_SESSION['_login_lock'] ?? 0;
            if ($lock > time()) {
                $error = 'تلاش‌های ناموفق زیاد بود. ' . fa_digits(ceil(($lock - time()) / 60)) . ' دقیقه دیگر دوباره امتحان کنید.';
            } elseif (Auth::attempt(to_en_digits((string)input('username')), (string)($_POST['password'] ?? ''))) {
                unset($_SESSION['_login_fail'], $_SESSION['_login_lock']);
                redirect('dashboard');
            } else {
                $_SESSION['_login_fail'] = ($_SESSION['_login_fail'] ?? 0) + 1;
                if ($_SESSION['_login_fail'] >= 5) {
                    $_SESSION['_login_lock'] = time() + 300;
                    $_SESSION['_login_fail'] = 0;
                }
                $error = 'نام کاربری یا رمز عبور اشتباه است';
            }
        }
        View::render('auth/login', ['title' => 'ورود', 'error' => $error], 'layouts/auth');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('auth/login');
    }

    public function password(): void
    {
        if (!Auth::check()) {
            json_error('ابتدا وارد شوید', 401);
        }
        $this->requirePost();
        $row = DB::one('SELECT password_hash FROM admins WHERE id = ?', [Auth::id()]);
        if (!password_verify((string)($_POST['current'] ?? ''), $row['password_hash'])) {
            json_error('رمز فعلی اشتباه است');
        }
        $p = (string)($_POST['password'] ?? '');
        if (mb_strlen($p) < 8) {
            json_error('رمز جدید باید حداقل ۸ کاراکتر باشد');
        }
        if ($p !== ($_POST['password_confirm'] ?? '')) {
            json_error('تکرار رمز با رمز جدید یکسان نیست');
        }
        DB::update('admins', ['password_hash' => password_hash($p, PASSWORD_DEFAULT)], 'id = ?', [Auth::id()]);
        json_ok('رمز عبور تغییر کرد');
    }
}
