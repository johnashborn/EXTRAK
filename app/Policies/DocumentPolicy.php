<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * A document is as visible as its service record.
 */
class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        // The draft scope hides the record from users who may not see it, so no service means deny.
        $service = $document->service;

        return $service !== null && Gate::forUser($user)->allows('view', $service);
    }
}
