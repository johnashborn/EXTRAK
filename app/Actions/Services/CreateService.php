<?php

namespace App\Actions\Services;

use App\Actions\Documents\AttachDocument;
use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Enums\ServiceState;
use App\Models\Service;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ReportReading\ReportReader;
use App\Services\ServiceCodeAssigner;
use Illuminate\Http\UploadedFile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Throwable;

/**
 * Saves a new record as a draft of the uploader's own campus, with its report and supporting files.
 */
class CreateService
{
    public function __construct(
        private readonly AttachDocument $attach,
        private readonly ActivityLogger $log,
        private readonly ReportReader $reader,
        private readonly ServiceCodeAssigner $codes,
    ) {
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  list<array{file: UploadedFile, kind: DocumentKind, copy_type: CopyType}>  $attachments
     */
    public function handle(User $by, array $fields, UploadedFile $report, CopyType $reportCopy, array $attachments = []): Service
    {
        if ($by->campus_id === null) {
            throw new LogicException('Only a user with a campus can add records.');
        }

        $stored = [];

        try {
            return DB::transaction(function () use ($by, $fields, $report, $reportCopy, $attachments, &$stored) {
                $service = new Service;
                foreach (ServiceFields::COLUMNS as $column) {
                    $service->{$column} = $fields[$column] ?? null;
                }
                $service->campus_id = $by->campus_id; // never from the request
                $service->state = ServiceState::Draft;
                $service->encoded_by = $by->id;
                $service->save();

                foreach (ServiceFields::LISTS as $field => $relation) {
                    $service->{$relation}()->sync(ServiceFields::ids($fields[$field] ?? []));
                }

                if (config('extrack.code.assign_on') === 'draft') {
                    $this->codes->assign($service, $this->year($service));
                }

                $this->log->log($by, 'created', $service, ['title' => $service->title]);

                // Files go in after the rows exist; if anything fails they are deleted below.
                $reportDocument = $this->attach->handle($by, $service, $report, DocumentKind::AccomplishmentReport, $reportCopy);
                $stored[] = $reportDocument->path;

                foreach ($attachments as $attachment) {
                    $stored[] = $this->attach->handle($by, $service, $attachment['file'], $attachment['kind'], $attachment['copy_type'])->path;
                }

                $service->reader_suggestions = $this->reader->read($reportDocument)->toArray();
                $service->save();

                return $service;
            });
        } catch (Throwable $e) {
            Storage::disk(AttachDocument::DISK)->delete($stored);

            throw $e;
        }
    }

    private function year(Service $service): int
    {
        return $service->start_date instanceof CarbonInterface ? $service->start_date->year : (int) now()->year;
    }
}
