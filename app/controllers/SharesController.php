<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Notifier;
use App\Models\Project;

defined('APP_ROOT') or exit('No direct access');

/** سهم‌بندی سود پروژه بین ادمین‌ها؛ هر ادمین سهم خودش را می‌بیند */
class SharesController extends Controller
{
    public function index(): void
    {
        $mine = Project::sharesOf((int)Auth::id());
        $everyone = [];
        if (Auth::isSuper()) {
            $rows = DB::all('SELECT admin_id FROM project_shares s JOIN projects p ON p.id = s.project_id AND p.profit_sharing = 1 GROUP BY admin_id');
            foreach ($rows as $r) {
                $list = Project::sharesOf((int)$r['admin_id']);
                $name = DB::value('SELECT name FROM admins WHERE id = ?', [$r['admin_id']]);
                $everyone[] = ['name' => $name, 'projects' => count($list), 'amount' => array_sum(array_column($list, 'amount'))];
            }
            usort($everyone, fn($a, $b) => $b['amount'] <=> $a['amount']);
        }
        $this->render('shares/index', ['title' => 'سهم من از پروژه‌ها', 'mine' => $mine, 'everyone' => $everyone]);
    }

    public function toggle(): void
    {
        $this->requirePost();
        $this->authorize('projects', Auth::MANAGE);
        $p = $this->findOr404('projects', input_int('project_id'));
        DB::update('projects', ['profit_sharing' => input('enabled') ? 1 : 0], 'id = ?', [$p['id']]);
        json_ok(input('enabled') ? 'سهم‌بندی این پروژه فعال شد' : 'سهم‌بندی غیرفعال شد (سهم‌ها حفظ می‌شوند)', ['redirect' => url('projects/show', ['id' => $p['id'], 'tab' => 'shares'])]);
    }

    public function save(): void
    {
        $this->requirePost();
        $this->authorize('projects', Auth::MANAGE);
        if (!Auth::can('finance')) {
            json_error('برای سهم‌بندی دسترسی به بخش مالی لازم است', 403);
        }
        $p = $this->findOr404('projects', input_int('project_id'));
        $ids = (array)($_POST['admin_id'] ?? []);
        $pcs = (array)($_POST['percent'] ?? []);
        $new = [];
        foreach ($ids as $i => $aid) {
            $aid = (int)$aid;
            $pc = round((float)str_replace(['٫', ','], '.', to_en_digits((string)($pcs[$i] ?? '0'))), 2);
            if (!$aid && $pc <= 0) {
                continue;
            }
            if (!$aid) {
                json_error('برای هر درصد یک ادمین انتخاب کنید');
            }
            if ($pc <= 0 || $pc > 100) {
                json_error('درصد هر نفر باید بین ۰ تا ۱۰۰ باشد');
            }
            if (isset($new[$aid])) {
                json_error('یک ادمین فقط یک بار می‌تواند در سهم‌بندی باشد');
            }
            if (!DB::value('SELECT id FROM admins WHERE id = ? AND is_active = 1', [$aid])) {
                json_error('ادمین انتخاب‌شده معتبر یا فعال نیست');
            }
            $new[$aid] = $pc;
        }
        if (round(array_sum($new), 2) > 100) {
            json_error('جمع درصدها نمی‌تواند بیشتر از ۱۰۰ باشد');
        }
        $old = [];
        foreach (DB::all('SELECT admin_id, percent FROM project_shares WHERE project_id = ?', [$p['id']]) as $r) {
            $old[(int)$r['admin_id']] = (float)$r['percent'];
        }
        DB::transaction(function () use ($p, $new) {
            DB::delete('project_shares', 'project_id = ?', [$p['id']]);
            foreach ($new as $aid => $pc) {
                DB::insert('project_shares', ['project_id' => $p['id'], 'admin_id' => $aid, 'percent' => $pc, 'created_at' => now()]);
            }
        });
        foreach ($new as $aid => $pc) {
            if ($aid !== Auth::id() && (!isset($old[$aid]) || abs($old[$aid] - $pc) > 0.001)) {
                Notifier::add('share', 'shares', 'project', (int)$p['id'], "sh:{$p['id']}:{$aid}:" . (string)$pc, 'سهم شما از پروژه «' . $p['title'] . '» ثبت شد',
                    'سهم شما: ' . rtrim(rtrim(number_format($pc, 2), '0'), '.') . '٪ از سود پروژه', url('shares'), $aid);
            }
        }
        json_ok('سهم‌بندی ذخیره شد', ['redirect' => url('projects/show', ['id' => $p['id'], 'tab' => 'shares'])]);
    }
}
