<?php

namespace App\Policies;

use App\Enums\ServiceState;
use App\Models\Service;
use App\Models\User;

/**
 * The section 5 table. Abilities come from config('extrack.permissions'); the own-campus limits live here.
 */
class ServicePolicy
{
    public function view(User $user, Service $service): bool
    {
        if ($service->state === ServiceState::Published) {
            return $user->hasAbility('view_published');
        }

        return $user->hasAbility('view_drafts') && $this->ownCampus($user, $service);
    }

    /** A new record always belongs to the uploader's campus, so the user needs one. */
    public function create(User $user): bool
    {
        return $user->hasAbility('create') && $user->campus_id !== null;
    }

    public function update(User $user, Service $service): bool
    {
        $ability = $service->state === ServiceState::Published ? 'edit_published' : 'edit_draft';

        return $user->hasAbility($ability) && $this->ownCampus($user, $service);
    }

    public function publish(User $user, Service $service): bool
    {
        return $user->hasAbility('publish')
            && $this->ownCampus($user, $service)
            && $service->state === ServiceState::Draft;
    }

    public function discard(User $user, Service $service): bool
    {
        return $user->hasAbility('discard_draft')
            && $this->ownCampus($user, $service)
            && $service->state === ServiceState::Draft;
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasAbility('delete_published') && $service->state === ServiceState::Published;
    }

    private function ownCampus(User $user, Service $service): bool
    {
        return $user->campus_id !== null && (int) $user->campus_id === (int) $service->campus_id;
    }
}
