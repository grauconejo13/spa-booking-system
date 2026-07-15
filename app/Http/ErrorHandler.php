<?php

declare(strict_types=1);

namespace SpaBooking\Http;

use ErrorException;
use SpaBooking\View\ViewRenderer;
use Throwable;

final class ErrorHandler
{
    public function __construct(
        private readonly ViewRenderer $views,
        private readonly bool $debug,
        private readonly ?string $logPath = null
    ) {
    }

    public function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        if ($this->logPath !== null) {
            ini_set('log_errors', '1');
            ini_set('error_log', $this->logPath);
        }

        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): bool {
                if ((error_reporting() & $severity) === 0) {
                    return false;
                }

                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );
        set_exception_handler([$this, 'handle']);
    }

    public function handle(Throwable $exception): void
    {
        error_log(sprintf(
            '[%s] %s in %s:%d',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));

        $response = new Response(
            $this->views->render('errors/500', [
                'title' => 'Something went wrong',
                'detail' => $this->debug ? $exception->getMessage() : null,
            ]),
            500
        );
        $response->send();
    }
}
