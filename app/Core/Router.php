<?php

namespace App\Core;

use App\Core\Exceptions\ValidationException;

class Router
{
    private array $routes = [];

    public function get(string $path, string $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, string $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, string $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function delete(string $path, string $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
    }

    public function dispatch(): void
    {
        $method = Request::getMethod();
        $path = Request::getPath();

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = $this->buildPattern($route);

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $this->callHandler($handler, $matches);
                return;
            }
        }

        echo Response::notFound();
    }

    private function buildPattern(string $route): string
    {
        $pattern = preg_replace('/\(\\\d\+\)/', '(\d+)', $route);
        return '#^' . $pattern . '$#';
    }

    private function callHandler(string $handler, array $params): void
    {
        [$controller, $method] = explode('@', $handler);
        $controller = "App\\Controllers\\{$controller}";

        if (!class_exists($controller)) {
            echo Response::error("Controller {$controller} not found", 500);
            return;
        }

        if (!method_exists($controller, $method)) {
            echo Response::error("Method {$method} not found in controller", 500);
            return;
        }

        try {
            $instance = new $controller();
            $result = call_user_func_array([$instance, $method], $params);

            if ($result !== null) {
                echo $result;
            }
        } catch (ValidationException $e) {
            echo Response::error($e->getMessage(), $e->getCode(), $e->getErrors());
        } catch (\Throwable $e) {
            // Let the global error handler deal with it
            throw $e;
        }
    }
}
