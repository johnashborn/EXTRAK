<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserManagerGuard;
use Illuminate\Auth\Access\AuthorizationException;

class SetUserActive
{
    public function __construct(
        private readonly ActivityLogger $log,
        private readonly UserManagerGuard $managers,
    ) {
    }

    public function handle(User $by, User $target, bool $active): User
    {
        if ($by->is($target)) {
            throw new AuthorizationException('You cannot change your own active state.');
        }

        if ($target->is_active === $active) {
            return $target;
        }

        if (! $active) {
            $this->managers->assertAnotherManagerRemains($target, $target->role, false, 'is_active');
        }

        $target->is_active = $active;
        $target->save();

        $this->log->log($by, $active ? 'user_reactivated' : 'user_deactivated', $target);

        return $target;
    }
}
