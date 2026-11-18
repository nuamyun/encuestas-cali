<?php
/** @var \App\Validation\BitacoraFiltro $filtro */
/** @var list<array{id: int, nombre: string}> $puntos */
/** @var list<array<string, mixed>> $solicitudes */
/** @var array{total: int, urbanas: int, rurales: int, sinUbicacion: int, porPunto: array<string, int>} $resumen */
/** @var list<array{lat: float, lon: float, titulo: string, detalle: string}> $marcadores */
/** @var array<string, float> $mapa */
$fechaTexto = $filtro->fecha->format('d/m/Y');
$hoy = date('Y-m-d');
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
        <button type="submit" class="btn btn-sm btn-primary">Consultar</button>
        <a class="btn btn-sm btn-outline-secondary" href="/bitacora">Hoy</a>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card app-card h-100"><div class="card-body">
            <div class="text-secondary small">Solicitudes del día</div>
            <div class="fs-3 fw-semibold"><?= $resumen['total'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card app-card h-100"><div class="card-body">
            <div class="text-secondary small">Zona urbana</div>
            <div class="fs-3 fw-semibold"><?= $resumen['urbanas'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card app-card h-100"><div class="card-body">
            <div class="text-secondary small">Zona rural</div>
            <div class="fs-3 fw-semibold"><?= $resumen['rurales'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card app-card h-100"><div class="card-body">
            <div class="text-secondary small">Sin georreferenciar</div>
            <div class="fs-3 fw-semibold<?= $resumen['sinUbicacion'] > 0 ? ' text-warning' : '' ?>"><?= $resumen['sinUbicacion'] ?></div>
        </div></div>
    </div>
</div>

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
        <div class="card-body text-secondary">No se radicaron solicitudes en este día.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>Hora</th>
                    <th>#</th>
                    <th>Punto de atención</th>
                    <th>Solicitante</th>
                    <th>Celular</th>
                    <th>Dirección</th>
                    <th>Barrio</th>
                    <th>Comuna / corregimiento</th>
                    <th>Zona</th>
                    <th>Ubicación</th>
                    <th>Fecha límite</th>
                    <th>Radicó</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitudes as $s): ?>
                    <tr>
                        <td><?= e(date('H:i', strtotime((string) $s['fecha_radicacion']))) ?></td>
                        <td><?= (int) $s['id'] ?></td>
                        <td><?= e($s['punto']) ?></td>
                        <td><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></td>
                        <td><?= e($s['celular']) ?></td>
                        <td><?= e($s['direccion']) ?></td>
                        <td><?= e($s['barrio']) ?></td>
                        <td><?= e($s['comuna']) ?></td>
                        <td><?= $s['tipo'] === 'comuna' ? 'Urbana' : 'Rural' ?></td>
                        <td>
                            <?php if ($s['latitud'] === null): ?>
                                <span class="badge text-bg-warning">Sin georreferenciar</span>
                            <?php else: ?>
                                <span class="small text-secondary"><?= e($s['latitud']) ?>, <?= e($s['longitud']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap"><?= e(date('d/m/Y', strtotime((string) $s['fecha_limite']))) ?></td>
                        <td class="small"><?= e($s['usuario_nombres'] . ' ' . $s['usuario_apellidos']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

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
