<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Kleiner Router: Muster wie "/{locale}/hochzeitsfotograf/{stadt}" werden auf
 * eine Funktion abgebildet. Kein Framework – bei rund fünfzig Routen wäre das
 * mehr Ballast als Hilfe.
 */
final class Router
{
    /** @var list<array{method:string,regex:string,keys:list<string>,handler:callable}> */
    private array $routes = [];

    /** @var callable|null */
    private $fallback = null;

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** Gleiches Muster für GET und POST – Formularseiten im Adminbereich. */
    public function any(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
        $this->add('POST', $pattern, $handler);
    }

    public function notFound(callable $handler): void
    {
        $this->fallback = $handler;
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $keys = [];
        $regex = preg_replace_callback(
            '/\{([a-z_]+)\}/i',
            static function (array $m) use (&$keys): string {
                $keys[] = $m[1];
                return '([^/]+)';
            },
            $pattern
        );

        $this->routes[] = [
            'method'  => $method,
            'regex'   => '#^' . $regex . '$#',
            'keys'    => $keys,
            'handler' => $handler,
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        $path = '/' . trim(parse_url($path, PHP_URL_PATH) ?: '/', '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            $params = [];
            foreach ($route['keys'] as $i => $key) {
                $params[$key] = urldecode($matches[$i] ?? '');
            }

            ($route['handler'])($params);
            return;
        }

        http_response_code(404);
        if ($this->fallback !== null) {
            ($this->fallback)([]);
        }
    }
}
