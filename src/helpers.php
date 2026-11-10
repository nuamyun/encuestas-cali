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
