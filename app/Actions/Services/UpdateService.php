<?php

namespace App\Actions\Services;

use App\Actions\Documents\AttachDocument;
use App\Actions\Documents\RemoveDocument;
use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Models\Service;
use App\Models\User;
use App\Services\ActivityLogger;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Saves changes to a record, adds and removes files, and logs only the fields that changed.
 * The campus, state and code are never changed here. A new report replaces the old one.
 */
class UpdateService
{
    public function __construct(
        private readonly AttachDocument $attach,
        private readonly RemoveDocument $remove,
        private readonly ActivityLogger $log,
    ) {
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  list<array{file: UploadedFile, kind: DocumentKind, copy_type: CopyType}>  $attachments
     * @param  list<int>  $removeDocumentIds
     */
    public function handle(User $by, Service $s, array $fields, array $attachments = [], array $removeDocumentIds = []): Service
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($by, $s, $fields, $attachments, $removeDocumentIds, &$stored) {
                $this->saveFields($by, $s, $fields);

                $newReportIds = [];
                foreach ($attachments as $attachment) {
                    $document = $this->attach->handle($by, $s, $attachment['file'], $attachment['kind'], $attachment['copy_type']);
                    $stored[] = $document->path;

                    if ($attachment['kind'] === DocumentKind::AccomplishmentReport) {
                        $newReportIds[] = $document->id;
                    }
                }

                // A new report replaces the old one; the record is never left without a report.
                if ($newReportIds !== []) {
                    $s->documents()
                        ->where('kind', DocumentKind::AccomplishmentReport->value)
                        ->whereKeyNot($newReportIds)
                        ->get()
                        ->each(fn ($old) => $this->remove->handle($by, $old));
                }

                foreach ($removeDocumentIds as $id) {
                    $document = $s->documents()->whereKey($id)->first();
                    if ($document !== null) {
                        $this->remove->handle($by, $document);
                    }
                }

                return $s->refresh();
            });
        } catch (Throwable $e) {
            Storage::disk(AttachDocument::DISK)->delete($stored);

            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function saveFields(User $by, Service $s, array $fields): void
    {
        foreach (ServiceFields::COLUMNS as $column) {
            if (array_key_exists($column, $fields)) {
                $s->{$column} = $fields[$column];
            }
        }

        $changes = [];
        foreach (array_keys($s->getDirty()) as $column) {
            $changes[$column] = ['from' => $this->plain($s->getOriginal($column)), 'to' => $this->plain($s->{$column})];
        }
        $s->save();

        foreach (ServiceFields::LISTS as $field => $relation) {
            if (! array_key_exists($field, $fields)) {
                continue;
            }

            $related = $s->{$relation}();
            $before = ServiceFields::ids($related->pluck($related->getRelated()->getQualifiedKeyName())->all());
            $after = ServiceFields::ids($fields[$field]);

            if ($before !== $after) {
                $related->sync($after);
                $changes[$field] = ['from' => $before, 'to' => $after];
            }
        }

        if ($changes !== []) {
            $this->log->log($by, 'updated', $s, $changes);
        }
    }

    private function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof DateTimeInterface => $value->format('Y-m-d'),
            $value instanceof BackedEnum => $value->value,
            default => $value,
        };
    }
}
