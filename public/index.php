<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Migrator;
use App\Core\ErrorHandler;

// Load environment variables
Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();

// Register global error handler
ErrorHandler::register();

try {
    // Only run migrations if database is connected and in development
    if ($_ENV['APP_ENV'] === 'development') {
        try {
            $migrator = new Migrator();
            if ($migrator->isDatabaseConnected()) {
                $migrator->runMigrations();
            }
        } catch (\App\Core\Exceptions\DatabaseException $e) {
            // Log but don't stop the application if migrations fail
            error_log('Migrations skipped: ' . $e->getMessage());
        }
    }

    // Router setup
    $router = new Router();

    // Health check endpoint
    $router->get('/health', 'HealthController@check');

    // User routes
    $router->get('/users', 'UserController@index');
    $router->get('/users/(\d+)', 'UserController@show');
    $router->post('/users', 'UserController@store');
    $router->put('/users/(\d+)', 'UserController@update');
    $router->delete('/users/(\d+)', 'UserController@destroy');
    $router->post('/users/(\d+)/restore', 'UserController@restore');

    $router->dispatch();
} catch (Throwable $e) {
    // This catch is a final safety net
    ErrorHandler::handleException($e);
}
