<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
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
        return $user->hasPermission('usuarios.view');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermission('usuarios.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('usuarios.create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermission('usuarios.edit');
    }

    public function delete(User $user, User $target): bool
    {
        if ($target->roles->contains('slug', 'admin')) {
            return false;
        }

        return $user->hasPermission('usuarios.delete');
    }

    public function restore(User $user, User $target): bool
    {
        return $user->hasPermission('usuarios.delete');
    }

    public function forceDelete(User $user, User $target): bool
    {
        return false;
    }
}
