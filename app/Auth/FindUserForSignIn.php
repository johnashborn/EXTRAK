<?php

namespace App\Auth;

use App\Models\User;

/**
 * Looks up an existing active user for a sign-in. Invite-only: it never creates accounts.
 */
class FindUserForSignIn
{
    public function handle(string $email, string $provider, ?string $externalId = null): ?User
    {
        $user = User::query()
            ->where('email', $email)
            ->where('is_active', true)
            ->first();

        if ($user === null) {
            return null;
        }

        if ($externalId === null) {
            return $user;
        }

        // A user already linked to a different external identity must not be taken over by email alone.
        if ($user->external_id !== null && $user->external_id !== $externalId) {
            return null;
        }

        if ($user->external_id === null) {
            $user->forceFill(['external_id' => $externalId, 'auth_provider' => $provider])->save();
        }

        return $user;
    }
}
