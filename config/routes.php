<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use App\Core\Router;

/**
 * Tabla de rutas de la aplicación.
 * El tercer argumento lista los perfiles autorizados (vacío = pública).
 *
 * @var Router $router
 */

$router->get('/', [HomeController::class, 'index']);
$router->get('/health', [HomeController::class, 'health']);
