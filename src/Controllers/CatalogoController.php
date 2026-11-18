<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\CatalogoRepository;

/**
 * Endpoints JSON para los formularios (selects dependientes).
 */
final class CatalogoController
{
    /**
     * GET /api/barrios?comuna_corregimiento_id=ID
     */
    public function barrios(): void
    {
        $id = (int) ($_GET['comuna_corregimiento_id'] ?? 0);

        View::json(['barrios' => $id > 0 ? (new CatalogoRepository())->barriosDe($id) : []]);
    }
}
