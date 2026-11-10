<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Token CSRF por sesión. Todo formulario POST debe incluir Csrf::field()
 * y el controlador debe verificarlo con Csrf::validate().
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        $expected = $_SESSION[self::KEY] ?? '';

        return is_string($token) && $expected !== '' && hash_equals($expected, $token);
    }
}
