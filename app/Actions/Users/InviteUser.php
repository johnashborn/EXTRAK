<?php

namespace App\Actions\Users;

use App\Enums\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\URL;

class InviteUser
{
    public function __construct(private readonly ActivityLogger $log)
    {
    }

    /**
     * Creates the user with no password and returns the signed invite link. Nothing is sent.
     *
     * @param  array{name: string, email: string, role: string|Role, campus_id?: int|null}  $data
     * @return array{user: User, url: string}
     */
    public function handle(User $by, array $data): array
    {
        $role = $data['role'] instanceof Role ? $data['role'] : Role::from($data['role']);

        $user = new User;
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $role;
        $user->campus_id = $role === Role::President ? null : ($data['campus_id'] ?? null);
        $user->is_active = true;
        $user->auth_provider = config('extrack.auth_provider');
        $user->password = null;
        $user->save();

        $this->log->log($by, 'user_invited', $user, [
            'email' => $user->email,
            'role' => $role->value,
            'campus_id' => $user->campus_id,
        ]);

        $url = URL::temporarySignedRoute(
            'invites.show',
            now()->addDays((int) config('extrack.invite_days')),
            ['user' => $user->id],
        );

        return ['user' => $user, 'url' => $url];
    }
}
