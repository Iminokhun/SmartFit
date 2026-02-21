<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {

    }

    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->isAdmin($user) || $this->isManager($user);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->isAdmin($user); // manager = false
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user); // manager = false
    }

    private function isAdmin(User $user): bool
    {
        return strtolower((string) $user->role?->name) === 'admin' || $user->role_id === 1;
    }

    private function isManager(User $user): bool
    {
        return strtolower((string) $user->role?->name) === 'manager' || $user->role_id === 2;
    }
}
