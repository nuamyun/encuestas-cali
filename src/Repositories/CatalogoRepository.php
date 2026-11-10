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
}
