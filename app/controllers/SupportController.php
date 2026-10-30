<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Jalali;
use App\Core\Router;
use App\Core\Upload;
use App\Models\Project;

defined('APP_ROOT') or exit('No direct access');

/** پشتیبانی: روزشمار، تمدید پولی و تیکت‌ها */
class SupportController extends Controller
{
    public function index(): void
    {
        $this->authorize('support');
        $projects = DB::all("SELECT p.id, p.title, p.support_start, p.support_end, p.support_months, p.status,
                                    CONCAT(c.first_name,' ',c.last_name) AS customer, c.id AS cid,
                                    (SELECT COUNT(*) FROM tickets t WHERE t.project_id = p.id AND t.status IN ('open','in_progress')) AS open_tickets
                             FROM projects p JOIN customers c ON c.id = p.customer_id
                             WHERE p.support_end IS NOT NULL AND p.status <> 'canceled'
                             ORDER BY (p.support_end < CURDATE()), p.support_end ASC");
        $tf = (string)input('t', 'active');
        $tWhere = $tf === 'closed' ? "t.status = 'closed'" : ($tf === 'all' ? '1' : "t.status <> 'closed'");
        $tickets = DB::all("SELECT t.*, p.title AS project_title, CONCAT(c.first_name,' ',c.last_name) AS customer,
                                   (SELECT COUNT(*) FROM ticket_replies r WHERE r.ticket_id = t.id) AS replies
                            FROM tickets t JOIN projects p ON p.id = t.project_id JOIN customers c ON c.id = p.customer_id
                            WHERE $tWhere ORDER BY FIELD(t.priority,'urgent','high','normal','low'), t.id DESC LIMIT 300");
        $this->render('support/index', [
            'title' => 'پشتیبانی و تیکت‌ها',
            'projects' => $projects, 'tickets' => $tickets, 'tf' => $tf,
            'projectOptions' => Project::options(),
        ]);
    }

    public function renewalSave(): void
    {
        $this->requirePost();
        $this->authorize('support', Auth::MANAGE);
        $id = input_int('id');
        $months = input_int('months');
        if ($months < 1) {
            json_error('مدت تمدید را وارد کنید');
        }
        $old = $id ? $this->findOr404('support_renewals', $id) : null;
        $pid = $old ? (int)$old['project_id'] : input_int('project_id');
        $project = $this->findOr404('projects', $pid);
        if (input('start_date') && !input_date('start_date')) {
            json_error('تاریخ شروع معتبر نیست');
        }
        $start = input_date('start_date');
        if (!$start) {
            $start = ($project['support_end'] && $project['support_end'] >= today()) ? date('Y-m-d', strtotime($project['support_end'] . ' +1 day')) : today();
        }
        $isPaid = input('is_paid') ? 1 : 0;
        $data = [
            'months' => $months, 'amount' => parse_money(input('amount')),
            'start_date' => $start, 'end_date' => Jalali::addMonths($start, $months),
            'is_paid' => $isPaid, 'paid_date' => $isPaid ? (input_date('paid_date') ?? today()) : null,
            'notes' => input('notes') ?: null,
        ];
        if ($old) {
            DB::update('support_renewals', $data, 'id = ?', [$id]);
        } else {
            // اگر پروژه هنوز تحویل نشده، شروع پشتیبانی همان شروع تمدید است
            if (!$project['delivery_date']) {
                DB::update('projects', ['delivery_date' => $start, 'support_months' => 0], 'id = ?', [$pid]);
            }
            $id = DB::insert('support_renewals', $data + ['project_id' => $pid, 'created_by' => Auth::id(), 'created_at' => now()]);
            Upload::saveMany('files', 'renewal', $id, 'فاکتور تمدید');
        }
        Project::recalcSupport($pid);
        $this->log('renewal', 'project', $pid, 'تمدید پشتیبانی ' . $months . ' ماه');
        json_ok('تمدید پشتیبانی ثبت شد');
    }

    public function renewalDelete(): void
    {
        $this->requirePost();
        $this->authorize('support', Auth::MANAGE);
        $r = $this->findOr404('support_renewals', input_int('id'));
        Upload::deleteFor('renewal', [(int)$r['id']]);
        DB::delete('support_renewals', 'id = ?', [$r['id']]);
        Project::recalcSupport((int)$r['project_id']);
        json_ok('تمدید حذف شد');
    }

    public function ticketSave(): void
    {
        $this->requirePost();
        $this->authorize('support', Auth::MANAGE);
        $id = input_int('id');
        $subject = (string)input('subject');
        if ($subject === '') {
            json_error('موضوع تیکت الزامی است');
        }
        $status = (string)input('status', 'open');
        $priority = (string)input('priority', 'normal');
        $data = [
            'subject' => mb_substr($subject, 0, 200), 'description' => input('description') ?: null,
            'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'status' => in_array($status, ['open', 'in_progress', 'answered', 'closed'], true) ? $status : 'open',
            'channel' => input('channel') ?: null,
        ];
        $data['closed_at'] = $data['status'] === 'closed' ? now() : null;
        if ($id) {
            $this->findOr404('tickets', $id);
            DB::update('tickets', $data + ['updated_at' => now()], 'id = ?', [$id]);
            json_ok('تیکت به‌روزرسانی شد');
        }
        $pid = input_int('project_id');
        $this->findOr404('projects', $pid);
        $id = DB::insert('tickets', $data + ['project_id' => $pid, 'created_by' => Auth::id(), 'created_at' => now()]);
        Upload::saveMany('files', 'ticket', $id);
        $this->log('ticket', 'project', $pid, 'تیکت: ' . $subject);
        json_ok('تیکت ثبت شد', ['redirect' => url('support/ticket', ['id' => $id])]);
    }

    public function ticket(): void
    {
        $this->authorize('support');
        $t = DB::one("SELECT t.*, p.title AS project_title, p.support_end, CONCAT(c.first_name,' ',c.last_name) AS customer, c.id AS cid, a.name AS creator
                      FROM tickets t JOIN projects p ON p.id = t.project_id JOIN customers c ON c.id = p.customer_id
                      LEFT JOIN admins a ON a.id = t.created_by WHERE t.id = ?", [input_int('id')]);
        if (!$t) {
            Router::notFound();
        }
        $replies = DB::all('SELECT r.*, a.name FROM ticket_replies r LEFT JOIN admins a ON a.id = r.admin_id WHERE r.ticket_id = ? ORDER BY r.id', [$t['id']]);
        $this->render('support/ticket', [
            'title' => 'تیکت: ' . $t['subject'],
            'subtitle' => '<a href="' . url('support') . '">پشتیبانی</a> / <a href="' . url('projects/show', ['id' => $t['project_id'], 'tab' => 'support']) . '">' . e($t['project_title']) . '</a>',
            't' => $t, 'replies' => $replies,
        ]);
    }

    public function reply(): void
    {
        $this->requirePost();
        $this->authorize('support', Auth::MANAGE);
        $t = $this->findOr404('tickets', input_int('ticket_id'));
        $body = (string)input('body');
        if ($body === '') {
            json_error('متن پاسخ / یادداشت را بنویسید');
        }
        DB::insert('ticket_replies', ['ticket_id' => $t['id'], 'admin_id' => Auth::id(), 'body' => $body, 'created_at' => now()]);
        $status = (string)input('status');
        if (in_array($status, ['open', 'in_progress', 'answered', 'closed'], true)) {
            DB::update('tickets', ['status' => $status, 'updated_at' => now(), 'closed_at' => $status === 'closed' ? now() : null], 'id = ?', [$t['id']]);
        }
        Upload::saveMany('files', 'ticket', (int)$t['id']);
        json_ok('پاسخ ثبت شد');
    }

    public function ticketDelete(): void
    {
        $this->requirePost();
        $this->authorize('support', Auth::MANAGE);
        $t = $this->findOr404('tickets', input_int('id'));
        Upload::deleteFor('ticket', [(int)$t['id']]);
        DB::delete('tickets', 'id = ?', [$t['id']]);
        json_ok('تیکت حذف شد', ['redirect' => url('support')]);
    }
}
