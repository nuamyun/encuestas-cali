<?php
/** @var \App\Models\Reglas\BitacoraFiltro $filtro */
/** @var list<array{id: int, nombre: string}> $puntos */
/** @var list<array<string, mixed>> $solicitudes */
/** @var array{total: int, urbanas: int, rurales: int, sinUbicacion: int, porEstado: array<string, int>, porPunto: array<string, int>} $resumen */
/** @var list<array{lat: float, lon: float, titulo: string, detalle: string}> $marcadores */
/** @var array<string, float> $mapa */
use App\Core\Csrf;
use App\Models\Reglas\EstadoSolicitud;

$fechaTexto = $filtro->fecha->format('d/m/Y');
$hoy = date('Y-m-d');

// Enlace a la bitácora conservando día y punto, con otro estado
$urlEstado = static function (?string $estado) use ($filtro): string {
    return '/bitacora?' . http_build_query(array_filter([
        'fecha'  => $filtro->fecha->format('Y-m-d'),
        'punto'  => $filtro->puntoId,
        'estado' => $estado,
    ]));
};

// Campos ocultos que todo formulario de cambio de estado envía
$camposRegreso = Csrf::field()
    . '<input type="hidden" name="fecha" value="' . e($filtro->fecha->format('Y-m-d')) . '">'
    . '<input type="hidden" name="punto" value="' . e($filtro->puntoId) . '">'
    . '<input type="hidden" name="filtro_estado" value="' . e($filtro->estado) . '">';

