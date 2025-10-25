<?php

namespace App\Core;

use PDO;

abstract class Repository
{
    protected PDO $db;
    protected string $table;
    protected bool $softDelete = false;
    protected string $softDeleteColumn = 'is_deleted';
    protected string $deletedAtColumn = 'deleted_at';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function find(int $id): ?array
    {
        $where = $this->softDelete ? "AND {$this->softDeleteColumn} = 0" : "";
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? {$where}");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findAll(): array
    {
        $where = $this->softDelete ? "WHERE {$this->softDeleteColumn} = 0" : "";
        $stmt = $this->db->query("SELECT * FROM {$this->table} {$where}");
        return $stmt->fetchAll();
    }

    public function findWithRelations(int $id, array $relations): ?array
    {
        $record = $this->find($id);
        if (!$record) {
            return null;
        }

        foreach ($relations as $relation) {
            $relatedRepoClass = "App\\Repositories\\" . ucfirst($relation) . "Repository";
            if (class_exists($relatedRepoClass)) {
                $relatedRepo = new $relatedRepoClass();
                $foreignKey = "{$relation}_id";
                if (isset($record[$foreignKey])) {
                    $record[$relation] = $relatedRepo->find($record[$foreignKey]);
                }
            }
        }

        return $record;
    }

    

    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($key) => "{$key} = :{$key}", array_keys($data)));
        $where = $this->softDelete ? "AND {$this->softDeleteColumn} = 0" : "";

        $sql = "UPDATE {$this->table} SET {$set} WHERE id = :id {$where}";
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;

        return $stmt->execute($data);
    }

    public function delete(int $id): bool
    {
        if ($this->softDelete) {
            return $this->softDelete($id);
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function softDelete(int $id): bool
    {
        if (!$this->softDelete) {
            throw new \RuntimeException("Soft delete is not enabled for this repository");
        }

        $sql = "UPDATE {$this->table} SET 
                {$this->softDeleteColumn} = 1, 
                {$this->deletedAtColumn} = NOW() 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function restore(int $id): bool
    {
        if (!$this->softDelete) {
            throw new \RuntimeException("Soft delete is not enabled for this repository");
        }

        $sql = "UPDATE {$this->table} SET 
                {$this->softDeleteColumn} = 0, 
                {$this->deletedAtColumn} = NULL 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function findWithTrashed(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findAllWithTrashed(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function findOnlyTrashed(): array
    {
        if (!$this->softDelete) {
            throw new \RuntimeException("Soft delete is not enabled for this repository");
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE {$this->softDeleteColumn} = 1");
        return $stmt->fetchAll();
    }

    public function forceDelete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
