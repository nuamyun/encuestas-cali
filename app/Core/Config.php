<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Acceso a la configuración:
 * - get():  una variable de entorno (definida en docker-compose.yml / .env).
 * - file(): un archivo de la carpeta config/ (por ejemplo, 'database' o 'app').
 */
final class Config
{
    /** @var array<string, array<string, mixed>> */
    private static array $archivos = [];

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function file(string $nombre): array
    {
        if (!isset(self::$archivos[$nombre])) {
            $ruta = __DIR__ . '/../../config/' . $nombre . '.php';
            if (!is_file($ruta)) {
                throw new \RuntimeException("Archivo de configuración no encontrado: config/{$nombre}.php");
            }
            self::$archivos[$nombre] = require $ruta;
        }

        return self::$archivos[$nombre];
    }
}
