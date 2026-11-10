<?php
/** @var array{nombres: string, perfil: string, punto_nombre: ?string} $usuario */
/** @var list<array{titulo: string, ruta: string, descripcion: string}> $modulos */
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Hola, <?= e($usuario['nombres']) ?></h1>
    <p class="text-secondary mb-0">
        Perfil: <strong><?= e($usuario['perfil']) ?></strong>
        <?php if ($usuario['punto_nombre']): ?>
            · Punto de atención: <strong><?= e($usuario['punto_nombre']) ?></strong>
        <?php endif; ?>
    </p>
</div>

<?php if ($modulos === []): ?>
    <div class="alert alert-info">Tu perfil todavía no tiene módulos habilitados en esta etapa del sistema.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($modulos as $modulo): ?>
            <div class="col-sm-6 col-lg-4">
                <a class="card app-card app-card-link h-100" href="<?= e($modulo['ruta']) ?>">
                    <div class="card-body">
                        <h2 class="h5"><?= e($modulo['titulo']) ?></h2>
                        <p class="text-secondary mb-0"><?= e($modulo['descripcion']) ?></p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
