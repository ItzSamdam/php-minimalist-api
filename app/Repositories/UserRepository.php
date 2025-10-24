<?php

namespace App\Repositories;

use App\Core\Repository;
use App\Models\User;

class UserRepository extends Repository
{
    protected string $table = 'users';
    protected bool $softDelete = true;
    protected string $softDeleteColumn = 'is_deleted';
    protected string $deletedAtColumn = 'deleted_at';

    public function findByEmail(string $email): ?User
    {
        $where = $this->softDelete ? "AND {$this->softDeleteColumn} = 0" : "";
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? {$where}");
        $stmt->execute([$email]);
        $data = $stmt->fetch();

        return $data ? new User($data) : null;
    }

    public function createUser(User $user): int
    {
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function updateUser(int $id, User $user): bool
    {
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->update($id, $data);
    }
}
