<?php

namespace App\Core;

use PDO;
use PDOException;
use App\Core\Exceptions\DatabaseException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::createConnection();
        }

        return self::$connection;
    }

    private static function createConnection(): void
    {
        $config = require __DIR__ . '/../../config/database.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";

        try {
            self::$connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Database connection failed: " . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    public static function testConnection(): bool
    {
        try {
            self::getConnection()->query('SELECT 1');
            return true;
        } catch (DatabaseException $e) {
            return false;
        }
    }

    public static function disconnect(): void
    {
        self::$connection = null;
    }
}
