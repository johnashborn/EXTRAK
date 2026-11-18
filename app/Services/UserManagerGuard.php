<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Keeps at least one active user able to manage users. Derived from the config abilities.
 */
class UserManagerGuard
{
    /**
     * Throws unless some other active user holds manage_users, when $target currently does and would stop.
     */
    public function assertAnotherManagerRemains(User $target, ?Role $newRole, bool $stillActive, string $field): void
    {
        $managerRoles = (array) config('extrack.permissions.manage_users', []);

        $isManagerNow = $target->is_active && in_array($target->role?->value, $managerRoles, true);
        $staysManager = $stillActive && $newRole !== null && in_array($newRole->value, $managerRoles, true);

        if (! $isManagerNow || $staysManager) {
            return;
        }

        $othersExist = User::query()
            ->where('is_active', true)
            ->whereIn('role', $managerRoles)
            ->whereKeyNot($target->getKey())
            ->exists();

        if (! $othersExist) {
            throw ValidationException::withMessages([
                $field => 'At least one active user must be able to manage users.',
            ]);
        }
    }
}
