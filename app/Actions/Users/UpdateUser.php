<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserManagerGuard;
use Illuminate\Validation\ValidationException;

class UpdateUser
{
    public function __construct(
        private readonly ActivityLogger $log,
        private readonly UserManagerGuard $managers,
    ) {
    }

    /**
     * @param  array{name: string, role: string|Role, campus_id?: int|string|null}  $data
     */
    public function handle(User $by, User $target, array $data): User
    {
        $role = $data['role'] instanceof Role ? $data['role'] : Role::from($data['role']);
        $campusId = $role === Role::President || empty($data['campus_id']) ? null : (int) $data['campus_id'];

        $accessChanges = $target->role !== $role || $target->campus_id !== $campusId;

        if ($accessChanges) {
            $this->managers->assertAnotherManagerRemains($target, $role, true, 'role');

            if ($by->is($target)) {
                throw ValidationException::withMessages([
                    'role' => "You can't change your own role or campus.",
                ]);
            }
        }

        $target->name = $data['name'];
        $target->role = $role;
        $target->campus_id = $campusId;

        $changes = [];
        foreach (array_keys($target->getDirty()) as $field) {
            $old = $target->getOriginal($field);
            $new = $target->getAttribute($field);
            $changes[$field] = [
                'from' => $old instanceof Role ? $old->value : $old,
                'to' => $new instanceof Role ? $new->value : $new,
            ];
        }

        $target->save();

        if ($changes !== []) {
            $this->log->log($by, 'user_updated', $target, $changes);
        }

        return $target;
    }
}
