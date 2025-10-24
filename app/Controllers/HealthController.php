<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Database;

class HealthController
{
    public function check(): string
    {
        return Response::success([
            'server_check' => 'ok',
            'services' => [
                'database' => Database::testConnection() ? 'connected' : 'disconnected'
            ]
        ]);
    }
}
