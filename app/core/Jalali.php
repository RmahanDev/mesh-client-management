<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * تبدیل تاریخ شمسی/میلادی (الگوریتم jdf)
 * در دیتابیس تاریخ‌ها به‌صورت استاندارد DATE ذخیره می‌شوند تا محاسبه روزشمار و گزارش دقیق باشد؛
 * اما همه ورودی‌ها و نمایش‌ها فقط شمسی است.
 */
class Jalali
{
    public const MONTHS = [1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2   = ($gm > 2) ? ($gy + 1) : $gy;
        $days  = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
        $jy    = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy   += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy  += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }

    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy  += 1595;
        $days = -355668 + (365 * $jy) + (intdiv($jy, 33) * 8) + intdiv(($jy % 33) + 3, 4) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy   = 400 * intdiv($days, 146097);
        $days %= 146097;
        if ($days > 36524) {
            $days--;
            $gy  += 100 * intdiv($days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy  += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy  += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        $gd    = $days + 1;
        $leap  = ($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0);
        $sal_a = [0, 31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($gm = 0; $gm < 13 && $gd > $sal_a[$gm]; $gm++) {
            $gd -= $sal_a[$gm];
        }
        return [$gy, $gm, $gd];
    }

    public static function isLeap(int $jy): bool
    {
        [$gy, $gm, $gd] = self::toGregorian($jy, 12, 30);
        return self::toJalali($gy, $gm, $gd) === [$jy, 12, 30];
    }

    public static function monthLength(int $jy, int $jm): int
    {
        if ($jm <= 6) {
            return 31;
        }
        if ($jm <= 11) {
            return 30;
        }
        return self::isLeap($jy) ? 30 : 29;
    }

    /** Y-m-d (میلادی) → «1404/08/08» */
    public static function format(string $date, bool $withTime = false): string
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }
        [$jy, $jm, $jd] = self::toJalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
        $out = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        if ($withTime) {
            $out .= ' ' . date('H:i', $ts);
        }
        return $out;
    }

    /** «۱۴۰۴/۰۸/۰۸» یا «1404-8-8» → Y-m-d میلادی ؛ نامعتبر = null */
    public static function parse(string $value): ?string
    {
        $value = trim(to_en_digits($value));
        if ($value === '') {
            return null;
        }
        if (!preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $value, $m)) {
            return null;
        }
        [$jy, $jm, $jd] = [(int)$m[1], (int)$m[2], (int)$m[3]];
        if ($jy < 1300 || $jy > 1500 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > self::monthLength($jy, $jm)) {
            return null;
        }
        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** افزودن ماه شمسی به یک تاریخ میلادی (مثلاً برای اقساط و پشتیبانی) */
    public static function addMonths(string $gDate, int $months): string
    {
        $ts = strtotime($gDate);
        [$jy, $jm, $jd] = self::toJalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
        $total = $jy * 12 + ($jm - 1) + $months;
        $jy    = intdiv($total, 12);
        $jm    = $total % 12 + 1;
        $jd = min($jd, self::monthLength($jy, $jm));
        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** [سال, ماه] شمسی یک تاریخ میلادی */
    public static function yearMonth(string $gDate): array
    {
        $ts = strtotime($gDate);
        [$jy, $jm] = self::toJalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
        return [$jy, $jm];
    }

    /** بازه میلادی ابتدا/انتهای یک ماه شمسی */
    public static function monthRange(int $jy, int $jm): array
    {
        [$a, $b, $c] = self::toGregorian($jy, $jm, 1);
        [$d, $e, $f] = self::toGregorian($jy, $jm, self::monthLength($jy, $jm));
        return [sprintf('%04d-%02d-%02d', $a, $b, $c), sprintf('%04d-%02d-%02d', $d, $e, $f)];
    }

    public static function monthName(int $jm): string
    {
        return self::MONTHS[$jm] ?? '';
    }
}
