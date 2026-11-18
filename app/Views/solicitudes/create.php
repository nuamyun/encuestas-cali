<?php
/** @var array<string, mixed> $old */
/** @var array<string, string> $errores */
/** @var list<array{id: int, tipo: string, numero: ?int, nombre: string}> $divisiones */
/** @var list<array{id: int, nombre: string}> $barrios */
/** @var array<string, float> $mapa */
use App\Core\Csrf;

$invalido = static fn (string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error    = static fn (string $campo): string => isset($errores[$campo])
    ? '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>' : '';
$ccSel     = (int) ($old['comuna_corregimiento_id'] ?? 0);
$barrioSel = (int) ($old['barrio_id'] ?? 0);
$comunas        = array_filter($divisiones, static fn ($d) => $d['tipo'] === 'comuna');
$corregimientos = array_filter($divisiones, static fn ($d) => $d['tipo'] === 'corregimiento');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Nueva solicitud de encuesta</h1>
    <a class="btn btn-outline-secondary" href="/solicitudes">Ver solicitudes</a>
</div>

<?php if ($errores !== []): ?>
    <div class="alert alert-danger">Revisa los campos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="/solicitudes" novalidate id="form-solicitud">
    <?= Csrf::field() ?>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Datos del solicitante</h2>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="nombres" class="form-label">Nombres</label>
                            <input type="text" class="form-control<?= $invalido('nombres') ?>" id="nombres" name="nombres"
                                   value="<?= e($old['nombres'] ?? '') ?>" maxlength="80" required autofocus>
                            <?= $error('nombres') ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control<?= $invalido('apellidos') ?>" id="apellidos" name="apellidos"
                                   value="<?= e($old['apellidos'] ?? '') ?>" maxlength="80" required>
                            <?= $error('apellidos') ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="documento" class="form-label">Número de documento</label>
                            <input type="text" inputmode="numeric" class="form-control<?= $invalido('documento') ?>" id="documento"
                                   name="documento" value="<?= e($old['documento'] ?? '') ?>" maxlength="20"
                                   placeholder="1144123456" required>
                            <?= $error('documento') ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="celular" class="form-label">Número de celular</label>
                            <input type="tel" inputmode="numeric" class="form-control<?= $invalido('celular') ?>" id="celular"
                                   name="celular" value="<?= e($old['celular'] ?? '') ?>" maxlength="16"
                                   placeholder="3001234567" required>
                            <?= $error('celular') ?>
                        </div>
                        <div class="col-12">
                            <label for="comuna_corregimiento_id" class="form-label">Comuna o corregimiento</label>
                            <select class="form-select<?= $invalido('comuna_corregimiento_id') ?>" id="comuna_corregimiento_id"
                                    name="comuna_corregimiento_id" required>
                                <option value="">Selecciona…</option>
                                <optgroup label="Zona urbana — comunas">
                                    <?php foreach ($comunas as $d): ?>
                                        <option value="<?= (int) $d['id'] ?>"<?= $ccSel === (int) $d['id'] ? ' selected' : '' ?>><?= e($d['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Zona rural — corregimientos">
                                    <?php foreach ($corregimientos as $d): ?>
                                        <option value="<?= (int) $d['id'] ?>"<?= $ccSel === (int) $d['id'] ? ' selected' : '' ?>><?= e($d['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <?= $error('comuna_corregimiento_id') ?>
                        </div>
                        <div class="col-12">
                            <label for="barrio_id" class="form-label">Barrio o vereda</label>
                            <select class="form-select<?= $invalido('barrio_id') ?>" id="barrio_id" name="barrio_id" required
                                <?= $ccSel === 0 ? 'disabled' : '' ?>>
                                <option value=""><?= $ccSel === 0 ? 'Primero selecciona la comuna o el corregimiento' : 'Selecciona…' ?></option>
                                <?php foreach ($barrios as $b): ?>
                                    <option value="<?= (int) $b['id'] ?>"<?= $barrioSel === (int) $b['id'] ? ' selected' : '' ?>><?= e($b['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= $error('barrio_id') ?>
                            <div class="form-text" id="barrio-ayuda">Solo aparecen los barrios de la comuna o corregimiento elegido.</div>
                        </div>
                        <div class="col-12">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control<?= $invalido('direccion') ?>" id="direccion" name="direccion"
                                   value="<?= e($old['direccion'] ?? '') ?>" maxlength="150"
                                   placeholder="Calle 5 # 38-25" required>
                            <?= $error('direccion') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card app-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h5 mb-0">Georreferenciación</h2>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-ubicar">Ubicar dirección en el mapa</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-quitar">Quitar</button>
                        </div>
                    </div>
                    <div id="mapa" class="app-mapa<?= isset($errores['ubicacion']) ? ' border border-danger' : '' ?>"></div>
                    <input type="hidden" id="latitud" name="latitud" value="<?= e($old['latitud'] ?? '') ?>">
                    <input type="hidden" id="longitud" name="longitud" value="<?= e($old['longitud'] ?? '') ?>">
                    <?php if (isset($errores['ubicacion'])): ?>
                        <div class="text-danger small mt-2"><?= e($errores['ubicacion']) ?></div>
                    <?php endif; ?>
                    <div id="mapa-estado" class="small mt-2 text-secondary"></div>
                    <div class="form-text">Puedes guardar sin ubicación y georreferenciar la solicitud después desde el listado.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">Radicar solicitud</button>
        <a class="btn btn-outline-secondary" href="/inicio">Cancelar</a>
    </div>
</form>

<script>
(function () {
    const comuna = document.getElementById('comuna_corregimiento_id');
    const barrio = document.getElementById('barrio_id');
    const ayuda = document.getElementById('barrio-ayuda');

    function opcion(valor, texto) {
        const o = document.createElement('option');
        o.value = valor;
        o.textContent = texto;
        return o;
    }

    async function cargarBarrios() {
        barrio.replaceChildren(opcion('', 'Cargando…'));
        barrio.disabled = true;
        if (!comuna.value) {
            barrio.replaceChildren(opcion('', 'Primero selecciona la comuna o el corregimiento'));
            return;
        }
        try {
            const r = await fetch('/api/barrios?comuna_corregimiento_id=' + encodeURIComponent(comuna.value));
            const datos = await r.json();
            barrio.replaceChildren(opcion('', datos.barrios.length ? 'Selecciona…' : 'No hay barrios cargados'));
            datos.barrios.forEach(function (b) { barrio.appendChild(opcion(b.id, b.nombre)); });
            barrio.disabled = datos.barrios.length === 0;
            ayuda.textContent = datos.barrios.length + ' barrios o veredas en ' + comuna.options[comuna.selectedIndex].text + '.';
        } catch (e) {
            barrio.replaceChildren(opcion('', 'No se pudieron cargar los barrios'));
        }
    }

    comuna.addEventListener('change', cargarBarrios);

    const selector = MapaCali.selector({
        contenedor: 'mapa',
        latInput: 'latitud',
        lonInput: 'longitud',
        estado: 'mapa-estado',
        config: <?= json_encode($mapa, JSON_THROW_ON_ERROR) ?>
    });

    document.getElementById('btn-quitar').addEventListener('click', selector.quitar);

    document.getElementById('btn-ubicar').addEventListener('click', function () {
        const direccion = MapaCali.limpiarDireccion(document.getElementById('direccion').value);
        const nombreBarrio = barrio.value ? barrio.options[barrio.selectedIndex].text : '';
        const intentos = [];
        if (direccion) {
            intentos.push({ texto: 'Dirección encontrada.', consulta: direccion + ', Cali, Valle del Cauca' });
        }
        if (nombreBarrio) {
            intentos.push({ texto: 'No se encontró la dirección exacta; el marcador quedó en el barrio ' + nombreBarrio + '.', consulta: nombreBarrio + ', Cali, Valle del Cauca' });
        }
        if (!intentos.length) {
            document.getElementById('mapa-estado').textContent = 'Escribe la dirección o elige el barrio antes de ubicar.';
            return;
        }
        selector.buscar(intentos);
    });
})();
</script>
