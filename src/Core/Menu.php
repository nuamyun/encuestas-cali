<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Opciones de menú por perfil. Cada paso del desarrollo agrega aquí los
 * módulos que implementa, junto con su ruta en config/routes.php.
 */
final class Menu
{
    /** @var array<string, list<array{titulo: string, ruta: string, descripcion: string}>> */
    private const OPCIONES = [
        'administrador' => [
            ['titulo' => 'Usuarios', 'ruta' => '/usuarios', 'descripcion' => 'Crear usuarios y asignar perfiles.'],
        ],
        'ventanilla'  => [
            ['titulo' => 'Nueva solicitud', 'ruta' => '/solicitudes/nueva', 'descripcion' => 'Atender a un usuario y radicar su solicitud de encuesta.'],
            ['titulo' => 'Solicitudes', 'ruta' => '/solicitudes', 'descripcion' => 'Ver las solicitudes del punto y georreferenciar las pendientes.'],
        ],
        'encuestador' => [],
        'calidad'     => [],
        'enrutador'   => [
            ['titulo' => 'Bitácora', 'ruta' => '/bitacora', 'descripcion' => 'Solicitudes generadas en el día en todos los puntos de atención.'],
        ],
    ];

    /**
     * @return list<array{titulo: string, ruta: string, descripcion: string}>
     */
    public static function para(string $perfil): array
    {
        return self::OPCIONES[$perfil] ?? [];
    }
}
