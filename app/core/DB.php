<?php
namespace App\Core;

use PDO;
use PDOStatement;

defined('APP_ROOT') or exit('No direct access');

/** لایه سبک دیتابیس روی PDO با Prepared Statement */
class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c   = config('db');
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";
            try {
                self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                self::$pdo->exec("SET time_zone = '+03:30'");
            } catch (\PDOException $e) {
                http_response_code(500);
                $msg = config('debug') ? e($e->getMessage()) : '';
                exit('<div dir="rtl" style="font-family:tahoma;padding:40px;background:#0f172a;color:#e2e8f0;min-height:100vh">'
                    . '<h2>اتصال به دیتابیس برقرار نشد</h2><p>ابتدا فایل <code>database/schema.sql</code> را در phpMyAdmin ایمپورت کنید '
                    . 'و تنظیمات <code>config/config.php</code> را بررسی کنید.</p><pre style="direction:ltr;color:#f97316">' . $msg . '</pre></div>');
            }
        }
        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function value(string $sql, array $params = [])
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql  = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        self::query($sql, array_values($data));
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(',', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $st  = self::query("UPDATE `$table` SET $set WHERE $where", array_merge(array_values($data), $whereParams));
        return $st->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query("DELETE FROM `$table` WHERE $where", $params)->rowCount();
    }

    public static function transaction(callable $fn)
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $r = $fn();
            $pdo->commit();
            return $r;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** ساخت placeholder برای IN (...) */
    public static function in(array $values): string
    {
        return implode(',', array_fill(0, max(1, count($values)), '?'));
    }
}
