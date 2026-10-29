<?php

namespace App\Actions\Prototype;

use App\Models\PrototypeState;
use App\Models\User;

/**
 * Saves the dashboards' shared data and bumps its version, so other open
 * dashboards can tell that something changed. Last save wins.
 */
class SavePrototypeState
{
    public function __invoke(array $data, ?User $user): PrototypeState
    {
        $state = PrototypeState::firstOrNew(['key' => PrototypeState::DEFAULT_KEY]);
        $state->data = $data;
        $state->version = $state->version + 1;
        $state->updated_by = $user?->id;
        $state->save();

        return $state;
    }
}
