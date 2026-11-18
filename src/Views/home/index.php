<?php /** @var bool $dbOnline */ ?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card app-card">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Sistema de solicitudes de encuesta</h1>
                <p class="text-secondary mb-4">Atención, georreferenciación y enrutamiento de solicitudes en Santiago de Cali.</p>

                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Aplicación PHP
                        <span class="badge text-bg-success">En línea</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Base de datos MySQL
                        <?php if ($dbOnline): ?>
                            <span class="badge text-bg-success">Conectada</span>
                        <?php else: ?>
                            <span class="badge text-bg-danger">Sin conexión</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