$tarjetas = [
    [null, 'Solicitudes del día', $resumen['total'], ''],
    [EstadoSolicitud::PENDIENTE, 'Pendientes', $resumen['porEstado']['pendiente'], 'app-estado-pendiente'],
    [EstadoSolicitud::LISTA, 'Listas', $resumen['porEstado']['lista'], 'app-estado-lista'],
    [EstadoSolicitud::RECHAZADA, 'Rechazadas', $resumen['porEstado']['rechazada'], 'app-estado-rechazada'],
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
    <div>
        <h1 class="h3 mb-0">Bitácora diaria</h1>
        <p class="text-secondary mb-0">Solicitudes radicadas el <?= e($fechaTexto) ?> en <?= $filtro->puntoId ? 'el punto seleccionado' : 'todos los puntos de atención' ?>.</p>
    </div>
    <form method="get" action="/bitacora" class="d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label for="fecha" class="form-label small mb-1">Día</label>
            <input type="date" class="form-control form-control-sm" id="fecha" name="fecha"
                   value="<?= e($filtro->fecha->format('Y-m-d')) ?>" max="<?= e($hoy) ?>">
        </div>
        <div>
            <label for="punto" class="form-label small mb-1">Punto de atención</label>
            <select class="form-select form-select-sm" id="punto" name="punto">
                <option value="">Todos los puntos</option>
                <?php foreach ($puntos as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"<?= $filtro->puntoId === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="estado" class="form-label small mb-1">Estado</label>
            <select class="form-select form-select-sm" id="estado" name="estado">
                <option value="">Todos</option>
                <?php foreach (EstadoSolicitud::ETIQUETAS as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>"<?= $filtro->estado === $valor ? ' selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Consultar</button>
        <a class="btn btn-sm btn-outline-secondary" href="/bitacora">Hoy</a>
    </form>
</div>

<div class="row g-3 mb-2">
    <?php foreach ($tarjetas as [$estado, $titulo, $cantidad, $clase]): ?>
        <div class="col-6 col-lg-3">
            <a href="<?= e($urlEstado($estado)) ?>"
               class="card app-card app-card-link h-100 <?= e($clase) ?><?= $filtro->estado === $estado ? ' app-card-activa' : '' ?>">
                <div class="card-body">
                    <div class="text-secondary small"><?= e($titulo) ?></div>
                    <div class="fs-3 fw-semibold"><?= (int) $cantidad ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<p class="small text-secondary mb-4">
    Zona urbana: <?= $resumen['urbanas'] ?> · Zona rural: <?= $resumen['rurales'] ?> ·
    Sin georreferenciar: <span class="<?= $resumen['sinUbicacion'] > 0 ? 'text-warning-emphasis fw-semibold' : '' ?>"><?= $resumen['sinUbicacion'] ?></span>
</p>

<?php if ($resumen['total'] > 0): ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card app-card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Ubicación de las solicitudes georreferenciadas (<?= count($marcadores) ?>)</h2>
                    <div id="mapa-bitacora" class="app-mapa"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">Por punto de atención</h2>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($resumen['porPunto'] as $punto => $cantidad): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span><?= e($punto) ?></span>
                                <span class="badge app-badge-perfil-claro"><?= (int) $cantidad ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card app-card">
    <?php if ($solicitudes === []): ?>
        <div class="card-body text-secondary">
            <?= $resumen['total'] === 0 ? 'No se radicaron solicitudes en este día.' : 'No hay solicitudes con ese estado en este día.' ?>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Hora / #</th>
                    <th>Punto de atención</th>
                    <th>Solicitante</th>
                    <th>Dirección</th>
                    <th>Ubicación</th>
                    <th>Fecha límite</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitudes as $s): ?>
                    <tr>
                        <td class="text-nowrap">
                            <?= e(date('H:i', strtotime((string) $s['fecha_radicacion']))) ?><br>
                            <span class="small text-secondary">#<?= (int) $s['id'] ?></span>
                        </td>
                        <td>
                            <?= e($s['punto']) ?><br>
                            <span class="small text-secondary">Radicó: <?= e($s['usuario_nombres'] . ' ' . $s['usuario_apellidos']) ?></span>
                        </td>
                        <td>
                            <?= e($s['nombres'] . ' ' . $s['apellidos']) ?><br>
                            <span class="small text-secondary">Doc. <?= e($s['documento']) ?> · Cel. <?= e($s['celular']) ?></span>
                        </td>
                        <td>
                            <?= e($s['direccion']) ?><br>
                            <span class="small text-secondary"><?= e($s['barrio']) ?> · <?= e($s['comuna']) ?> · <?= $s['tipo'] === 'comuna' ? 'urbana' : 'rural' ?></span>
                        </td>
                        <td>
                            <?php if ($s['latitud'] === null): ?>
                                <span class="badge text-bg-warning">Sin georreferenciar</span>
                            <?php else: ?>
                                <span class="small text-secondary"><?= e($s['latitud']) ?>, <?= e($s['longitud']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= e(date('d/m/Y', strtotime((string) $s['fecha_limite']))) ?></td>
                        <td>
                            <?= estado_badge((string) $s['estado']) ?>
                            <?php if ($s['estado'] === 'rechazada' && $s['motivo_rechazo']): ?>
                                <div class="small text-secondary mt-1">Motivo: <?= e($s['motivo_rechazo']) ?></div>
                            <?php endif; ?>
                            <?php if ($s['revisado_en']): ?>
                                <div class="small text-secondary mt-1">
                                    <?= e($s['revisor_nombres']) ?>, <?= e(date('d/m H:i', strtotime((string) $s['revisado_en']))) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <?php if ($s['estado'] !== 'lista'): ?>
                                <form method="post" action="/bitacora/solicitudes/<?= (int) $s['id'] ?>/estado" class="d-inline">
                                    <?= $camposRegreso ?>
                                    <input type="hidden" name="estado" value="lista">
                                    <button type="submit" class="btn btn-sm btn-outline-success">Lista</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($s['estado'] !== 'rechazada'): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal" data-bs-target="#modal-rechazo"
                                        data-id="<?= (int) $s['id'] ?>"
                                        data-nombre="<?= e($s['nombres'] . ' ' . $s['apellidos']) ?>">Rechazar</button>
                            <?php endif; ?>
                            <?php if ($s['estado'] !== 'pendiente'): ?>
                                <form method="post" action="/bitacora/solicitudes/<?= (int) $s['id'] ?>/estado" class="d-inline">
                                    <?= $camposRegreso ?>
                                    <input type="hidden" name="estado" value="pendiente">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Pendiente</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modal-rechazo" tabindex="-1" aria-labelledby="modal-rechazo-titulo" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="" class="modal-content" id="form-rechazo">
            <?= $camposRegreso ?>
            <input type="hidden" name="estado" value="rechazada">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modal-rechazo-titulo">Rechazar solicitud</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="rechazo-solicitante"></p>
                <label for="motivo" class="form-label">Motivo del rechazo</label>
                <textarea class="form-control" id="motivo" name="motivo" rows="3" minlength="5" maxlength="255" required
                          placeholder="Ejemplo: la dirección no existe o está fuera del área de cobertura"></textarea>
                <div class="form-text">Obligatorio, de 5 a 255 caracteres. La ventanilla lo verá en su listado.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Rechazar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('modal-rechazo').addEventListener('show.bs.modal', function (evento) {
    const boton = evento.relatedTarget;
    document.getElementById('form-rechazo').action = '/bitacora/solicitudes/' + boton.dataset.id + '/estado';
    document.getElementById('rechazo-solicitante').textContent = 'Solicitud #' + boton.dataset.id + ' de ' + boton.dataset.nombre + '.';
    document.getElementById('motivo').value = '';
});
</script>

<?php if ($resumen['total'] > 0): ?>
<script>
(function () {
    const cfg = <?= json_encode($mapa, JSON_THROW_ON_ERROR) ?>;
    const marcadores = <?= json_encode($marcadores, JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
    const mapa = L.map('mapa-bitacora', { scrollWheelZoom: false }).setView([cfg.centroLat, cfg.centroLon], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(mapa);

    const puntos = marcadores.map(function (m) {
        const popup = document.createElement('div');
        const titulo = document.createElement('strong');
        titulo.textContent = m.titulo;
        popup.append(titulo, document.createElement('br'), m.detalle);
        L.marker([m.lat, m.lon]).addTo(mapa).bindPopup(popup);
        return [m.lat, m.lon];
    });
    if (puntos.length) {
        mapa.fitBounds(puntos, { padding: [30, 30], maxZoom: 15 });
    }
    setTimeout(function () { mapa.invalidateSize(); }, 200);
})();
</script>
<?php endif; ?>
