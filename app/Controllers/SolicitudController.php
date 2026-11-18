<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;
use App\Models\CatalogoModel;
use App\Models\SolicitudModel;
use App\Models\Reglas\SolicitudValidator;
use App\Models\Reglas\Ubicacion;

/**
 * HU-03: registro de solicitudes de encuesta.
 * HU-04: georreferenciación de cada solicitud.
 * Solo perfil ventanilla; cada ventanilla trabaja con su punto de atención.
 */
final class SolicitudController
{
    private SolicitudModel $solicitudes;
    private CatalogoModel $catalogo;

    public function __construct()
    {
        $this->solicitudes = new SolicitudModel();
        $this->catalogo    = new CatalogoModel();
    }

    public function index(): void
    {
        $soloPendientes = ($_GET['pendientes'] ?? '') === '1';

        View::render('solicitudes/index', [
            'title'          => 'Solicitudes del punto',
            'usuario'        => Auth::user(),
            'solicitudes'    => $this->solicitudes->delPunto($this->puntoActual(), $soloPendientes),
            'soloPendientes' => $soloPendientes,
        ]);
    }

    public function create(): void
    {
        $this->renderForm([], []);
    }

    public function store(): void
    {
        ['datos' => $datos, 'errores' => $errores] = SolicitudValidator::validate(
            $_POST,
            fn (int $barrio, int $cc): bool => $this->catalogo->barrioPertenece($barrio, $cc),
            fn (string $documento): int => $this->solicitudes->activasPorDocumento($documento)
        );

        if ($errores !== []) {
            http_response_code(422);
            $this->renderForm($datos, $errores);

            return;
        }

        $id = $this->solicitudes->create($datos + [
            'punto_atencion_id' => $this->puntoActual(),
            'usuario_id'        => Auth::user()['id'],
        ]);
        $solicitud = $this->solicitudes->find($id);

        $mensaje = sprintf(
            'Solicitud #%d radicada para %s %s. Fecha límite de atención: %s.',
            $id,
            $datos['nombres'],
            $datos['apellidos'],
            date('d/m/Y', strtotime((string) $solicitud['fecha_limite']))
        );
        if ($datos['latitud'] === null) {
            $mensaje .= ' Quedó sin georreferenciar: puedes ubicarla desde el listado.';
        }

        Session::flash('success', $mensaje);
        redirect('/solicitudes');
    }

    public function editUbicacion(string $id): void
    {
        $solicitud = $this->solicitudDelPunto((int) $id);

        View::render('solicitudes/ubicacion', [
            'title'     => 'Georreferenciar solicitud',
            'solicitud' => $solicitud,
            'error'     => null,
            'mapa'      => Ubicacion::config(),
            'conMapa'   => true,
        ]);
    }

    public function updateUbicacion(string $id): void
    {
        $solicitud = $this->solicitudDelPunto((int) $id);
        $ubicacion = Ubicacion::validar($_POST['latitud'] ?? '', $_POST['longitud'] ?? '');

        if ($ubicacion['error'] !== null || $ubicacion['lat'] === null) {
            http_response_code(422);
            View::render('solicitudes/ubicacion', [
                'title'     => 'Georreferenciar solicitud',
                'solicitud' => $solicitud,
                'error'     => $ubicacion['error'] ?? 'Marca la ubicación en el mapa antes de guardar.',
                'mapa'      => Ubicacion::config(),
                'conMapa'   => true,
            ]);

            return;
        }

        $this->solicitudes->actualizarUbicacion((int) $id, $ubicacion['lat'], $ubicacion['lon']);
        Session::flash('success', "Solicitud #{$id} georreferenciada.");
        redirect('/solicitudes');
    }

    /**
     * @param array<string, mixed>  $old
     * @param array<string, string> $errores
     */
    private function renderForm(array $old, array $errores): void
    {
        $ccId = (int) ($old['comuna_corregimiento_id'] ?? 0);

        View::render('solicitudes/create', [
            'title'      => 'Nueva solicitud',
            'old'        => $old,
            'errores'    => $errores,
            'divisiones' => $this->catalogo->comunasCorregimientos(),
            'barrios'    => $ccId > 0 ? $this->catalogo->barriosDe($ccId) : [],
            'mapa'       => Ubicacion::config(),
            'conMapa'    => true,
        ]);
    }

    private function puntoActual(): int
    {
        $punto = Auth::user()['punto_atencion_id'] ?? null;

        if ($punto === null) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }

        return $punto;
    }

    /**
     * Una ventanilla solo puede ver y georreferenciar solicitudes de su punto.
     *
     * @return array<string, mixed>
     */
    private function solicitudDelPunto(int $id): array
    {
        $solicitud = $this->solicitudes->find($id);

        if ($solicitud === null || (int) $solicitud['punto_atencion_id'] !== $this->puntoActual()) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Solicitud no encontrada']);
            exit;
        }

        return $solicitud;
    }
}
