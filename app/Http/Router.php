<?php

declare(strict_types=1);

namespace SpaBooking\Http;

use Closure;
use RuntimeException;

final class Router
{
    /** @var array<string, array<string, Closure(): Response>> */
    private array $routes = [];

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
    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        if (isset($this->routes[$method][$path])) {
            throw new RuntimeException(sprintf('Duplicate route: %s %s', $method, $path));
        }

        $this->routes[$method][$path] = Closure::fromCallable($handler);
    }

    public function dispatch(string $method, string $uri): Response
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $this->normalizePath($path) : '/';
        $handler = $this->routes[strtoupper($method)][$path] ?? $this->notFoundHandler;

        return $handler();
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
