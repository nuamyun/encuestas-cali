<?php

declare(strict_types=1);

namespace App\Models\Reglas;

/**
 * Estados de una solicitud en la bitácora del enrutador.
 *
 *   pendiente -> recién radicada, sin revisar (estado inicial)
 *   lista     -> el enrutador la revisó y puede ir a una hoja de ruta
 *   rechazada -> el enrutador la descartó; el motivo es obligatorio
 *
 * El enrutador puede pasar de cualquier estado a otro (por ejemplo, volver
 * a "pendiente" si se equivocó).
 */
final class EstadoSolicitud
{
    public const PENDIENTE = 'pendiente';
    public const LISTA     = 'lista';
    public const RECHAZADA = 'rechazada';

    public const TODOS = [self::PENDIENTE, self::LISTA, self::RECHAZADA];

    public const ETIQUETAS = [
        self::PENDIENTE => 'Pendiente',
        self::LISTA     => 'Lista',
        self::RECHAZADA => 'Rechazada',
    ];

    /**
     * Valida un cambio de estado pedido por el enrutador.
     *
     * @param array<string, mixed> $input  'estado' y, si es rechazo, 'motivo'
     * @return array{estado: ?string, motivo: ?string, error: ?string}
     */
    public static function validarCambio(array $input): array
    {
        $estado = (string) ($input['estado'] ?? '');
        $motivo = preg_replace('/\s+/u', ' ', trim((string) ($input['motivo'] ?? ''))) ?? '';

        if (!in_array($estado, self::TODOS, true)) {
            return ['estado' => null, 'motivo' => null, 'error' => 'Estado no válido.'];
        }

        if ($estado !== self::RECHAZADA) {
            // Solo el rechazo lleva motivo; al cambiar a otro estado se borra.
            return ['estado' => $estado, 'motivo' => null, 'error' => null];
        }

        $largo = mb_strlen($motivo);
        if ($largo < 5 || $largo > 255) {
            return ['estado' => null, 'motivo' => null, 'error' => 'Para rechazar, escribe el motivo (de 5 a 255 caracteres).'];
        }

        return ['estado' => $estado, 'motivo' => $motivo, 'error' => null];
    }
}
