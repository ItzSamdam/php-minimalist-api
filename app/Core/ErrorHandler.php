<?php

namespace App\Core;

use Throwable;
use PDOException;

class ErrorHandler
{
    public static function register(): void
    {
        // Set error reporting level
        error_reporting(E_ALL);

        // Set custom error handler
        set_error_handler([self::class, 'handleError']);

        // Set custom exception handler
        set_exception_handler([self::class, 'handleException']);

        // Set shutdown function to catch fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        // Convert error to exception
        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleException(Throwable $exception): void
    {
        self::logException($exception);

        $response = self::formatException($exception);

        // Ensure we send JSON response
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($response['status']);
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::handleException(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }

    private static function formatException(Throwable $exception): array
    {
        $statusCode = self::getStatusCode($exception);
        $response = [
            'success' => false,
            'error' => [
                'message' => self::getUserMessage($exception),
                'code' => $statusCode,
            ],
            'timestamp' => date('c')
        ];

        // Add debug information in development
        if ($_ENV['APP_ENV'] === 'development') {
            $response['error']['debug'] = [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => self::formatTrace($exception->getTrace())
            ];
        }

        return $response;
    }

    private static function getStatusCode(Throwable $exception): int
    {
        // Map exceptions to HTTP status codes
        if ($exception instanceof \App\Core\Exceptions\ValidationException) {
            return 422;
        }

        if ($exception instanceof PDOException) {
            return 503; // Service Unavailable for database errors
        }

        if ($exception instanceof \InvalidArgumentException) {
            return 400;
        }

        if ($exception instanceof \RuntimeException) {
            return 500;
        }

        // Default to 500 for unhandled exceptions
        return 500;
    }

    private static function getUserMessage(Throwable $exception): string
    {
        // User-friendly messages based on exception type
        if ($exception instanceof PDOException) {
            return 'Database service temporarily unavailable';
        }

        if ($exception instanceof \ErrorException) {
            return 'Internal server error';
        }

        // Use the exception message for known exception types
        if ($exception instanceof \App\Core\Exceptions\ValidationException) {
            return $exception->getMessage();
        }

        // Generic message for production, detailed for development
        if ($_ENV['APP_ENV'] === 'production') {
            return 'An unexpected error occurred';
        }

        return $exception->getMessage();
    }

    private static function formatTrace(array $trace): array
    {
        $formatted = [];
        foreach ($trace as $index => $frame) {
            $formatted[] = [
                'file' => $frame['file'] ?? '[internal]',
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? '',
                'class' => $frame['class'] ?? '',
                'type' => $frame['type'] ?? ''
            ];
        }
        return $formatted;
    }

    private static function logException(Throwable $exception): void
    {
        $logFile = __DIR__ . '/../../logs/error.log';

        // Create logs directory if it doesn't exist
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $message = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        error_log($message, 3, $logFile);
    }
}
