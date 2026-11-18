<?php

declare(strict_types=1);

/**
 * CONEXIÓN A LA BASE DE DATOS
 * ===========================
 * Este es el único lugar donde se definen los datos de conexión a MySQL.
 * Los lee app/Core/Database.php, que abre la conexión con PDO.
 *
 * Los valores llegan como variables de entorno desde docker-compose.yml,
 * que a su vez las toma del archivo .env:
 *
 *   .env  ->  docker-compose.yml (servicio "app")  ->  variables de entorno
 *        ->  config/database.php  ->  app/Core/Database.php  ->  PDO -> MySQL
 *
 * Dentro de Docker, el servidor de MySQL se llama "db" (el nombre del
 * servicio en docker-compose.yml) y escucha en el puerto 3306.
 * Desde el equipo (por ejemplo, MySQL Workbench) se entra por localhost:3307.
 */

use App\Core\Config;

return [
    'driver'    => 'mysql',
    'host'      => Config::get('DB_HOST', 'db'),
    'port'      => Config::get('DB_PORT', '3306'),
    'database'  => Config::get('DB_NAME', 'encuestas_cali'),
    'username'  => Config::get('DB_USER', 'app'),
    'password'  => Config::get('DB_PASSWORD', ''),
    'charset'   => 'utf8mb4',
    'time_zone' => '-05:00', // hora de Colombia
    // Solo para pruebas: si DB_DSN existe, reemplaza host, puerto y base.
    'dsn'       => Config::get('DB_DSN'),
];
