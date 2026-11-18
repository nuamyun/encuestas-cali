<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo de la tabla solicitudes: todas las consultas SQL de las
 * solicitudes de encuesta están aquí.
 */
final class SolicitudModel
{
    /** Columnas comunes de los listados, con barrio, comuna, punto y usuarios. */
    private const SELECT = "
        SELECT s.id, s.nombres, s.apellidos, s.documento, s.celular, s.direccion,
               s.latitud, s.longitud, s.fecha_radicacion, s.fecha_limite,
               s.estado, s.motivo_rechazo, s.revisado_en,
               s.punto_atencion_id, b.nombre AS barrio,
               c.id AS comuna_corregimiento_id, c.tipo, c.numero, c.nombre AS comuna,
               pa.nombre AS punto,
               u.nombres AS usuario_nombres, u.apellidos AS usuario_apellidos,
               r.nombres AS revisor_nombres, r.apellidos AS revisor_apellidos
          FROM solicitudes s
          JOIN barrios b                  ON b.id = s.barrio_id
          JOIN comunas_corregimientos c   ON c.id = b.comuna_corregimiento_id
          JOIN puntos_atencion pa         ON pa.id = s.punto_atencion_id
          JOIN usuarios u                 ON u.id = s.usuario_id
     LEFT JOIN usuarios r                 ON r.id = s.revisado_por";

    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @param array{nombres: string, apellidos: string, documento: string, celular: string, direccion: string, barrio_id: int, latitud: ?float, longitud: ?float, punto_atencion_id: int, usuario_id: int} $datos
     */
    public function create(array $datos): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO solicitudes
                (nombres, apellidos, documento, celular, direccion, barrio_id, latitud, longitud, punto_atencion_id, usuario_id)
             VALUES
                (:nombres, :apellidos, :documento, :celular, :direccion, :barrio_id, :latitud, :longitud, :punto_atencion_id, :usuario_id)'
        );
        $stmt->execute([
            'nombres'           => $datos['nombres'],
            'apellidos'         => $datos['apellidos'],
            'documento'         => $datos['documento'],
            'celular'           => $datos['celular'],
            'direccion'         => $datos['direccion'],
            'barrio_id'         => $datos['barrio_id'],
            'latitud'           => $datos['latitud'],
            'longitud'          => $datos['longitud'],
            'punto_atencion_id' => $datos['punto_atencion_id'],
            'usuario_id'        => $datos['usuario_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(self::SELECT . ' WHERE s.id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Cuántas solicitudes activas (pendientes o listas) tiene un documento.
     * Las rechazadas no cuentan para el límite por persona.
     */
    public function activasPorDocumento(string $documento): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM solicitudes WHERE documento = :documento AND estado <> 'rechazada'"
        );
        $stmt->execute(['documento' => $documento]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Solicitudes radicadas en un punto de atención, las más recientes primero.
     *
     * @return list<array<string, mixed>>
     */
    public function delPunto(int $puntoId, bool $soloSinUbicacion = false, int $limite = 100): array
    {
        $sql = self::SELECT . ' WHERE s.punto_atencion_id = :punto'
            . ($soloSinUbicacion ? ' AND s.latitud IS NULL' : '')
            . ' ORDER BY s.fecha_radicacion DESC, s.id DESC LIMIT ' . max(1, $limite);

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['punto' => $puntoId]);

        return $stmt->fetchAll();
    }

    /**
     * Bitácora (HU-05): solicitudes radicadas en un rango de fechas, de todos
     * los puntos de atención o de uno solo, en orden de radicación.
     *
     * @return list<array<string, mixed>>
     */
    public function radicadasEntre(string $desde, string $hasta, ?int $puntoId = null): array
    {
        $sql = self::SELECT . ' WHERE s.fecha_radicacion >= :desde AND s.fecha_radicacion < :hasta'
            . ($puntoId !== null ? ' AND s.punto_atencion_id = :punto' : '')
            . ' ORDER BY s.fecha_radicacion, s.id';

        $params = ['desde' => $desde, 'hasta' => $hasta];
        if ($puntoId !== null) {
            $params['punto'] = $puntoId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function actualizarUbicacion(int $id, ?float $lat, ?float $lon): void
    {
        $stmt = $this->db->prepare('UPDATE solicitudes SET latitud = :lat, longitud = :lon WHERE id = :id');
        $stmt->execute(['lat' => $lat, 'lon' => $lon, 'id' => $id]);
    }

    /**
     * Cambia el estado (pendiente, lista o rechazada) y registra quién y cuándo.
     */
    public function cambiarEstado(int $id, string $estado, ?string $motivo, int $revisorId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE solicitudes
                SET estado = :estado, motivo_rechazo = :motivo,
                    revisado_por = :revisor, revisado_en = CURRENT_TIMESTAMP
              WHERE id = :id'
        );
        $stmt->execute(['estado' => $estado, 'motivo' => $motivo, 'revisor' => $revisorId, 'id' => $id]);
    }
}
