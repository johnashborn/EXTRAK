<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function log(?User $by, string $action, Model $subject, array $changes = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $by?->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'changes' => $changes === [] ? null : $changes,
        ]);
    }
}
