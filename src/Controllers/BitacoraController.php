<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Repositories\CatalogoRepository;
use App\Repositories\SolicitudRepository;
use App\Validation\BitacoraFiltro;
use App\Validation\Ubicacion;

/**
 * HU-05: bitácora diaria del enrutador con las solicitudes de todos los
 * puntos de atención.
 */
final class BitacoraController
{
    public function index(): void
    {
        $puntos = (new CatalogoRepository())->puntosAtencion();
        $filtro = BitacoraFiltro::desde($_GET, array_map('intval', array_column($puntos, 'id')));

        $solicitudes = (new SolicitudRepository())->radicadasEntre(
            $filtro->desdeSql(),
            $filtro->hastaSql(),
            $filtro->puntoId
        );

        $marcadores = [];
        foreach ($solicitudes as $s) {
            if ($s['latitud'] !== null) {
                $marcadores[] = [
                    'lat'    => (float) $s['latitud'],
                    'lon'    => (float) $s['longitud'],
                    'titulo' => '#' . $s['id'] . ' ' . $s['nombres'] . ' ' . $s['apellidos'],
                    'detalle' => $s['direccion'] . ' · ' . $s['barrio'] . ' · ' . $s['comuna'],
                ];
            }
        }

        View::render('bitacora/index', [
            'title'       => 'Bitácora diaria',
            'filtro'      => $filtro,
            'puntos'      => $puntos,
            'solicitudes' => $solicitudes,
            'resumen'     => BitacoraFiltro::resumen($solicitudes),
            'marcadores'  => $marcadores,
            'mapa'        => Ubicacion::config(),
            'conMapa'     => true,
        ]);
    }
}
