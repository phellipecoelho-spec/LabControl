<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function before(?User $user): ?bool
    {
        if ($user && $user->roles->contains('slug', 'admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Role $role): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('usuarios.create');
    }

    public function update(User $user, Role $role): bool
    {
        if ($role->slug === 'admin') {
            return false;
        }

        return $user->hasPermission('usuarios.edit');
    }

    public function delete(User $user, Role $role): bool
    {
        if ($role->slug === 'admin') {
            return false;
        }

        if ($role->users()->exists()) {
            return false;
        }

        return $user->hasPermission('usuarios.delete');
    }
}
