<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Reglas de HU-03 (registro de la solicitud) y HU-04 (georreferenciación).
 */
final class SolicitudValidator
{
    /**
     * @param array<string, mixed> $input
     * @param callable(int $barrioId, int $comunaCorregimientoId): bool $barrioPertenece
     * @return array{datos: array<string, mixed>, errores: array<string, string>}
     */
    public static function validate(array $input, callable $barrioPertenece): array
    {
        $datos = [
            'nombres'                 => self::limpiar($input['nombres'] ?? ''),
            'apellidos'               => self::limpiar($input['apellidos'] ?? ''),
            'celular'                 => self::normalizarCelular($input['celular'] ?? ''),
            'direccion'               => self::limpiar($input['direccion'] ?? ''),
            'comuna_corregimiento_id' => (int) ($input['comuna_corregimiento_id'] ?? 0),
            'barrio_id'               => (int) ($input['barrio_id'] ?? 0),
            'latitud'                 => null,
            'longitud'                => null,
        ];
        $errores = [];

        if (!self::nombreValido($datos['nombres'])) {
            $errores['nombres'] = 'Escribe los nombres (solo letras, de 2 a 80 caracteres).';
        }
        if (!self::nombreValido($datos['apellidos'])) {
            $errores['apellidos'] = 'Escribe los apellidos (solo letras, de 2 a 80 caracteres).';
        }
        if (!preg_match('/^3\d{9}$/', $datos['celular'])) {
            $errores['celular'] = 'El celular debe tener 10 dígitos y empezar por 3.';
        }
        $largo = mb_strlen($datos['direccion']);
        if ($largo < 5 || $largo > 150) {
            $errores['direccion'] = 'Escribe la dirección completa (de 5 a 150 caracteres).';
        }

        if ($datos['comuna_corregimiento_id'] <= 0) {
            $errores['comuna_corregimiento_id'] = 'Selecciona la comuna o el corregimiento.';
        }
        if ($datos['barrio_id'] <= 0) {
            $errores['barrio_id'] = 'Selecciona el barrio o la vereda.';
        } elseif (!isset($errores['comuna_corregimiento_id'])
            && !$barrioPertenece($datos['barrio_id'], $datos['comuna_corregimiento_id'])) {
            $errores['barrio_id'] = 'El barrio seleccionado no pertenece a esa comuna o corregimiento.';
        }

        $ubicacion = Ubicacion::validar($input['latitud'] ?? '', $input['longitud'] ?? '');
        if ($ubicacion['error'] !== null) {
            $errores['ubicacion'] = $ubicacion['error'];
        }
        $datos['latitud']  = $ubicacion['lat'];
        $datos['longitud'] = $ubicacion['lon'];

        return ['datos' => $datos, 'errores' => $errores];
    }

    /**
     * Acepta "300 123 4567", "300-123-4567" o "+57 3001234567".
     */
    public static function normalizarCelular(mixed $valor): string
    {
        $digitos = preg_replace('/\D/', '', (string) $valor) ?? '';

        if (strlen($digitos) === 12 && str_starts_with($digitos, '57')) {
            $digitos = substr($digitos, 2);
        }

        return $digitos;
    }

    private static function limpiar(mixed $valor): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $valor)) ?? '';
    }

    private static function nombreValido(string $valor): bool
    {
        return (bool) preg_match("/^[\\p{L}][\\p{L} '.-]{1,79}$/u", $valor);
    }
}
