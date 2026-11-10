<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lee la configuración desde variables de entorno (definidas en
 * docker-compose.yml o en el archivo .env).
 */
final class Config
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        return $value === false || $value === '' ? $default : $value;
    }
}
