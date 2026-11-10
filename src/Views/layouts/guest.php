<?php
/** @var string $content */
/** @var string|null $title */
use App\Core\Session;

$flashes = Session::pullFlash();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Iniciar sesión') ?> · Encuestas Cali</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="app-guest">
<main class="container">
    <div class="row justify-content-center align-items-center min-vh-100 py-4">
        <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <?php foreach ($flashes as $flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>

            <?= $content ?>
        </div>
    </div>
</main>
</body>
</html>
