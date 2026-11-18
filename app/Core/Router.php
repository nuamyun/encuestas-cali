<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador mínimo: asocia método HTTP + ruta con un controlador y
 * registra qué perfiles pueden acceder (se aplica en el paso de login).
 *
 * Rutas con parámetros: '/usuarios/{id}' captura 'id'.
 */
final class Router
{
    /** @var list<array{method: string, path: string, pattern: string, handler: array{0: class-string, 1: string}, perfiles: list<string>}> */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $perfiles Perfiles autorizados; vacío = ruta pública
     */
    public function get(string $path, array $handler, array $perfiles = []): void
    {
        $this->add('GET', $path, $handler, $perfiles);
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $perfiles
     */
    public function post(string $path, array $handler, array $perfiles = []): void
    {
        $this->add('POST', $path, $handler, $perfiles);
    }

    /**
     * Busca la ruta que corresponde a la petición.
     *
     * @return array{handler: array{0: class-string, 1: string}, perfiles: list<string>, params: array<string, string>}|null
     */
    public function match(string $method, string $uri): ?array
    {
        $method = strtoupper($method) === 'HEAD' ? 'GET' : strtoupper($method);
        $path   = self::normalize((string) parse_url($uri, PHP_URL_PATH));

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $path, $matches) === 1) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return ['handler' => $route['handler'], 'perfiles' => $route['perfiles'], 'params' => $params];
            }
        }

        return null;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param list<string> $perfiles
     */
    private function add(string $method, string $path, array $handler, array $perfiles): void
    {
        $path    = self::normalize($path);
        $pattern = '#^' . preg_replace('#\{([a-zA-Z_]+)\}#', '(?P<$1>[^/]+)', $path) . '$#';

        $this->routes[] = [
            'method'   => $method,
            'path'     => $path,
            'pattern'  => $pattern,
            'handler'  => $handler,
            'perfiles' => $perfiles,
        ];
    }

    private static function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '' ? '/' : $path;
    }
}
