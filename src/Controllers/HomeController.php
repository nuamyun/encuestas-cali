<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class HomeController
{
    /**
     * La raíz lleva al panel si hay sesión, o al login si no.
     */
    public function index(): void
    {
        redirect(Auth::check() ? '/inicio' : '/login');
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
