<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * ساخت نوتیفیکیشن‌های داخل پنل (بدون نیاز به Cron):
 * در هر بار باز شدن پنل (حداکثر هر ۱۰ دقیقه) سررسیدها و پایان پشتیبانی‌ها بررسی می‌شوند.
 */
class Notifier
{
    public static function refresh(bool $force = false): void
    {
        $last = (int)($_SESSION['_notif_check'] ?? 0);
        if (!$force && time() - $last < 600) {
            return;
        }
        $_SESSION['_notif_check'] = time();

        $days  = (int)Settings::get('reminder_days', 7);
        $today = today();
        $limit = date('Y-m-d', strtotime("+$days days"));

        // اقساط/مراحل نزدیک سررسید
        $rows = DB::all(
            "SELECT p.id, p.title, p.amount, p.paid_amount, p.due_date, pr.id AS project_id, pr.title AS project_title,
                    CONCAT(c.first_name,' ',c.last_name) AS customer
             FROM payments p JOIN projects pr ON pr.id = p.project_id JOIN customers c ON c.id = pr.customer_id
             WHERE p.status <> 'paid' AND p.due_date IS NOT NULL AND p.due_date BETWEEN ? AND ? AND pr.status <> 'canceled'",
            [$today, $limit]
        );
        foreach ($rows as $r) {
            $left = days_until($r['due_date']);
            $when = $left === 0 ? 'امروز' : fa_digits($left) . ' روز دیگر';
            self::add('payment_due', 'finance', 'payment', (int)$r['id'], 'due:' . $r['id'] . ':' . $r['due_date'],
                "سررسید پرداخت {$when}",
                "«{$r['title']}» پروژه «{$r['project_title']}» ({$r['customer']}) به مبلغ " . money_plain($r['amount'] - $r['paid_amount']) . ' در تاریخ ' . jdate($r['due_date']),
                url('projects/show', ['id' => $r['project_id'], 'tab' => 'payments']));
        }

        // معوقه‌ها
        $rows = DB::all(
            "SELECT p.id, p.title, p.amount, p.paid_amount, p.due_date, pr.id AS project_id, pr.title AS project_title,
                    CONCAT(c.first_name,' ',c.last_name) AS customer
             FROM payments p JOIN projects pr ON pr.id = p.project_id JOIN customers c ON c.id = pr.customer_id
             WHERE p.status <> 'paid' AND p.due_date IS NOT NULL AND p.due_date < ? AND pr.status <> 'canceled'",
            [$today]
        );
        foreach ($rows as $r) {
            self::add('payment_overdue', 'finance', 'payment', (int)$r['id'], 'overdue:' . $r['id'] . ':' . $r['due_date'],
                'پرداخت معوق شده',
                "«{$r['title']}» پروژه «{$r['project_title']}» ({$r['customer']}) از تاریخ " . jdate($r['due_date']) . ' پرداخت نشده است.',
                url('projects/show', ['id' => $r['project_id'], 'tab' => 'payments']));
        }

        // پایان پشتیبانی
        $rows = DB::all(
            "SELECT pr.id, pr.title, pr.support_end, CONCAT(c.first_name,' ',c.last_name) AS customer
             FROM projects pr JOIN customers c ON c.id = pr.customer_id
             WHERE pr.support_end IS NOT NULL AND pr.support_end BETWEEN ? AND ? AND pr.status <> 'canceled'",
            [$today, $limit]
        );
        foreach ($rows as $r) {
            $left = days_until($r['support_end']);
            self::add('support_ending', 'support', 'project', (int)$r['id'], 'support:' . $r['id'] . ':' . $r['support_end'],
                'پشتیبانی رو به پایان است',
                "پشتیبانی پروژه «{$r['title']}» ({$r['customer']}) " . ($left === 0 ? 'امروز' : fa_digits($left) . ' روز دیگر') . ' تمام می‌شود. پیشنهاد تمدید بدهید.',
                url('projects/show', ['id' => $r['id'], 'tab' => 'support']));
        }
    }

    /** $adminId: اعلان اختصاصی یک ادمین (مثل تیکت داخلی)؛ null = عمومی طبق دسترسی بخش */
    public static function add(string $type, string $section, string $refType, int $refId, string $key, string $title, string $body, string $link, ?int $adminId = null): void
    {
        DB::query(
            'INSERT IGNORE INTO notifications (type, section, admin_id, ref_type, ref_id, uniq_key, title, body, link, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$type, $section, $adminId, $refType, $refId, $key, $title, $body, $link, now()]
        );
    }

    /** بخش‌هایی که کاربر فعلی می‌تواند نوتیف‌شان را ببیند */
    private static function sections(): array
    {
        return array_values(array_filter(['finance', 'support', 'projects', 'customers'], fn($s) => Auth::can($s)));
    }

    public static function unreadCount(): int
    {
        $s = self::sections() ?: ['-'];
        return (int)DB::value(
            'SELECT COUNT(*) FROM notifications n LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.admin_id = ?
             WHERE r.notification_id IS NULL AND ((n.admin_id IS NULL AND n.section IN (' . DB::in($s) . ')) OR n.admin_id = ?)',
            array_merge([Auth::id()], $s, [Auth::id()])
        );
    }

    public static function latest(int $limit = 8, bool $onlyUnread = false): array
    {
        $s = self::sections() ?: ['-'];
        $where = $onlyUnread ? 'AND r.notification_id IS NULL' : '';
        return DB::all(
            "SELECT n.*, (r.notification_id IS NOT NULL) AS is_read FROM notifications n
             LEFT JOIN notification_reads r ON r.notification_id = n.id AND r.admin_id = ?
             WHERE ((n.admin_id IS NULL AND n.section IN (" . DB::in($s) . ")) OR n.admin_id = ?) $where ORDER BY n.id DESC LIMIT " . (int)$limit,
            array_merge([Auth::id()], $s, [Auth::id()])
        );
    }
}
