<?php
/** @var string $content */
/** @var string|null $title */
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Menu;
use App\Core\Session;

$flashes  = Session::pullFlash();
$usuario  = Auth::user();
$opciones = $usuario ? Menu::para($usuario['perfil']) : [];
$actual   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Inicio') ?> · Encuestas Cali</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <?= $head ?? '' ?>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar">
    <div class="container">
        <a class="navbar-brand" href="/inicio">Encuestas Cali</a>
        <?php if ($usuario): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                    aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="menu">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link<?= $actual === '/inicio' ? ' active' : '' ?>" href="/inicio">Inicio</a></li>
                    <?php foreach ($opciones as $opcion): ?>
                        <li class="nav-item">
                            <a class="nav-link<?= str_starts_with((string) $actual, $opcion['ruta']) ? ' active' : '' ?>"
                               href="<?= e($opcion['ruta']) ?>"><?= e($opcion['titulo']) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    <span class="navbar-text small">
                        <?= e($usuario['nombres']) ?>
                        <span class="badge app-badge-perfil"><?= e($usuario['perfil']) ?></span>
                    </span>
                    <form method="post" action="/logout" class="m-0">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-light">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container py-4">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $scripts ?? '' ?>
</body>
</html>
