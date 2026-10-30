<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * احراز هویت و سطح دسترسی
 * سطح‌ها: 0 = بدون دسترسی، 1 = فقط مشاهده، 2 = مدیریت (ثبت/ویرایش/حذف)
 * مدیر کل (is_super) به همه بخش‌ها دسترسی کامل دارد.
 */
class Auth
{
    public const NONE = 0;
    public const VIEW = 1;
    public const MANAGE = 2;

    public const SECTIONS = [
        'customers'  => ['مشتریان', 'bi-people'],
        'projects'   => ['پروژه‌ها', 'bi-kanban'],
        'finance'    => ['مالی (پرداخت‌ها و هزینه‌ها)', 'bi-cash-coin'],
        'support'    => ['پشتیبانی و تیکت‌ها', 'bi-headset'],
        'reports'    => ['گزارش‌ها', 'bi-graph-up-arrow'],
        'categories' => ['دسته‌بندی‌ها', 'bi-tags'],
    ];

    private static ?array $user = null;
    private static ?array $perms = null;

    public static function attempt(string $username, string $password): bool
    {
        $u = DB::one('SELECT * FROM admins WHERE username = ? AND is_active = 1', [$username]);
        if (!$u || !password_verify($password, $u['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$u['id'];
        DB::update('admins', ['last_login_at' => now()], 'id = ?', [$u['id']]);
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            DB::update('admins', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = ?', [$u['id']]);
        }
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && !empty($_SESSION['admin_id'])) {
            self::$user = DB::one('SELECT id, name, username, is_super, is_active FROM admins WHERE id = ? AND is_active = 1', [$_SESSION['admin_id']]);
            if (!self::$user) {
                unset($_SESSION['admin_id']);
            }
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function isSuper(): bool
    {
        return !empty(self::user()['is_super']);
    }

    public static function level(string $section): int
    {
        if (self::isSuper() || (in_array($section, ['internal', 'shares'], true) && self::check())) {
            return self::MANAGE;
        }
        if (self::$perms === null) {
            self::$perms = [];
            if (self::id()) {
                foreach (DB::all('SELECT section, level FROM admin_permissions WHERE admin_id = ?', [self::id()]) as $p) {
                    self::$perms[$p['section']] = (int)$p['level'];
                }
            }
        }
        return self::$perms[$section] ?? self::NONE;
    }

    public static function can(string $section, int $level = self::VIEW): bool
    {
        return self::level($section) >= $level;
    }

    public static function canManage(string $section): bool
    {
        return self::can($section, self::MANAGE);
    }
}
