<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Notifier;

defined('APP_ROOT') or exit('No direct access');

class NotificationsController extends Controller
{
    public function index(): void
    {
        $this->render('notifications/index', ['title' => 'اعلان‌ها', 'items' => Notifier::latest(200)]);
    }

    public function open(): void
    {
        $id = input_int('id');
        $n  = DB::one('SELECT * FROM notifications WHERE id = ?', [$id]);
        if (!$n || ($n['admin_id'] ? (int)$n['admin_id'] !== Auth::id() : !Auth::can($n['section']))) {
            redirect('notifications');
        }
        DB::query('INSERT IGNORE INTO notification_reads (notification_id, admin_id, read_at) VALUES (?,?,?)', [$id, Auth::id(), now()]);
        header('Location: ' . ($n['link'] ?: url('notifications')));
        exit;
    }

    public function readAll(): void
    {
        $this->requirePost();
        foreach (Notifier::latest(1000, true) as $n) {
            DB::query('INSERT IGNORE INTO notification_reads (notification_id, admin_id, read_at) VALUES (?,?,?)', [$n['id'], Auth::id(), now()]);
        }
        json_ok('همه اعلان‌ها خوانده شد');
    }
}
