<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Reglas de HU-01 para crear un usuario. No consulta la base de datos:
 * la unicidad de documento y correo la verifica el controlador.
 */
final class UsuarioValidator
{
    /**
     * @param array<string, mixed> $input  Datos del formulario
     * @param array<int, string>   $perfiles  id => nombre de los perfiles válidos
     * @param list<int>            $puntos  ids de puntos de atención válidos
     * @return array{datos: array<string, mixed>, errores: array<string, string>}
     */
    public static function validate(array $input, array $perfiles, array $puntos): array
    {
        $datos = [
            'documento'         => trim((string) ($input['documento'] ?? '')),
            'nombres'           => self::limpiarNombre($input['nombres'] ?? ''),
            'apellidos'         => self::limpiarNombre($input['apellidos'] ?? ''),
            'correo'            => mb_strtolower(trim((string) ($input['correo'] ?? ''))),
            'password'          => (string) ($input['password'] ?? ''),
            'perfil_id'         => (int) ($input['perfil_id'] ?? 0),
            'punto_atencion_id' => ($input['punto_atencion_id'] ?? '') === '' ? null : (int) $input['punto_atencion_id'],
        ];
        $errores = [];

        if (!preg_match('/^\d{5,15}$/', $datos['documento'])) {
            $errores['documento'] = 'El documento debe tener entre 5 y 15 dígitos, sin puntos ni espacios.';
        }
        if (!self::nombreValido($datos['nombres'])) {
            $errores['nombres'] = 'Escribe los nombres (solo letras, de 2 a 80 caracteres).';
        }
        if (!self::nombreValido($datos['apellidos'])) {
            $errores['apellidos'] = 'Escribe los apellidos (solo letras, de 2 a 80 caracteres).';
        }
        if (filter_var($datos['correo'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($datos['correo']) > 120) {
            $errores['correo'] = 'Escribe un correo electrónico válido.';
        }
        if (mb_strlen($datos['password']) < 8) {
            $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        $perfil = $perfiles[$datos['perfil_id']] ?? null;
        if ($perfil === null) {
            $errores['perfil_id'] = 'Selecciona un perfil.';
        }

        if ($perfil === 'ventanilla') {
            if ($datos['punto_atencion_id'] === null || !in_array($datos['punto_atencion_id'], $puntos, true)) {
                $errores['punto_atencion_id'] = 'El perfil ventanilla debe tener un punto de atención.';
            }
        } else {
            // Solo ventanilla se asocia a un punto
            $datos['punto_atencion_id'] = null;
        }

        return ['datos' => $datos, 'errores' => $errores];
    }

    private static function limpiarNombre(mixed $valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor)) ?? '';
    }

    private static function nombreValido(string $valor): bool
    {
        return (bool) preg_match("/^[\\p{L}][\\p{L} '.-]{1,79}$/u", $valor);
    }
}
