<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renderiza una vista PHP dentro de un layout.
 * Las vistas viven en src/Views y reciben sus datos como variables.
 */
final class View
{
    private const BASE = __DIR__ . '/../Views/';

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $content = self::capture($view, $data);

        if ($layout === null) {
            echo $content;

            return;
        }

        echo self::capture($layout, $data + ['content' => $content]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $view, array $data): string
    {
        $file = self::BASE . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
