<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

final class HomeController
{
    /**
     * Página inicial. En el paso 2 se reemplaza por el formulario de login.
     */
    public function index(): void
    {
        View::render('home/index', [
            'title'   => 'Inicio',
            'dbOnline' => Database::isAvailable(),
        ]);
    }

    /**
     * Chequeo de salud para Docker, GitHub Actions y el despliegue.
     */
    public function health(): void
    {
        $db = Database::isAvailable();

        View::json(['status' => $db ? 'ok' : 'degraded', 'db' => $db], $db ? 200 : 503);
    }
}
