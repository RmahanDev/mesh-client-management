<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

class Settings
{
    private static ?array $cache = null;

    public static function get(string $key, $default = null)
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (DB::all('SELECT `key`, `value` FROM settings') as $r) {
                self::$cache[$r['key']] = $r['value'];
            }
        }
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        DB::query('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$key, (string)$value]);
        self::$cache[$key] = (string)$value;
    }
}
