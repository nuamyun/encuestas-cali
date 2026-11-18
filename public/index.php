<?php

declare(strict_types=1);

/**
 * Front controller: toda petición entra por aquí (ver .htaccess).
 * Orden: ruta -> CSRF (en POST) -> autorización por perfil -> controlador.
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

// Con el servidor embebido de PHP (php -S), servir los archivos estáticos directamente
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('America/Bogota');
Session::start();

$router = new Router();
require __DIR__ . '/../config/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$route  = $router->match($method, $_SERVER['REQUEST_URI'] ?? '/');

if ($route === null) {
    http_response_code(404);
    View::render('errors/404', ['title' => 'Página no encontrada']);
    exit;
}

// Todo formulario POST debe traer el token CSRF de la sesión
if (strtoupper($method) === 'POST' && !Csrf::validate($_POST['_csrf'] ?? null)) {
    http_response_code(419);
    Session::flash('warning', 'La sesión expiró. Intenta de nuevo.');
    redirect(Auth::check() ? '/inicio' : '/login');
}

switch (Auth::autorizar($route['perfiles'], Auth::user())) {
    case 'login':
        Session::flash('warning', 'Inicia sesión para continuar.');
        redirect('/login');
    case 'prohibido':
        http_response_code(403);
        View::render('errors/403', ['title' => 'Acceso denegado']);
        exit;
}

[$class, $action] = $route['handler'];
(new $class())->{$action}(...$route['params']);
