<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class UsuarioRepository
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActivoByCorreo(string $correo): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.nombres, u.apellidos, u.correo, u.password_hash, u.punto_atencion_id,
                    p.nombre AS perfil, pa.nombre AS punto_nombre
               FROM usuarios u
               JOIN perfiles p ON p.id = u.perfil_id
          LEFT JOIN puntos_atencion pa ON pa.id = u.punto_atencion_id
              WHERE u.correo = :correo AND u.activo = 1'
        );
        $stmt->execute(['correo' => mb_strtolower(trim($correo))]);

        return $stmt->fetch() ?: null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return $this->db->query(
            'SELECT u.id, u.documento, u.nombres, u.apellidos, u.correo, u.activo,
                    p.nombre AS perfil, pa.nombre AS punto_nombre
               FROM usuarios u
               JOIN perfiles p ON p.id = u.perfil_id
          LEFT JOIN puntos_atencion pa ON pa.id = u.punto_atencion_id
           ORDER BY p.nombre, u.apellidos, u.nombres'
        )->fetchAll();
    }

    public function existeDocumento(string $documento): bool
    {
        return $this->existe('documento', $documento);
    }

    public function existeCorreo(string $correo): bool
    {
        return $this->existe('correo', $correo);
    }

    /**
     * @param array{documento: string, nombres: string, apellidos: string, correo: string, password: string, perfil_id: int, punto_atencion_id: ?int} $datos
     */
    public function create(array $datos): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO usuarios (documento, nombres, apellidos, correo, password_hash, perfil_id, punto_atencion_id)
             VALUES (:documento, :nombres, :apellidos, :correo, :password_hash, :perfil_id, :punto_atencion_id)'
        );
        $stmt->execute([
            'documento'         => $datos['documento'],
            'nombres'           => $datos['nombres'],
            'apellidos'         => $datos['apellidos'],
            'correo'            => $datos['correo'],
            'password_hash'     => password_hash($datos['password'], PASSWORD_DEFAULT),
            'perfil_id'         => $datos['perfil_id'],
            'punto_atencion_id' => $datos['punto_atencion_id'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    private function existe(string $columna, string $valor): bool
    {
        // $columna nunca viene del usuario: solo 'documento' o 'correo'
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE {$columna} = :valor LIMIT 1");
        $stmt->execute(['valor' => $valor]);

        return $stmt->fetchColumn() !== false;
    }
}
