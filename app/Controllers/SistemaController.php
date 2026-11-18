<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\View;

/**
 * Página "Sistema" del administrador: tecnologías, versiones en ejecución y
 * datos de conexión a la base de datos (sin la contraseña).
 */
final class SistemaController
{
    public function index(): void
    {
        $app = Config::file('app');
        $db  = Config::file('database');

        $mysql = Database::serverVersion();

        $tecnologias = [
            ['capa' => 'Lenguaje del servidor', 'nombre' => 'PHP', 'version' => PHP_VERSION, 'origen' => 'Imagen Docker php:8.4-apache'],
            ['capa' => 'Servidor web', 'nombre' => 'Apache HTTP Server', 'version' => self::versionApache(), 'origen' => 'Imagen Docker php:8.4-apache'],
            ['capa' => 'Base de datos', 'nombre' => 'MySQL', 'version' => $mysql ?? 'sin conexión', 'origen' => 'Imagen Docker mysql:8.4 (LTS)'],
            ['capa' => 'Acceso a datos', 'nombre' => 'PDO + pdo_mysql', 'version' => phpversion('pdo_mysql') ?: '—', 'origen' => 'Extensión de PHP'],
            ['capa' => 'Interfaz', 'nombre' => 'Bootstrap', 'version' => $app['frontend']['bootstrap'], 'origen' => 'config/app.php (CDN jsDelivr)'],
            ['capa' => 'Mapas', 'nombre' => 'Leaflet + OpenStreetMap', 'version' => $app['frontend']['leaflet'], 'origen' => 'config/app.php (CDN unpkg)'],
            ['capa' => 'Arquitectura', 'nombre' => 'MVC propio', 'version' => $app['version'], 'origen' => 'Carpeta app/ (Models, Views, Controllers)'],
        ];

        $conexion = [
            'Archivo de configuración' => 'config/database.php',
            'Clase que conecta'        => 'app/Core/Database.php (PDO)',
            'Motor'                    => $db['driver'],
            'Servidor (host)'          => $db['dsn'] ? 'definido por DB_DSN' : $db['host'],
            'Puerto'                   => $db['dsn'] ? '—' : $db['port'],
            'Base de datos'            => $db['database'],
            'Usuario'                  => $db['username'],
            'Juego de caracteres'      => $db['charset'],
            'Zona horaria'             => $db['time_zone'] . ' (Colombia)',
            'Estado'                   => $mysql !== null ? 'Conectada' : 'Sin conexión',
        ];

        View::render('sistema/index', [
            'title'       => 'Sistema',
            'app'         => $app,
            'tecnologias' => $tecnologias,
            'conexion'    => $conexion,
            'conectada'   => $mysql !== null,
        ]);
    }

    /**
     * "Apache/2.4.65 (Debian)" -> "2.4.65".
     */
    private static function versionApache(): string
    {
        $software = (string) ($_SERVER['SERVER_SOFTWARE'] ?? '');

        return preg_match('#Apache/([\d.]+)#', $software, $m) === 1 ? $m[1] : ($software !== '' ? $software : '—');
    }
}
