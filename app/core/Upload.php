<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * آپلود امن پیوست‌ها در پوشه خصوصی (بدون دسترسی مستقیم از مرورگر)
 * فایل‌ها با نام تصادفی ذخیره و فقط از مسیر files/view با بررسی دسترسی نمایش داده می‌شوند.
 */
class Upload
{
    public const ENTITIES = [
        'customer'   => ['customers', 'customers'],
        'project'    => ['projects', 'projects'],
        'payment'    => ['payments', 'finance'],
        'promissory' => ['promissory_notes', 'finance'],
        'guarantor'  => ['guarantors', 'finance'],
        'expense'    => ['expenses', 'finance'],
        'ticket'     => ['tickets', 'support'],
        'renewal'    => ['support_renewals', 'support'],
        'internal_ticket' => ['internal_tickets', 'internal'],
    ];

    public static function sectionFor(string $entity): ?string
    {
        return self::ENTITIES[$entity][1] ?? null;
    }

    /**
     * ذخیره همه فایل‌های یک فیلد input[type=file][multiple]
     * @return array{saved:int, errors:string[]}
     */
    public static function saveMany(string $field, string $entity, int $entityId, ?string $title = null): array
    {
        $result = ['saved' => 0, 'errors' => []];
        if (empty($_FILES[$field]) || !isset(self::ENTITIES[$entity])) {
            return $result;
        }
        $files = self::normalize($_FILES[$field]);
        foreach ($files as $f) {
            if ($f['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $err = self::saveOne($f, $entity, $entityId, $title);
            if ($err) {
                $result['errors'][] = $err;
            } else {
                $result['saved']++;
            }
        }
        return $result;
    }

    private static function normalize(array $f): array
    {
        if (!is_array($f['name'])) {
            return [$f];
        }
        $out = [];
        foreach ($f['name'] as $i => $name) {
            $out[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
        return $out;
    }

    private static function saveOne(array $f, string $entity, int $entityId, ?string $title): ?string
    {
        $name = basename($f['name']);
        if ($f['error'] !== UPLOAD_ERR_OK) {
            return "خطا در آپلود «{$name}»";
        }
        $maxMb = (int)Settings::get('upload_max_mb', config('upload.max_size_mb'));
        if ($f['size'] > $maxMb * 1048576) {
            return "حجم «{$name}» بیشتر از {$maxMb} مگابایت است";
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, config('upload.allowed_ext'), true)) {
            return "نوع فایل «{$name}» مجاز نیست";
        }
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $fi   = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fi, $f['tmp_name']) ?: $mime;
            finfo_close($fi);
        }
        if (!in_array($mime, config('upload.allowed_mime'), true)) {
            return "محتوای فایل «{$name}» معتبر نیست";
        }
        $sub  = date('Y/m');
        $dir  = rtrim(config('storage_path'), '/\\') . '/' . $sub;
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            return 'پوشه ذخیره‌سازی قابل ساخت نیست';
        }
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $stored)) {
            return "ذخیره «{$name}» ناموفق بود";
        }
        DB::insert('attachments', [
            'entity_type' => $entity, 'entity_id' => $entityId, 'title' => $title ?: null,
            'original_name' => mb_substr($name, 0, 250), 'stored_path' => $sub . '/' . $stored,
            'mime' => $mime, 'size' => (int)$f['size'], 'uploaded_by' => Auth::id(), 'created_at' => now(),
        ]);
        return null;
    }

    public static function path(array $att): string
    {
        return rtrim(config('storage_path'), '/\\') . '/' . $att['stored_path'];
    }

    public static function list(string $entity, int $id): array
    {
        return DB::all('SELECT * FROM attachments WHERE entity_type = ? AND entity_id = ? ORDER BY id DESC', [$entity, $id]);
    }

    /** حذف پیوست‌های یک موجودیت (هنگام حذف رکورد) */
    public static function deleteFor(string $entity, array $ids): void
    {
        if (!$ids) {
            return;
        }
        $rows = DB::all('SELECT * FROM attachments WHERE entity_type = ? AND entity_id IN (' . DB::in($ids) . ')', array_merge([$entity], $ids));
        foreach ($rows as $r) {
            @unlink(self::path($r));
        }
        DB::query('DELETE FROM attachments WHERE entity_type = ? AND entity_id IN (' . DB::in($ids) . ')', array_merge([$entity], $ids));
    }

    public static function isImage(string $mime): bool
    {
        return str_starts_with($mime, 'image/');
    }
}
