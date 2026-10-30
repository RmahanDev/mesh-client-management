<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Notifier;

defined('APP_ROOT') or exit('No direct access');

/** تیکت داخلی بین ادمین‌ها و دپارتمان‌ها */
class InternalTicket
{
    private static ?int $myDept = null;
    private static bool $myDeptLoaded = false;

    public static function myDepartment(): ?int
    {
        if (!self::$myDeptLoaded) {
            $v = DB::value('SELECT department_id FROM admins WHERE id = ?', [Auth::id()]);
            self::$myDept = $v ? (int)$v : null;
            self::$myDeptLoaded = true;
        }
        return self::$myDept;
    }

    public static function find(int $id): ?array
    {
        return DB::one("SELECT t.*, c.name AS creator_name, a.name AS assignee_name,
                               fd.name AS from_dept_name, fd.color AS from_dept_color, td.name AS to_dept_name, td.color AS to_dept_color,
                               p.title AS project_title
                        FROM internal_tickets t
                        LEFT JOIN admins c ON c.id = t.created_by
                        LEFT JOIN admins a ON a.id = t.assigned_to
                        LEFT JOIN departments fd ON fd.id = t.from_department_id
                        LEFT JOIN departments td ON td.id = t.to_department_id
                        LEFT JOIN projects p ON p.id = t.project_id
                        WHERE t.id = ?", [$id]);
    }

    /** مدیر کل، ایجادکننده، مسئول و اعضای دپارتمان مقصد */
    public static function canAccess(array $t): bool
    {
        if (Auth::isSuper()) {
            return true;
        }
        $me = Auth::id();
        $dept = self::myDepartment();
        return (int)$t['created_by'] === $me || (int)$t['assigned_to'] === $me
            || ($dept !== null && (int)$t['to_department_id'] === $dept);
    }

    /** اجازه ارجاع / تغییر وضعیت: همه شرکت‌کنندگان به جز کسی که فقط ایجادکننده است و دسترسی مقصد ندارد؟ ساده: همان دسترسی مشاهده */
    public static function canHandle(array $t): bool
    {
        return self::canAccess($t);
    }

    /** شرط SQL دسترسی برای لیست‌ها [sql, params] */
    public static function accessWhere(string $alias = 't'): array
    {
        if (Auth::isSuper()) {
            return ['1', []];
        }
        return ["({$alias}.created_by = ? OR {$alias}.assigned_to = ? OR {$alias}.to_department_id = ?)", [Auth::id(), Auth::id(), self::myDepartment() ?? 0]];
    }

    /** شرط «کار روی میز من»: مسئول من، یا بدون مسئول و متعلق به دپارتمان من */
    public static function inboxWhere(string $alias = 't'): array
    {
        return ["({$alias}.assigned_to = ? OR ({$alias}.assigned_to IS NULL AND {$alias}.to_department_id = ?))", [Auth::id(), self::myDepartment() ?? 0]];
    }

    public static function pendingCount(): int
    {
        [$w, $p] = self::inboxWhere();
        return (int)DB::value("SELECT COUNT(*) FROM internal_tickets t WHERE $w AND t.status IN ('open','in_progress')", $p);
    }

    public static function inbox(int $limit = 6): array
    {
        [$w, $p] = self::inboxWhere();
        return DB::all("SELECT t.*, c.name AS creator_name FROM internal_tickets t LEFT JOIN admins c ON c.id = t.created_by
                        WHERE $w AND t.status IN ('open','in_progress') ORDER BY FIELD(t.priority,'urgent','high','normal','low'), t.id DESC LIMIT " . (int)$limit, $p);
    }

    /** شناسه ادمین‌هایی که باید از رویداد روی تیکت خبردار شوند (به جز $except) */
    public static function participants(array $t, int $except): array
    {
        $ids = [];
        if ($t['created_by']) {
            $ids[] = (int)$t['created_by'];
        }
        if ($t['assigned_to']) {
            $ids[] = (int)$t['assigned_to'];
        } elseif ($t['to_department_id']) {
            foreach (DB::all('SELECT id FROM admins WHERE department_id = ? AND is_active = 1', [$t['to_department_id']]) as $r) {
                $ids[] = (int)$r['id'];
            }
        }
        foreach (DB::all('SELECT DISTINCT admin_id FROM internal_ticket_replies WHERE ticket_id = ? AND admin_id IS NOT NULL AND kind = ?', [$t['id'], 'reply']) as $r) {
            $ids[] = (int)$r['admin_id'];
        }
        $ids = array_values(array_unique(array_filter($ids, fn($i) => $i !== $except)));
        if (!$ids) {
            return [];
        }
        return array_map('intval', array_column(DB::all('SELECT id FROM admins WHERE is_active = 1 AND id IN (' . DB::in($ids) . ')', $ids), 'id'));
    }

    /** ساخت اعلان اختصاصی برای هر ادمین */
    public static function notify(array $adminIds, array $t, string $event, int $eventId, string $title, string $body): void
    {
        foreach ($adminIds as $aid) {
            Notifier::add('internal_ticket', 'internal', 'internal_ticket', (int)$t['id'], "it:{$t['id']}:{$aid}:{$event}:{$eventId}",
                $title, mb_substr($body, 0, 300), url('internal/show', ['id' => $t['id']]), (int)$aid);
        }
    }

    public static function addSystem(int $ticketId, string $text): void
    {
        DB::insert('internal_ticket_replies', ['ticket_id' => $ticketId, 'admin_id' => Auth::id(), 'kind' => 'system', 'body' => $text, 'created_at' => now()]);
    }
}
