<?php
/** @var array<string, mixed> $old */
/** @var array<string, string> $errores */
/** @var list<array{id: int, nombre: string}> $perfiles */
/** @var list<array{id: int, nombre: string}> $puntos */
/** @var int $perfilVentanilla */
use App\Core\Csrf;

$invalido = static fn (string $campo): string => isset($errores[$campo]) ? ' is-invalid' : '';
$error    = static fn (string $campo): string => isset($errores[$campo])
    ? '<div class="invalid-feedback">' . e($errores[$campo]) . '</div>' : '';
$perfilSel = (int) ($old['perfil_id'] ?? 0);
$puntoSel  = (int) ($old['punto_atencion_id'] ?? 0);
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/usuarios">Usuarios</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nuevo usuario</li>
    </ol>
</nav>

<div class="card app-card">
    <div class="card-body p-4">
        <h1 class="h4 mb-4">Nuevo usuario</h1>

        <form method="post" action="/usuarios" novalidate>
            <?= Csrf::field() ?>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombres" class="form-label">Nombres</label>
                    <input type="text" class="form-control<?= $invalido('nombres') ?>" id="nombres" name="nombres"
                           value="<?= e($old['nombres'] ?? '') ?>" maxlength="80" required>
                    <?= $error('nombres') ?>
                </div>
                <div class="col-md-6">
                    <label for="apellidos" class="form-label">Apellidos</label>
                    <input type="text" class="form-control<?= $invalido('apellidos') ?>" id="apellidos" name="apellidos"
                           value="<?= e($old['apellidos'] ?? '') ?>" maxlength="80" required>
                    <?= $error('apellidos') ?>
                </div>
                <div class="col-md-6">
                    <label for="documento" class="form-label">Documento</label>
                    <input type="text" inputmode="numeric" class="form-control<?= $invalido('documento') ?>" id="documento"
                           name="documento" value="<?= e($old['documento'] ?? '') ?>" maxlength="15" required>
                    <?= $error('documento') ?>
                </div>
                <div class="col-md-6">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control<?= $invalido('correo') ?>" id="correo" name="correo"
                           value="<?= e($old['correo'] ?? '') ?>" maxlength="120" required>
                    <?= $error('correo') ?>
                </div>
                <div class="col-md-6">
                    <label for="perfil_id" class="form-label">Perfil</label>
                    <select class="form-select<?= $invalido('perfil_id') ?>" id="perfil_id" name="perfil_id" required>
                        <option value="">Selecciona…</option>
                        <?php foreach ($perfiles as $perfil): ?>
                            <option value="<?= (int) $perfil['id'] ?>"<?= $perfilSel === (int) $perfil['id'] ? ' selected' : '' ?>>
                                <?= e(ucfirst($perfil['nombre'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $error('perfil_id') ?>
                </div>
                <div class="col-md-6" id="grupo-punto">
                    <label for="punto_atencion_id" class="form-label">Punto de atención</label>
                    <select class="form-select<?= $invalido('punto_atencion_id') ?>" id="punto_atencion_id" name="punto_atencion_id">
                        <option value="">Selecciona…</option>
                        <?php foreach ($puntos as $punto): ?>
                            <option value="<?= (int) $punto['id'] ?>"<?= $puntoSel === (int) $punto['id'] ? ' selected' : '' ?>>
                                <?= e($punto['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Obligatorio solo para el perfil ventanilla.</div>
                    <?= $error('punto_atencion_id') ?>
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control<?= $invalido('password') ?>" id="password" name="password"
                           minlength="8" autocomplete="new-password" required>
                    <div class="form-text">Mínimo 8 caracteres.</div>
                    <?= $error('password') ?>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">Guardar usuario</button>
                <a class="btn btn-outline-secondary" href="/usuarios">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const perfil = document.getElementById('perfil_id');
        const grupo = document.getElementById('grupo-punto');
        const ventanilla = '<?= (int) $perfilVentanilla ?>';
        const actualizar = () => { grupo.hidden = perfil.value !== ventanilla; };
        perfil.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
