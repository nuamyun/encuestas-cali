<?php

declare(strict_types=1);

/**
 * Escapa un valor para imprimirlo en HTML (previene XSS).
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Redirige a una ruta interna y termina la ejecución.
 */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Etiqueta de color para el estado de una solicitud.
 */
function estado_badge(string $estado): string
{
    $clases = [
        'pendiente' => ['text-bg-secondary', 'Pendiente'],
        'lista'     => ['text-bg-success', 'Lista'],
        'rechazada' => ['text-bg-danger', 'Rechazada'],
    ];
    [$clase, $texto] = $clases[$estado] ?? ['text-bg-light', $estado];

    return '<span class="badge ' . $clase . '">' . e($texto) . '</span>';
}
