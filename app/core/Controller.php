<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

abstract class Controller
{
    /** کنترلرهایی که به لاگین نیاز ندارند این را false کنند */
    protected bool $requiresAuth = true;

    public function __construct()
    {
        if ($this->requiresAuth && !Auth::check()) {
            if (is_ajax()) {
                json_error('ابتدا وارد شوید', 401, ['redirect' => url('auth/login')]);
            }
            redirect('auth/login');
        }
        if ($this->requiresAuth) {
            Notifier::refresh();
        }
    }

    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            json_error('درخواست نامعتبر', 405);
        }
        verify_csrf();
    }

    protected function authorize(string $section, int $level = Auth::VIEW): void
    {
        if (!Auth::can($section, $level)) {
            if (is_ajax() || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
                json_error('شما به این بخش دسترسی ندارید', 403);
            }
            http_response_code(403);
            View::render('errors/403', ['title' => 'عدم دسترسی']);
            exit;
        }
    }

    protected function requireSuper(): void
    {
        if (!Auth::isSuper()) {
            $this->authorize('__super__', Auth::MANAGE);
        }
    }

    protected function findOr404(string $table, int $id): array
    {
        $row = DB::one("SELECT * FROM `$table` WHERE id = ?", [$id]);
        if (!$row) {
            Router::notFound();
        }
        return $row;
    }

    protected function log(string $action, string $entity, int $entityId, string $details = ''): void
    {
        DB::insert('activity_log', [
            'admin_id' => Auth::id(), 'action' => $action, 'entity_type' => $entity,
            'entity_id' => $entityId, 'details' => mb_substr($details, 0, 500), 'created_at' => now(),
        ]);
    }
}
