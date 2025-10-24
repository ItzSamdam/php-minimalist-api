<?php

namespace App\Core;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use App\Core\Exceptions\ValidationException;

class Request
{
    public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function getPath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        return rtrim($path, '/');
    }

    public static function getBody(): array
    {
        $input = file_get_contents('php://input');

        // Try JSON first
        $data = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data ?? [];
        }

        // Fallback to form data
        if (self::getMethod() === 'POST') {
            return $_POST;
        }

        return [];
    }

    public static function getQueryParams(): array
    {
        return $_GET;
    }

    public static function getHeader(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }

    public static function getBearerToken(): ?string
    {
        $header = self::getHeader('Authorization');
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function validate(array $rules): array
    {
        $data = self::getBody();
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            try {
                $rule->assert($value);
            } catch (NestedValidationException $e) {
                $errors[$field] = array_values($e->getMessages());
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return $data;
    }

    public static function validateWithCustomRules(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            try {
                $rule->assert($value);
            } catch (NestedValidationException $e) {
                $errors[$field] = array_values($e->getMessages());
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return $data;
    }

    public static function getFile(string $name): ?array
    {
        return $_FILES[$name] ?? null;
    }

    public static function hasFile(string $name): bool
    {
        return isset($_FILES[$name]) && $_FILES[$name]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public static function getClientIp(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] ??
            $_SERVER['HTTP_X_FORWARDED_FOR'] ??
            $_SERVER['REMOTE_ADDR'] ??
            'unknown';
    }

    public static function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}
