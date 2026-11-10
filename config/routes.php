<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\InicioController;
use App\Controllers\UsuarioController;
use App\Core\Auth;
use App\Core\Router;

/**
 * Tabla de rutas de la aplicación.
 * El tercer argumento lista los perfiles autorizados:
 *   []                      ruta pública
 *   [Auth::CUALQUIER_PERFIL] cualquier usuario con sesión
 *   ['administrador', ...]  solo esos perfiles
 *
 * @var Router $router
 */

// Públicas
$router->get('/', [HomeController::class, 'index']);
$router->get('/health', [HomeController::class, 'health']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

// Cualquier usuario autenticado (HU-02)
$router->post('/logout', [AuthController::class, 'logout'], [Auth::CUALQUIER_PERFIL]);
$router->get('/inicio', [InicioController::class, 'index'], [Auth::CUALQUIER_PERFIL]);

// Administrador (HU-01)
$router->get('/usuarios', [UsuarioController::class, 'index'], ['administrador']);
$router->get('/usuarios/nuevo', [UsuarioController::class, 'create'], ['administrador']);
$router->post('/usuarios', [UsuarioController::class, 'store'], ['administrador']);
