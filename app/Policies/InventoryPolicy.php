<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;

class InventoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function view(User $user, Inventory $inventory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function update(User $user, Inventory $inventory): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function delete(User $user, Inventory $inventory): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    private function isAdmin(User $user): bool
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));

        return $roleName === 'admin' || $user->role_id === 1;
    }

    private function isManager(User $user): bool
    {
        $roleName = strtolower((string) ($user->role?->name ?? ''));

        return $roleName === 'manager' || $user->role_id === 2;
    }
}

