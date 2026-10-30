<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::partial($layout, array_merge($data, ['content' => $content]));
    }

    public static function partial(string $view, array $data = []): string
    {
        $file = APP_ROOT . '/app/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }
}
