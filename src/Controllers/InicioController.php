<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Menu;
use App\Core\View;

/**
 * Panel de inicio: muestra solo los módulos del perfil del usuario.
 */
final class InicioController
{
    public function index(): void
    {
        $usuario = Auth::user();

        View::render('inicio/index', [
            'title'   => 'Inicio',
            'usuario' => $usuario,
            'modulos' => Menu::para($usuario['perfil'] ?? ''),
        ]);
    }
}
