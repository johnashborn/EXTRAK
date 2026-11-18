<?php

namespace App\Actions\Services;

use App\Actions\Documents\AttachDocument;
use App\Enums\ServiceState;
use App\Models\Service;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Removes a draft for good: its rows and, once that is committed, its files.
 */
class DiscardDraft
{
    public function __construct(private readonly ActivityLogger $log)
    {
    }

    public function handle(User $by, Service $s): void
    {
        if ($s->state !== ServiceState::Draft) {
            throw ValidationException::withMessages(['state' => 'Only drafts can be discarded.']);
        }

        DB::transaction(function () use ($by, $s) {
            $paths = $s->documents()->pluck('path')->all();
            $directory = "documents/{$s->id}";

            $this->log->log($by, 'discarded', $s, ['title' => $s->title]);

            $s->documents()->delete();
            foreach (ServiceFields::LISTS as $relation) {
                $s->{$relation}()->detach();
            }
            $s->forceDelete();

            DB::afterCommit(function () use ($paths, $directory) {
                $disk = Storage::disk(AttachDocument::DISK);
                $disk->delete($paths);
                $disk->deleteDirectory($directory);
            });
        });
    }
}
