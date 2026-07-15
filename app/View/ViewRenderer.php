<?php

declare(strict_types=1);

namespace SpaBooking\View;

use RuntimeException;

final class ViewRenderer
{
    public function __construct(private readonly string $basePath)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = []): string
    {
        $content = $this->renderFile($this->resolve($view), $data);

        return $this->renderFile(
            $this->resolve('layouts/main'),
            array_merge($data, ['content' => $content])
        );
    }

    private function resolve(string $view): string
    {
        if (str_contains($view, '..')) {
            throw new RuntimeException('Invalid view path.');
        }

        $file = rtrim($this->basePath, '/\\') . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('View not found: %s', $view));
        }

        return $file;
    }

    /** @param array<string, mixed> $data */
    private function renderFile(string $file, array $data): string
    {
        ob_start();
        extract($data, EXTR_SKIP);
        require $file;
        $output = ob_get_clean();

        if ($output === false) {
            throw new RuntimeException('Unable to render view.');
        }

        return $output;
    }
}

