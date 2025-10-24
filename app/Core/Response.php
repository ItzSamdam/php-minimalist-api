<?php

namespace App\Core;

class Response
{
    public static function json(array $data, int $statusCode = 200): string
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        return json_encode([
            'success' => $statusCode < 400,
            'timestamp' => time(),
            'data' => $data
        ]);
    }

    public static function success(array $data = [], string $message = '', int $statusCode = 200): string
    {
        $response = $data;
        if ($message) {
            $response['message'] = $message;
        }

        return self::json($response, $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): string
    {
        $response = ['message' => $message];
        if ($errors) {
            $response['errors'] = $errors;
        }

        return self::json($response, $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): string
    {
        return self::error($message, 404);
    }

    public static function created(array $data = [], string $message = 'Resource created successfully'): string
    {
        return self::success($data, $message, 201);
    }

    public static function noContent(): string
    {
        http_response_code(204);
        return '';
    }
}
