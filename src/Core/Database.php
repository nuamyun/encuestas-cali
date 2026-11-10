<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Conexión única a MySQL mediante PDO.
 * - Consultas preparadas reales (sin emulación) contra inyección SQL.
 * - Zona horaria de Colombia, para que "las solicitudes del día" coincidan
 *   con el día local de Cali.
 * - DB_DSN permite apuntar a otra base (por ejemplo, en pruebas).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $dsn = Config::get('DB_DSN') ?? sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                Config::get('DB_HOST', 'db'),
                Config::get('DB_PORT', '3306'),
                Config::get('DB_NAME', 'encuestas_cali')
            );

            self::$pdo = new PDO($dsn, Config::get('DB_USER', 'app'), Config::get('DB_PASSWORD', ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            if (self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                self::$pdo->exec("SET time_zone = '-05:00'");
            }
        }

        return self::$pdo;
    }

    /**
     * Permite inyectar una conexión (se usa en las pruebas).
     */
    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /**
     * Indica si la base de datos responde. Se usa en /health.
     */
    public static function isAvailable(): bool
    {
        try {
            self::connection()->query('SELECT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
