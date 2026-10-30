<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Router;
use App\Core\Upload;
use App\Models\InternalTicket;

defined('APP_ROOT') or exit('No direct access');

/** تیکت داخلی بین ادمین‌ها و دپارتمان‌ها (برای همه ادمین‌ها فعال است) */
class InternalController extends Controller
{
    private const STATUSES = ['open', 'in_progress', 'done', 'closed'];

    public function index(): void
    {
        $box = (string)input('box', 'inbox');
        $st = (string)input('st', 'active');
        [$aw, $ap] = InternalTicket::accessWhere();
        $where = [$aw];
        $params = $ap;
        if ($box === 'sent') {
            $where[] = 't.created_by = ?';
            $params[] = Auth::id();
        } elseif ($box === 'dept') {
            $where[] = 't.to_department_id = ?';
            $params[] = InternalTicket::myDepartment() ?? 0;
        } elseif ($box === 'all' && Auth::isSuper()) {
            // همه
        } else {
            $box = 'inbox';
            [$iw, $ip] = InternalTicket::inboxWhere();
            $where[] = $iw;
            $params = array_merge($params, $ip);
        }
        if ($st === 'active') {
            $where[] = "t.status IN ('open','in_progress')";
        } elseif ($st === 'done') {
            $where[] = "t.status IN ('done','closed')";
        } else {
            $st = 'all';
        }
        $tickets = DB::all("SELECT t.*, c.name AS creator_name, a.name AS assignee_name,
                                   fd.name AS from_dept_name, fd.color AS from_dept_color, td.name AS to_dept_name, td.color AS to_dept_color,
                                   (SELECT COUNT(*) FROM internal_ticket_replies r WHERE r.ticket_id = t.id AND r.kind = 'reply') AS replies
                            FROM internal_tickets t
                            LEFT JOIN admins c ON c.id = t.created_by LEFT JOIN admins a ON a.id = t.assigned_to
                            LEFT JOIN departments fd ON fd.id = t.from_department_id LEFT JOIN departments td ON td.id = t.to_department_id
                            WHERE " . implode(' AND ', $where) . " ORDER BY FIELD(t.status,'open','in_progress','done','closed'), FIELD(t.priority,'urgent','high','normal','low'), t.id DESC LIMIT 300", $params);
        $this->render('internal/index', [
            'title' => 'تیکت داخلی', 'tickets' => $tickets, 'box' => $box, 'st' => $st,
            'pending' => InternalTicket::pendingCount(),
            'departments' => DB::all('SELECT * FROM departments ORDER BY sort_order, id'),
            'admins' => $this->adminOptions(),
            'projects' => Auth::can('projects') ? DB::all('SELECT id, title FROM projects ORDER BY id DESC LIMIT 300') : [],
            'myDept' => InternalTicket::myDepartment(),
        ]);
    }

    public function show(): void
    {
        $t = $this->ticket();
        $replies = DB::all('SELECT r.*, a.name, d.name AS dept_name FROM internal_ticket_replies r LEFT JOIN admins a ON a.id = r.admin_id LEFT JOIN departments d ON d.id = a.department_id WHERE r.ticket_id = ? ORDER BY r.id', [$t['id']]);
        $this->render('internal/show', [
            'title' => 'تیکت داخلی #' . fa_digits($t['id']),
            'subtitle' => '<a href="' . url('internal') . '">تیکت داخلی</a> / ' . e($t['subject']),
            't' => $t, 'replies' => $replies,
            'departments' => DB::all('SELECT * FROM departments ORDER BY sort_order, id'),
            'admins' => $this->adminOptions(),
        ]);
    }

    public function save(): void
    {
        $this->requirePost();
        $subject = (string)input('subject');
        if ($subject === '') {
            json_error('موضوع تیکت را بنویسید', 422, ['errors' => ['subject' => 'الزامی']]);
        }
        $to = input_int('to_department_id') ?: null;
        $assignee = input_int('assigned_to') ?: null;
        if ($to && !DB::value('SELECT id FROM departments WHERE id = ?', [$to])) {
            json_error('دپارتمان نامعتبر است');
        }
        if ($assignee) {
            $a = DB::one('SELECT id, department_id FROM admins WHERE id = ? AND is_active = 1', [$assignee]);
            if (!$a) {
                json_error('ادمین انتخاب‌شده معتبر نیست');
            }
            $to = $to ?: ($a['department_id'] ? (int)$a['department_id'] : null);
        }
        if (!$to && !$assignee) {
            json_error('دپارتمان مقصد یا یک ادمین را انتخاب کنید', 422, ['errors' => ['to_department_id' => 'الزامی']]);
        }
        $priority = (string)input('priority', 'normal');
        $project = input_int('project_id') ?: null;
        if ($project && !Auth::can('projects')) {
            $project = null;
        }
        $id = DB::insert('internal_tickets', [
            'subject' => mb_substr($subject, 0, 200), 'description' => input('description') ?: null,
            'priority' => in_array($priority, ['low', 'normal', 'high', 'urgent'], true) ? $priority : 'normal',
            'status' => 'open', 'created_by' => Auth::id(), 'from_department_id' => InternalTicket::myDepartment(),
            'to_department_id' => $to, 'assigned_to' => $assignee, 'project_id' => $project, 'created_at' => now(),
        ]);
        Upload::saveMany('files', 'internal_ticket', $id, null);
        $t = InternalTicket::find($id);
        $me = Auth::user()['name'];
        $targets = InternalTicket::participants($t, (int)Auth::id());
        InternalTicket::notify($targets, $t, 'new', $id, 'تیکت داخلی جدید' . ($assignee ? ' برای شما' : ' برای دپارتمان ' . ($t['to_dept_name'] ?? '')),
            "«{$t['subject']}» از طرف {$me}");
        json_ok('تیکت ثبت شد و برای ' . ($assignee ? $t['assignee_name'] : 'دپارتمان ' . $t['to_dept_name']) . ' اعلان رفت', ['redirect' => url('internal/show', ['id' => $id])]);
    }

    public function reply(): void
    {
        $this->requirePost();
        $t = $this->ticket();
        $body = (string)input('body');
        $status = (string)input('status');
        if ($body === '') {
            json_error('متن پیام را بنویسید');
        }
        $rid = DB::insert('internal_ticket_replies', ['ticket_id' => $t['id'], 'admin_id' => Auth::id(), 'kind' => 'reply', 'body' => $body, 'created_at' => now()]);
        $upd = ['updated_at' => now()];
        $statusNote = '';
        if (in_array($status, self::STATUSES, true) && $status !== $t['status']) {
            $upd['status'] = $status;
            $upd['closed_at'] = in_array($status, ['done', 'closed'], true) ? now() : null;
            InternalTicket::addSystem((int)$t['id'], 'وضعیت به «' . internal_status_label($status) . '» تغییر کرد');
            $statusNote = ' · وضعیت: ' . internal_status_label($status);
        } elseif ($t['status'] === 'open' && (int)$t['assigned_to'] === Auth::id()) {
            $upd['status'] = 'in_progress';
        }
        DB::update('internal_tickets', $upd, 'id = ?', [$t['id']]);
        Upload::saveMany('files', 'internal_ticket', (int)$t['id']);
        $me = Auth::user()['name'];
        InternalTicket::notify(InternalTicket::participants($t, (int)Auth::id()), $t, 'reply', $rid, 'پاسخ جدید در تیکت «' . $t['subject'] . '»', "{$me}: " . $body . $statusNote);
        json_ok('پیام ثبت شد');
    }

    public function assign(): void
    {
        $this->requirePost();
        $t = $this->ticket();
        $assignee = input_int('assigned_to') ?: null;
        $to = input_int('to_department_id') ?: null;
        $new = null;
        if ($assignee) {
            $new = DB::one('SELECT id, name, department_id FROM admins WHERE id = ? AND is_active = 1', [$assignee]);
            if (!$new) {
                json_error('ادمین انتخاب‌شده معتبر نیست');
            }
            $to = $to ?: ($new['department_id'] ? (int)$new['department_id'] : ((int)$t['to_department_id'] ?: null));
        }
        if ($to && !DB::value('SELECT id FROM departments WHERE id = ?', [$to])) {
            json_error('دپارتمان نامعتبر است');
        }
        if (!$to && !$assignee) {
            json_error('دپارتمان یا ادمین را انتخاب کنید');
        }
        DB::update('internal_tickets', ['assigned_to' => $assignee, 'to_department_id' => $to, 'updated_at' => now(), 'status' => $t['status'] === 'closed' ? 'open' : $t['status'], 'closed_at' => null], 'id = ?', [$t['id']]);
        $deptName = $to ? DB::value('SELECT name FROM departments WHERE id = ?', [$to]) : '—';
        $text = 'ارجاع شد به ' . ($new ? $new['name'] . ' (' . $deptName . ')' : 'دپارتمان ' . $deptName);
        InternalTicket::addSystem((int)$t['id'], $text . ' توسط ' . Auth::user()['name']);
        $t2 = InternalTicket::find((int)$t['id']);
        $targets = $new ? [(int)$new['id']] : InternalTicket::participants($t2, (int)Auth::id());
        $targets = array_values(array_diff($targets, [(int)Auth::id()]));
        InternalTicket::notify($targets, $t2, 'assign', (int)DB::value('SELECT MAX(id) FROM internal_ticket_replies'), 'تیکت داخلی به شما ارجاع شد', "«{$t['subject']}» توسط " . Auth::user()['name']);
        json_ok('ارجاع انجام شد و اعلان ارسال شد');
    }

    public function status(): void
    {
        $this->requirePost();
        $t = $this->ticket();
        $status = (string)input('status');
        if (!in_array($status, self::STATUSES, true)) {
            json_error('وضعیت نامعتبر است');
        }
        if ($status === $t['status']) {
            json_ok('بدون تغییر');
        }
        DB::update('internal_tickets', ['status' => $status, 'updated_at' => now(), 'closed_at' => in_array($status, ['done', 'closed'], true) ? now() : null], 'id = ?', [$t['id']]);
        InternalTicket::addSystem((int)$t['id'], 'وضعیت به «' . internal_status_label($status) . '» تغییر کرد توسط ' . Auth::user()['name']);
        $eid = (int)DB::value('SELECT MAX(id) FROM internal_ticket_replies');
        InternalTicket::notify(InternalTicket::participants($t, (int)Auth::id()), $t, 'status', $eid, 'تغییر وضعیت تیکت «' . $t['subject'] . '»', 'وضعیت جدید: ' . internal_status_label($status) . ' — ' . Auth::user()['name']);
        json_ok('وضعیت تیکت تغییر کرد');
    }

    public function delete(): void
    {
        $this->requirePost();
        $this->requireSuper();
        $t = $this->findOr404('internal_tickets', input_int('id'));
        Upload::deleteFor('internal_ticket', [(int)$t['id']]);
        DB::query("DELETE FROM notifications WHERE ref_type = 'internal_ticket' AND ref_id = ?", [$t['id']]);
        DB::delete('internal_tickets', 'id = ?', [$t['id']]);
        json_ok('تیکت حذف شد', ['redirect' => url('internal')]);
    }

    private function ticket(): array
    {
        $t = InternalTicket::find(input_int('id') ?: input_int('ticket_id'));
        if (!$t || !InternalTicket::canAccess($t)) {
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                json_error('تیکت پیدا نشد یا دسترسی ندارید', 404);
            }
            Router::notFound();
        }
        return $t;
    }

    private function adminOptions(): array
    {
        return DB::all('SELECT a.id, a.name, a.department_id, d.name AS dept_name FROM admins a LEFT JOIN departments d ON d.id = a.department_id WHERE a.is_active = 1 ORDER BY d.sort_order, a.name');
    }
}
