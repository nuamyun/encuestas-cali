<?php
/** @var string $correo */
/** @var string|null $error */
use App\Core\Csrf;
?>
<div class="card app-card">
    <div class="card-body p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="app-logo mb-3" aria-hidden="true">EC</div>
            <h1 class="h4 mb-1">Encuestas Cali</h1>
            <p class="text-secondary small mb-0">Ingresa con tu correo institucional</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/login" novalidate>
            <?= Csrf::field() ?>
            <div class="mb-3">
                <label for="correo" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="correo" name="correo"
                       value="<?= e($correo) ?>" autocomplete="username" required autofocus>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password"
                       autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
        </form>
    </div>
</div>
