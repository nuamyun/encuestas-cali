<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\BitacoraController;
use App\Controllers\CatalogoController;
use App\Controllers\SistemaController;
use App\Controllers\SolicitudController;
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

// Ventanilla: registro y georreferenciación de solicitudes (HU-03, HU-04)
$router->get('/solicitudes', [SolicitudController::class, 'index'], ['ventanilla']);
$router->get('/solicitudes/nueva', [SolicitudController::class, 'create'], ['ventanilla']);
$router->post('/solicitudes', [SolicitudController::class, 'store'], ['ventanilla']);
$router->get('/solicitudes/{id}/ubicacion', [SolicitudController::class, 'editUbicacion'], ['ventanilla']);
$router->post('/solicitudes/{id}/ubicacion', [SolicitudController::class, 'updateUbicacion'], ['ventanilla']);
$router->get('/api/barrios', [CatalogoController::class, 'barrios'], ['ventanilla']);

// Enrutador: bitácora diaria (HU-05)
$router->get('/bitacora', [BitacoraController::class, 'index'], ['enrutador']);
$router->post('/bitacora/solicitudes/{id}/estado', [BitacoraController::class, 'cambiarEstado'], ['enrutador']);

// Administrador (HU-01)
$router->get('/usuarios', [UsuarioController::class, 'index'], ['administrador']);
$router->get('/usuarios/nuevo', [UsuarioController::class, 'create'], ['administrador']);
$router->post('/usuarios', [UsuarioController::class, 'store'], ['administrador']);
$router->get('/sistema', [SistemaController::class, 'index'], ['administrador']);
