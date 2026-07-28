<?php

declare(strict_types=1);

namespace SpaBooking\Http;

use Closure;
use RuntimeException;

final class Router
{
    /** @var array<string, array<string, Closure(string ...$parameters): Response>> */
    private array $routes = [];

    /** @var array<string, list<array{pattern: string, handler: Closure(string ...$parameters): Response}>> */
    private array $parameterizedRoutes = [];

    /** @var array<string, array<string, true>> */
    private array $registeredRoutes = [];

    /** @var Closure(): Response */
    private Closure $notFoundHandler;

    /** @param (callable(): Response)|null $notFoundHandler */
    public function __construct(?callable $notFoundHandler = null)
    {
        $this->notFoundHandler = $notFoundHandler === null
            ? static fn (): Response => new Response('<h1>Page not found</h1>', 404)
            : Closure::fromCallable($notFoundHandler);
    }

    /** @param callable(): Response $handler */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param callable(): Response $handler */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param callable(): Response $handler */
    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        if (isset($this->registeredRoutes[$method][$path])) {
            throw new RuntimeException(sprintf('Duplicate route: %s %s', $method, $path));
        }

        $this->registeredRoutes[$method][$path] = true;
        $routeHandler = Closure::fromCallable($handler);

        if (preg_match('/\{[A-Za-z_][A-Za-z0-9_]*\}/', $path) === 1) {
            $pattern = preg_replace('/\\\{[A-Za-z_][A-Za-z0-9_]*\\\}/', '([^/]+)', preg_quote($path, '#'));
            assert(is_string($pattern));
            $this->parameterizedRoutes[$method][] = [
                'pattern' => '#^' . $pattern . '$#',
                'handler' => $routeHandler,
            ];

            return;
        }

        $this->routes[$method][$path] = $routeHandler;
    }

    public function dispatch(string $method, string $uri): Response
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $this->normalizePath($path) : '/';
        $method = strtoupper($method);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler !== null) {
            return $handler();
        }

        foreach ($this->parameterizedRoutes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);

            return $route['handler'](...$matches);
        }

        return ($this->notFoundHandler)();
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        $normalized = '/' . trim($path, '/');

        return $normalized === '/' ? '/' : rtrim($normalized, '/');
    }
}
