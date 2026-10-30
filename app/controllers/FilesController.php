<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;
use App\Core\Router;
use App\Core\Upload;
use App\Models\InternalTicket;

defined('APP_ROOT') or exit('No direct access');

/** نمایش/دانلود/آپلود/حذف پیوست‌ها با بررسی سطح دسترسی */
class FilesController extends Controller
{
    public function view(): void
    {
        $a = DB::one('SELECT * FROM attachments WHERE id = ?', [input_int('id')]);
        if (!$a) {
            Router::notFound();
        }
        $this->authorizeAttachment($a['entity_type'], (int)$a['entity_id'], Auth::VIEW);
        $path = Upload::path($a);
        if (!is_file($path)) {
            Router::notFound();
        }
        $inline = !input('download') && (Upload::isImage($a['mime']) || $a['mime'] === 'application/pdf');
        header('Content-Type: ' . $a['mime']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($a['original_name']));
        header('Cache-Control: private, max-age=3600');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        readfile($path);
        exit;
    }

    public function upload(): void
    {
        $this->requirePost();
        $entity = (string)input('entity');
        $id     = input_int('entity_id');
        $section = Upload::sectionFor($entity);
        if (!$section || !$id) {
            json_error('اطلاعات پیوست نامعتبر است');
        }
        $this->authorizeAttachment($entity, $id, Auth::MANAGE);
        $table = Upload::ENTITIES[$entity][0];
        if (!DB::value("SELECT id FROM `$table` WHERE id = ?", [$id])) {
            json_error('رکورد مربوطه پیدا نشد');
        }
        $r = Upload::saveMany('files', $entity, $id, input('title') ?: null);
        if (!$r['saved'] && !$r['errors']) {
            json_error('هیچ فایلی انتخاب نشده است');
        }
        if ($r['errors'] && !$r['saved']) {
            json_error(implode(' | ', $r['errors']));
        }
        json_ok(fa_digits($r['saved']) . ' فایل آپلود شد' . ($r['errors'] ? ' (خطا: ' . implode(' | ', $r['errors']) . ')' : ''));
    }

    public function delete(): void
    {
        $this->requirePost();
        $a = DB::one('SELECT * FROM attachments WHERE id = ?', [input_int('id')]);
        if (!$a) {
            json_error('پیوست پیدا نشد', 404);
        }
        $this->authorizeAttachment($a['entity_type'], (int)$a['entity_id'], Auth::MANAGE);
        @unlink(Upload::path($a));
        DB::delete('attachments', 'id = ?', [$a['id']]);
        json_ok('پیوست حذف شد');
    }

    /** تیکت داخلی: فقط شرکت‌کنندگان تیکت؛ بقیه: طبق دسترسی بخش */
    private function authorizeAttachment(string $entity, int $id, int $level): void
    {
        if ($entity === 'internal_ticket') {
            $t = InternalTicket::find($id);
            if (!$t || !InternalTicket::canAccess($t)) {
                json_error('شما به این تیکت دسترسی ندارید', 403);
            }
            return;
        }
        $this->authorize(Upload::sectionFor($entity) ?? '__none__', $level);
    }
}
