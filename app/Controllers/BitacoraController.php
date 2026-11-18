<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\CatalogoModel;
use App\Models\Reglas\BitacoraFiltro;
use App\Models\Reglas\EstadoSolicitud;
use App\Models\Reglas\Ubicacion;
use App\Models\SolicitudModel;

/**
 * HU-05: bitácora diaria del enrutador con las solicitudes de todos los
 * puntos de atención, y revisión de su estado (pendiente, lista, rechazada).
 */
final class BitacoraController
{
    public function index(): void
    {
        $puntos = (new CatalogoModel())->puntosAtencion();
        $filtro = BitacoraFiltro::desde($_GET, array_map('intval', array_column($puntos, 'id')));

        // Todas las del día (y punto) para los totales; la tabla y el mapa
        // muestran solo las del estado elegido.
        $delDia = (new SolicitudModel())->radicadasEntre(
            $filtro->desdeSql(),
            $filtro->hastaSql(),
            $filtro->puntoId
        );
        $solicitudes = $filtro->aplicarEstado($delDia);

        $marcadores = [];
        foreach ($solicitudes as $s) {
            if ($s['latitud'] !== null) {
                $marcadores[] = [
                    'lat'     => (float) $s['latitud'],
                    'lon'     => (float) $s['longitud'],
                    'titulo'  => '#' . $s['id'] . ' ' . $s['nombres'] . ' ' . $s['apellidos'],
                    'detalle' => $s['direccion'] . ' · ' . $s['barrio'] . ' · ' . $s['comuna']
                        . ' · ' . EstadoSolicitud::ETIQUETAS[$s['estado']],
                ];
            }
        }

        View::render('bitacora/index', [
            'title'       => 'Bitácora diaria',
            'filtro'      => $filtro,
            'puntos'      => $puntos,
            'solicitudes' => $solicitudes,
            'resumen'     => BitacoraFiltro::resumen($delDia),
            'marcadores'  => $marcadores,
            'mapa'        => Ubicacion::config(),
            'conMapa'     => true,
        ]);
    }

    /**
     * POST /bitacora/solicitudes/{id}/estado
     * El enrutador marca una solicitud como pendiente, lista o rechazada.
     */
    public function cambiarEstado(string $id): void
    {
        $solicitudes = new SolicitudModel();
        $solicitud   = $solicitudes->find((int) $id);
        $volver      = '/bitacora?' . $this->filtrosDeRegreso();

        if ($solicitud === null) {
            Session::flash('danger', "La solicitud #{$id} no existe.");
            redirect($volver);
        }

        $cambio = EstadoSolicitud::validarCambio($_POST);
        if ($cambio['error'] !== null) {
            Session::flash('danger', "Solicitud #{$id}: {$cambio['error']}");
            redirect($volver);
        }

        $solicitudes->cambiarEstado((int) $id, $cambio['estado'], $cambio['motivo'], Auth::user()['id']);
        Session::flash('success', sprintf(
            'Solicitud #%d de %s %s marcada como %s.',
            (int) $id,
            $solicitud['nombres'],
            $solicitud['apellidos'],
            mb_strtolower(EstadoSolicitud::ETIQUETAS[$cambio['estado']])
        ));
        redirect($volver);
    }

    /**
     * Solo deja pasar los filtros conocidos, para no redirigir a cualquier URL.
     */
    private function filtrosDeRegreso(): string
    {
        return http_build_query(array_filter([
            'fecha'  => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['fecha'] ?? '')) ? $_POST['fecha'] : null,
            'punto'  => ctype_digit((string) ($_POST['punto'] ?? '')) ? $_POST['punto'] : null,
            'estado' => in_array($_POST['filtro_estado'] ?? '', EstadoSolicitud::TODOS, true) ? $_POST['filtro_estado'] : null,
        ]));
    }
}
