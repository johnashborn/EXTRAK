<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAbility('manage_users');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasAbility('manage_users');
    }

    public function create(User $user): bool
    {
        return $user->hasAbility('manage_users');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasAbility('manage_users');
    }

    /** Deactivating or reactivating; nobody may change their own state. */
    public function setActive(User $user, User $target): bool
    {
        return $user->hasAbility('manage_users') && ! $user->is($target);
    }
}
