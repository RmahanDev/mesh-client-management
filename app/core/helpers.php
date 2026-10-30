<?php
defined('APP_ROOT') or exit('No direct access');

use App\Core\Jalali;

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $k) {
        if (!is_array($value) || !array_key_exists($k, $value)) {
            return $default;
        }
        $value = $value[$k];
    }
    return $value;
}

/** فرار دادن خروجی HTML */
function e($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_url(): string
{
    static $base = null;
    if ($base === null) {
        $dir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }
    return $base;
}

/** ساخت آدرس یک مسیر: url('customers/show', ['id'=>1]) */
function url(string $route = 'dashboard', array $params = []): string
{
    $q = array_merge(['r' => $route], $params);
    return base_url() . '/index.php?' . http_build_query($q);
}

function asset(string $path): string
{
    $file = APP_ROOT . '/assets/' . $path;
    $v    = is_file($file) ? filemtime($file) : 1;
    return base_url() . '/assets/' . $path . '?v=' . $v;
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_ok(string $message = 'با موفقیت ذخیره شد', array $extra = []): never
{
    json_response(array_merge(['ok' => true, 'message' => $message], $extra));
}

function json_error(string $message, int $code = 422, array $extra = []): never
{
    json_response(array_merge(['ok' => false, 'message' => $message], $extra), $code);
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        if (is_ajax()) {
            json_error('نشست شما منقضی شده است. صفحه را رفرش کنید.', 419);
        }
        http_response_code(419);
        exit('CSRF token mismatch');
    }
}

/* ---------------- Flash ---------------- */

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* ---------------- Input ---------------- */

function to_en_digits(string $s): string
{
    $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    return str_replace($fa, $en, $s);
}

function fa_digits($s): string
{
    return str_replace(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], (string)$s);
}

/** مقدار ورودی تمیز‌شده (trim) */
function input(string $key, $default = null)
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (is_string($v)) {
        $v = trim($v);
    }
    return $v;
}

function input_int(string $key, int $default = 0): int
{
    $v = input($key);
    if ($v === null || $v === '') {
        return $default;
    }
    return (int)to_en_digits((string)$v);
}

/** تبدیل «۱۲,۵۰۰,۰۰۰» به عدد صحیح */
function parse_money($v): int
{
    $v = to_en_digits((string)$v);
    $v = preg_replace('/[^\d\-]/', '', $v);
    return (int)($v ?: 0);
}

/** نمایش مبلغ به تومان */
function money($amount, bool $withUnit = true): string
{
    $s = fa_digits(number_format((int)$amount));
    return $withUnit ? $s . ' <small class="text-body-secondary">تومان</small>' : $s;
}

function money_plain($amount): string
{
    return fa_digits(number_format((int)$amount)) . ' تومان';
}

/** نمایش درصد با حذف صفرهای اضافه اعشار */
function percent_label($percent): string
{
    return fa_digits(rtrim(rtrim(number_format((float)$percent, 2, '.', ''), '0'), '.')) . '٪';
}

function num($n): string
{
    return fa_digits(number_format((int)$n));
}

/* ---------------- Date ---------------- */

/** نمایش تاریخ شمسی از Y-m-d یا datetime */
function jdate(?string $date, bool $withTime = false): string
{
    if (!$date || str_starts_with($date, '0000')) {
        return '—';
    }
    return fa_digits(Jalali::format($date, $withTime));
}

/** تاریخ شمسی برای مقدار input (با رقم انگلیسی) */
function jdate_input(?string $date): string
{
    if (!$date || str_starts_with($date, '0000')) {
        return '';
    }
    return Jalali::format($date);
}

/** تبدیل ورودی شمسی کاربر به Y-m-d میلادی برای ذخیره */
function input_date(string $key): ?string
{
    $v = input($key);
    return Jalali::parse(is_string($v) ? $v : '');
}

