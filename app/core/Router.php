<?php
namespace App\Core;

defined('APP_ROOT') or exit('No direct access');

/**
 * مسیریاب ساده: r=customers/show → CustomersController::show()
 */
class Router
{
    public static function dispatch(string $route): void
    {
        $route = trim(strtolower($route), '/');
        if (!preg_match('/^[a-z0-9_\-]+(\/[a-z0-9_\-]+)?$/', $route)) {
            self::notFound();
        }
        [$ctrl, $action] = array_pad(explode('/', $route, 2), 2, 'index');

        $class = 'App\\Controllers\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $ctrl))) . 'Controller';
        $method = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $action))));

        if (!class_exists($class) || !method_exists($class, $method) || str_starts_with($method, '_')) {
            self::notFound();
        }
        $ref = new \ReflectionMethod($class, $method);
        if (!$ref->isPublic() || $ref->isStatic()) {
            self::notFound();
        }

        $controller = new $class();
        $controller->$method();
    }

    public static function notFound(): never
    {
        http_response_code(404);
        if (is_ajax()) {
            json_error('صفحه پیدا نشد', 404);
        }
        View::render('errors/404', ['title' => 'صفحه پیدا نشد'], Auth::check() ? 'layouts/app' : 'layouts/auth');
        exit;
    }
}
