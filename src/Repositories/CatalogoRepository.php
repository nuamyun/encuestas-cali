<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

/**
 * Datos de referencia: perfiles, puntos de atención y división territorial.
 */
final class CatalogoRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function perfiles(): array
    {
        return $this->db->query('SELECT id, nombre FROM perfiles ORDER BY id')->fetchAll();
    }

    /**
     * @return list<array{id: int, nombre: string}>
     */
    public function puntosAtencion(): array
    {
        return $this->db->query(
            'SELECT pa.id, pa.nombre
               FROM puntos_atencion pa
               JOIN comunas_corregimientos c ON c.id = pa.comuna_id
           ORDER BY pa.es_oficina_principal DESC, c.numero'
        )->fetchAll();
    }

    /**
     * Comunas (zona urbana) y corregimientos (zona rural), en ese orden.
     *
     * @return list<array{id: int, tipo: string, numero: ?int, nombre: string}>
     */
    public function comunasCorregimientos(): array
    {
        return $this->db->query(
            "SELECT id, tipo, numero, nombre
               FROM comunas_corregimientos
           ORDER BY CASE tipo WHEN 'comuna' THEN 0 ELSE 1 END, numero, nombre"
        )->fetchAll();
    }

    /**
     * Barrios (en una comuna) o veredas (en un corregimiento).
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function barriosDe(int $comunaCorregimientoId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, nombre FROM barrios WHERE comuna_corregimiento_id = :cc ORDER BY nombre'
        );
        $stmt->execute(['cc' => $comunaCorregimientoId]);

        return $stmt->fetchAll();
    }

    public function barrioPertenece(int $barrioId, int $comunaCorregimientoId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM barrios WHERE id = :barrio AND comuna_corregimiento_id = :cc'
        );
        $stmt->execute(['barrio' => $barrioId, 'cc' => $comunaCorregimientoId]);

        return $stmt->fetchColumn() !== false;
    }
}
