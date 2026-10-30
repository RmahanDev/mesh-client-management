<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Settings;

defined('APP_ROOT') or exit('No direct access');

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->requireSuper();
        $this->render('settings/index', ['title' => 'تنظیمات']);
    }

    public function save(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $name = (string)input('company_name');
        if ($name === '') {
            json_error('نام سیستم الزامی است');
        }
        Settings::set('company_name', mb_substr($name, 0, 100));
        Settings::set('reminder_days', max(1, min(60, input_int('reminder_days', 7))));
        Settings::set('upload_max_mb', max(1, min(100, input_int('upload_max_mb', 10))));
        Settings::set('default_support_months', max(0, min(120, input_int('default_support_months', 3))));
        unset($_SESSION['_notif_check']);
        json_ok('تنظیمات ذخیره شد');
    }
}
