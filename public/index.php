<?php

declare(strict_types=1);

/**
 * Front controller: toda petición entra por aquí (ver .htaccess).
 */

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

$route = $router->match($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');

if ($route === null) {
    http_response_code(404);
    View::render('errors/404', ['title' => 'Página no encontrada']);
    exit;
}

[$class, $method] = $route['handler'];
(new $class())->{$method}(...$route['params']);
