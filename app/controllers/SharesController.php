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
        try {
            $new = Project::parseShares((array)($_POST['admin_id'] ?? []), (array)($_POST['percent'] ?? []));
        } catch (\InvalidArgumentException $e) {
            json_error($e->getMessage());
        }
        $old = DB::transaction(fn() => Project::replaceShares((int)$p['id'], $new));
        Project::notifyShares($p, $new, $old, Auth::id());
        json_ok('سهم‌بندی ذخیره شد', ['redirect' => url('projects/show', ['id' => $p['id'], 'tab' => 'shares'])]);
    }
}
