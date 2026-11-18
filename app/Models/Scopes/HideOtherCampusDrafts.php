<?php

namespace App\Models\Scopes;

use App\Enums\ServiceState;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * A draft is seen only by users allowed to view drafts, and only for their own campus.
 * With nobody signed in (console, seeders) nothing is filtered.
 */
class HideOtherCampusDrafts implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return;
        }

        $table = $model->getTable();
        $canSeeDrafts = $user->hasAbility('view_drafts') && $user->campus_id !== null;

        $builder->where(function (Builder $query) use ($table, $user, $canSeeDrafts) {
            $query->where("{$table}.state", '!=', ServiceState::Draft->value);

            if ($canSeeDrafts) {
                $query->orWhere("{$table}.campus_id", $user->campus_id);
            }
        });
    }
}
