<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Abre la conexión única a MySQL con PDO, usando los datos de
 * config/database.php.
 * - Consultas preparadas reales (sin emulación) contra inyección SQL.
 * - utf8mb4, para guardar bien tildes y eñes.
 * - Zona horaria de Colombia, para que "las solicitudes del día" coincidan
 *   con el día local de Cali.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $cfg = Config::file('database');

            $dsn = $cfg['dsn'] ?? sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $cfg['driver'],
                $cfg['host'],
                $cfg['port'],
                $cfg['database'],
                $cfg['charset']
            );

            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            if (self::$pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                self::$pdo->exec("SET time_zone = '" . $cfg['time_zone'] . "'");
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

    /**
     * Versión del servidor de base de datos, por ejemplo "8.4.3".
     */
    public static function serverVersion(): ?string
    {
        try {
            return (string) self::connection()->query('SELECT VERSION()')->fetchColumn();
        } catch (\Throwable) {
            return null;
        }
    }
}