function today(): string
{
    return date('Y-m-d');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** اختلاف روز تا یک تاریخ (منفی یعنی گذشته) */
function days_until(?string $date): ?int
{
    if (!$date) {
        return null;
    }
    $a = new DateTimeImmutable(today());
    $b = new DateTimeImmutable(substr($date, 0, 10));
    return (int)$a->diff($b)->format('%r%a');
}

/* ---------------- Labels ---------------- */

function payment_type_label(string $t): string
{
    return ['single' => 'یک‌جا', 'stages' => 'چندمرحله‌ای', 'installment' => 'اقساطی'][$t] ?? $t;
}

function project_status_label(string $s): string
{
    return [
        'pending' => 'در انتظار شروع', 'active' => 'در حال انجام',
        'delivered' => 'تحویل شده', 'canceled' => 'لغو شده',
    ][$s] ?? $s;
}

function project_status_color(string $s): string
{
    return ['pending' => 'secondary', 'active' => 'primary', 'delivered' => 'success', 'canceled' => 'danger'][$s] ?? 'secondary';
}

function ticket_status_label(string $s): string
{
    return ['open' => 'باز', 'in_progress' => 'در حال بررسی', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته'][$s] ?? $s;
}

function ticket_status_color(string $s): string
{
    return ['open' => 'warning', 'in_progress' => 'info', 'answered' => 'primary', 'closed' => 'secondary'][$s] ?? 'secondary';
}

function priority_label(string $p): string
{
    return ['low' => 'کم', 'normal' => 'معمولی', 'high' => 'زیاد', 'urgent' => 'فوری'][$p] ?? $p;
}

function priority_color(string $p): string
{
    return ['low' => 'secondary', 'normal' => 'info', 'high' => 'warning', 'urgent' => 'danger'][$p] ?? 'secondary';
}

function contact_types(): array
{
    return [
        'mobile' => ['موبایل', 'bi-phone'], 'phone' => ['تلفن ثابت', 'bi-telephone'],
        'email' => ['ایمیل', 'bi-envelope'], 'telegram' => ['تلگرام', 'bi-telegram'],
        'whatsapp' => ['واتس‌اپ', 'bi-whatsapp'], 'instagram' => ['اینستاگرام', 'bi-instagram'],
        'eitaa' => ['ایتا', 'bi-chat-dots'], 'bale' => ['بله', 'bi-chat-square-dots'],
        'website' => ['وب‌سایت', 'bi-globe'], 'address' => ['آدرس', 'bi-geo-alt'], 'other' => ['سایر', 'bi-three-dots'],
    ];
}

function payment_method_label(?string $m): string
{
    return [
        'card' => 'کارت به کارت', 'transfer' => 'انتقال بانکی (پایا/ساتنا)', 'cash' => 'نقدی',
        'pos' => 'کارتخوان', 'cheque' => 'چک', 'online' => 'درگاه آنلاین', 'other' => 'سایر',
    ][$m ?? ''] ?? '—';
}

function json_attr($data): string
{
    return e(json_encode($data, JSON_UNESCAPED_UNICODE));
}

function human_size(int $bytes): string
{
    if ($bytes < 1024) {
        return fa_digits($bytes) . ' بایت';
    }
    if ($bytes < 1048576) {
        return fa_digits(round($bytes / 1024, 1)) . ' کیلوبایت';
    }
    return fa_digits(round($bytes / 1048576, 1)) . ' مگابایت';
}

/* ---------------- Modal helpers ---------------- */

/** شروع یک مودال فرم (همه فرم‌های سایت داخل مودال و با AJAX ارسال می‌شوند) */
function modal_start(string $id, string $title, string $icon, string $action, string $size = '', array $hidden = []): string
{
    $h = '<div class="modal fade" id="' . e($id) . '" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable ' . e($size) . '"><div class="modal-content">'
        . '<form class="ajax-form" method="post" action="' . e($action) . '" enctype="multipart/form-data">' . csrf_field();
    foreach ($hidden as $name => $value) {
        $h .= '<input type="hidden" name="' . e($name) . '" value="' . e($value) . '"' . ($value !== '' ? ' data-fixed' : '') . '>';
    }
    return $h . '<div class="modal-header"><h5 class="modal-title"><i class="bi ' . e($icon) . '"></i><span>' . e($title) . '</span></h5>'
        . '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
        . '<div class="modal-body"><div class="alert alert-danger form-alert d-none"></div>';
}

function modal_end(string $submit = 'ذخیره', string $btnClass = 'btn-grad'): string
{
    return '</div><div class="modal-footer"><button type="button" class="btn btn-soft" data-bs-dismiss="modal">انصراف</button>'
        . '<button type="submit" class="btn ' . e($btnClass) . '"><i class="bi bi-check2 me-1"></i>' . e($submit) . '</button></div></form></div></div></div>';
}

function method_options(?string $selected = null): string
{
    $h = '';
    foreach (['card', 'transfer', 'cash', 'pos', 'cheque', 'online', 'other'] as $m) {
        $h .= '<option value="' . $m . '"' . ($selected === $m ? ' selected' : '') . '>' . payment_method_label($m) . '</option>';
    }
    return $h;
}

function internal_status_label(string $s): string
{
    return ['open' => 'باز', 'in_progress' => 'در حال انجام', 'done' => 'انجام شد', 'closed' => 'بسته'][$s] ?? $s;
}

function internal_status_color(string $s): string
{
    return ['open' => 'warning', 'in_progress' => 'info', 'done' => 'success', 'closed' => 'secondary'][$s] ?? 'secondary';
}
