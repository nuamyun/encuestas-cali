<?php
/** @var array<string, mixed> $app */
/** @var list<array{capa: string, nombre: string, version: string, origen: string}> $tecnologias */
/** @var array<string, string> $conexion */
/** @var bool $conectada */
?>
<div class="mb-4">
    <h1 class="h3 mb-1">Sistema</h1>
    <p class="text-secondary mb-0">Tecnologías y versiones que están corriendo en este momento, y cómo se conecta la aplicación a la base de datos.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card app-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Tecnologías y versiones</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Capa</th>
                            <th>Tecnología</th>
                            <th>Versión</th>
                            <th>De dónde sale</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($tecnologias as $t): ?>
                            <tr>
                                <td class="text-secondary"><?= e($t['capa']) ?></td>
                                <td class="fw-semibold"><?= e($t['nombre']) ?></td>
                                <td><code><?= e($t['version']) ?></code></td>
                                <td class="small text-secondary"><?= e($t['origen']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="small text-secondary mt-3 mb-0">
                    Las versiones de PHP, Apache y MySQL se leen del servidor en ejecución.
                    Herramientas de desarrollo: Git, GitHub Actions, Docker Compose, PHPUnit y Jira.
                </p>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card app-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Conexión a la base de datos</h2>
                <dl class="row mb-0">
                    <?php foreach ($conexion as $clave => $valor): ?>
                        <dt class="col-sm-5 text-secondary fw-normal"><?= e($clave) ?></dt>
                        <dd class="col-sm-7">
                            <?php if ($clave === 'Estado'): ?>
                                <span class="badge <?= $conectada ? 'text-bg-success' : 'text-bg-danger' ?>"><?= e($valor) ?></span>
                            <?php else: ?>
                                <code><?= e($valor) ?></code>
                            <?php endif; ?>
                        </dd>
                    <?php endforeach; ?>
                </dl>
                <hr>
                <p class="small text-secondary mb-0">
                    Recorrido: <code>.env</code> → <code>docker-compose.yml</code> → variables de entorno →
                    <code>config/database.php</code> → <code>app/Core/Database.php</code> → MySQL.
                    La contraseña no se muestra.
                </p>
            </div>
        </div>
    </div>
</div>
