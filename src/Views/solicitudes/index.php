<?php
/** @var array{punto_nombre: ?string} $usuario */
/** @var list<array<string, mixed>> $solicitudes */
/** @var bool $soloPendientes */
$hoy = date('Y-m-d');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-0">Solicitudes del punto</h1>
        <p class="text-secondary mb-0"><?= e($usuario['punto_nombre'] ?? '') ?></p>
    </div>
    <a class="btn btn-primary" href="/solicitudes/nueva">Nueva solicitud</a>
</div>

<div class="btn-group mb-3" role="group" aria-label="Filtro">
    <a class="btn btn-sm <?= $soloPendientes ? 'btn-outline-primary' : 'btn-primary' ?>" href="/solicitudes">Todas</a>
    <a class="btn btn-sm <?= $soloPendientes ? 'btn-primary' : 'btn-outline-primary' ?>" href="/solicitudes?pendientes=1">Sin georreferenciar</a>
</div>

<div class="card app-card">
    <?php if ($solicitudes === []): ?>
        <div class="card-body text-secondary">No hay solicitudes para mostrar.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Radicada</th>
                    <th>Solicitante</th>
                    <th>Celular</th>
                    <th>Dirección</th>
                    <th>Barrio / Comuna</th>
                    <th>Fecha límite</th>
                    <th>Ubicación</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitudes as $s): ?>
                    <tr>
                        <td><?= (int) $s['id'] ?></td>
                        <td class="text-nowrap"><?= e(date('d/m/Y H:i', strtotime((string) $s['fecha_radicacion']))) ?></td>
                        <td><?= e($s['nombres'] . ' ' . $s['apellidos']) ?></td>
                        <td><?= e($s['celular']) ?></td>
                        <td><?= e($s['direccion']) ?></td>
                        <td>
                            <?= e($s['barrio']) ?><br>
                            <span class="small text-secondary"><?= e($s['comuna']) ?> · <?= $s['tipo'] === 'comuna' ? 'urbana' : 'rural' ?></span>
                        </td>
                        <td class="text-nowrap<?= (string) $s['fecha_limite'] < $hoy ? ' text-danger' : '' ?>">
                            <?= e(date('d/m/Y', strtotime((string) $s['fecha_limite']))) ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if ($s['latitud'] === null): ?>
                                <span class="badge text-bg-warning">Sin georreferenciar</span>
                                <a class="btn btn-sm btn-outline-primary ms-1" href="/solicitudes/<?= (int) $s['id'] ?>/ubicacion">Ubicar</a>
                            <?php else: ?>
                                <span class="badge text-bg-success">Georreferenciada</span>
                                <a class="btn btn-sm btn-link" href="/solicitudes/<?= (int) $s['id'] ?>/ubicacion">Ajustar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
