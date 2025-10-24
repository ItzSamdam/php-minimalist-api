<?php

namespace App\Core;

use App\Core\Exceptions\DatabaseException;

class Migrator
{
    private \PDO $db;

    public function __construct()
    {
        try {
            $this->db = Database::getConnection();
            $this->createMigrationsTable();
        } catch (DatabaseException $e) {
            // Log the error but don't break the application
            error_log('Migration system: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createMigrationsTable(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function runMigrations(): void
    {
        try {
            $executed = $this->getExecutedMigrations();
            $files = glob(__DIR__ . '/../../migrations/*.php');

            foreach ($files as $file) {
                $migrationName = basename($file, '.php');

                if (!in_array($migrationName, $executed)) {
                    $migration = require $file;
                    $this->db->exec($migration['up']);

                    $stmt = $this->db->prepare("INSERT INTO migrations (migration) VALUES (?)");
                    $stmt->execute([$migrationName]);

                    error_log("Migrated: {$migrationName}");
                }
            }
        } catch (\Exception $e) {
            throw new DatabaseException('Migration failed: ' . $e->getMessage());
        }
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT migration FROM migrations");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function isDatabaseConnected(): bool
    {
        return Database::testConnection();
    }
}
