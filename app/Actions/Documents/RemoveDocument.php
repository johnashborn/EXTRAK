<?php

namespace App\Actions\Documents;

use App\Enums\DocumentKind;
use App\Models\Document;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Deletes a document row and, once the change is committed, its file. A record always keeps a report.
 */
class RemoveDocument
{
    public function __construct(private readonly ActivityLogger $log)
    {
    }

    public function handle(User $by, Document $d): void
    {
        $service = $d->service()->withoutGlobalScopes()->firstOrFail();

        if ($d->kind === DocumentKind::AccomplishmentReport) {
            $otherReports = $service->documents()
                ->where('kind', DocumentKind::AccomplishmentReport->value)
                ->whereKeyNot($d->id)
                ->exists();

            if (! $otherReports) {
                throw ValidationException::withMessages([
                    'remove_document_ids' => 'The record must keep its accomplishment report. Upload a new one to replace it.',
                ]);
            }
        }

        DB::transaction(function () use ($by, $d, $service) {
            $path = $d->path;
            $d->delete();

            $this->log->log($by, 'document_removed', $service, [
                'document_id' => $d->id,
                'kind' => $d->kind->value,
                'original_name' => $d->original_name,
            ]);

            // Keep the file if anything later in the transaction fails.
            DB::afterCommit(fn () => Storage::disk(AttachDocument::DISK)->delete($path));
        });
    }
}
