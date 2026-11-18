<?php /** @var list<array<string, mixed>> $usuarios */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Usuarios</h1>
    <a class="btn btn-primary" href="/usuarios/nuevo">Nuevo usuario</a>
</div>

<div class="card app-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th>Documento</th>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Perfil</th>
                <th>Punto de atención</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= e($u['documento']) ?></td>
                    <td><?= e($u['nombres'] . ' ' . $u['apellidos']) ?></td>
                    <td><?= e($u['correo']) ?></td>
                    <td><span class="badge app-badge-perfil-claro"><?= e($u['perfil']) ?></span></td>
                    <td><?= e($u['punto_nombre'] ?? '—') ?></td>
                    <td>
                        <?php if ((int) $u['activo'] === 1): ?>
                            <span class="badge text-bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
