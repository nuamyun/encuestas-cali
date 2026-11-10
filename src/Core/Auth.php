<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UsuarioRepository;

/**
 * Autenticación y autorización por perfil (HU-02, R1).
 */
final class Auth
{
    public const PERFILES = ['administrador', 'ventanilla', 'encuestador', 'calidad', 'enrutador'];

    /** Comodín de ruta: cualquier usuario autenticado, sin importar el perfil. */
    public const CUALQUIER_PERFIL = '*';

    private const KEY = '_usuario';

    /**
     * Valida credenciales. Devuelve false con el mismo resultado si el correo
     * no existe, la contraseña es incorrecta o el usuario está inactivo, para
     * no revelar cuál de las tres falló.
     */
    public static function attempt(string $correo, string $password, UsuarioRepository $usuarios): bool
    {
        $usuario = $usuarios->findActivoByCorreo($correo);

        if ($usuario === null || !password_verify($password, $usuario['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::KEY] = [
            'id'                => (int) $usuario['id'],
            'nombres'           => $usuario['nombres'],
            'apellidos'         => $usuario['apellidos'],
            'correo'            => $usuario['correo'],
            'perfil'            => $usuario['perfil'],
            'punto_atencion_id' => $usuario['punto_atencion_id'] !== null ? (int) $usuario['punto_atencion_id'] : null,
            'punto_nombre'      => $usuario['punto_nombre'],
        ];

        return true;
    }

    /**
     * @return array{id: int, nombres: string, apellidos: string, correo: string, perfil: string, punto_atencion_id: ?int, punto_nombre: ?string}|null
     */
    public static function user(): ?array
    {
        return $_SESSION[self::KEY] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 3600,
                'path'     => $params['path'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
            session_destroy();
        }
    }

    /**
     * Decide si un usuario puede entrar a una ruta.
     *
     * @param list<string> $perfilesRuta Perfiles permitidos; vacío = ruta pública
     * @param array{perfil: string}|null $usuario
     * @return 'permitido'|'login'|'prohibido'
     */
    public static function autorizar(array $perfilesRuta, ?array $usuario): string
    {
        if ($perfilesRuta === []) {
            return 'permitido';
        }
        if ($usuario === null) {
            return 'login';
        }
        if (in_array(self::CUALQUIER_PERFIL, $perfilesRuta, true) || in_array($usuario['perfil'], $perfilesRuta, true)) {
            return 'permitido';
        }

        return 'prohibido';
    }
}
