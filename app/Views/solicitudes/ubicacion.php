<?php
/** @var array<string, mixed> $solicitud */
/** @var string|null $error */
/** @var array<string, float> $mapa */
use App\Core\Csrf;
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/solicitudes">Solicitudes</a></li>
        <li class="breadcrumb-item active" aria-current="page">Georreferenciar #<?= (int) $solicitud['id'] ?></li>
    </ol>
</nav>

<div class="card app-card">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Solicitud #<?= (int) $solicitud['id'] ?> · <?= e($solicitud['nombres'] . ' ' . $solicitud['apellidos']) ?></h1>
                <p class="text-secondary mb-0">
                    <?= e($solicitud['direccion']) ?> · <?= e($solicitud['barrio']) ?> · <?= e($solicitud['comuna']) ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-ubicar">Ubicar dirección en el mapa</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/solicitudes/<?= (int) $solicitud['id'] ?>/ubicacion">
            <?= Csrf::field() ?>
            <div id="mapa" class="app-mapa"></div>
            <input type="hidden" id="latitud" name="latitud" value="<?= e($solicitud['latitud'] ?? '') ?>">
            <input type="hidden" id="longitud" name="longitud" value="<?= e($solicitud['longitud'] ?? '') ?>">
            <div id="mapa-estado" class="small mt-2 text-secondary"></div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Guardar ubicación</button>
                <a class="btn btn-outline-secondary" href="/solicitudes">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const selector = MapaCali.selector({
        contenedor: 'mapa',
        latInput: 'latitud',
        lonInput: 'longitud',
        estado: 'mapa-estado',
        config: <?= json_encode($mapa, JSON_THROW_ON_ERROR) ?>
    });
    const direccion = <?= json_encode((string) $solicitud['direccion'], JSON_THROW_ON_ERROR | JSON_HEX_TAG) ?>;
    const barrio = <?= json_encode((string) $solicitud['barrio'], JSON_THROW_ON_ERROR | JSON_HEX_TAG) ?>;

    document.getElementById('btn-ubicar').addEventListener('click', function () {
        selector.buscar([
            { texto: 'Dirección encontrada.', consulta: MapaCali.limpiarDireccion(direccion) + ', Cali, Valle del Cauca' },
            { texto: 'No se encontró la dirección exacta; el marcador quedó en el barrio ' + barrio + '.', consulta: barrio + ', Cali, Valle del Cauca' }
        ]);
    });
})();
</script>
