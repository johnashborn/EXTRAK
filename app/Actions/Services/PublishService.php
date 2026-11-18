<?php

namespace App\Actions\Services;

use App\Enums\ServiceState;
use App\Models\Service;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ServiceCodeAssigner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Makes a complete draft visible to everyone and gives it its code (year of its start date).
 */
class PublishService
{
    public function __construct(
        private readonly ActivityLogger $log,
        private readonly ServiceCodeAssigner $codes,
    ) {
    }

    public function handle(User $by, Service $s): Service
    {
        // One transaction: the code generator's campus lock nests inside it and lasts until the save commits.
        DB::transaction(function () use ($by, $s) {
            $service = Service::withoutGlobalScopes()->lockForUpdate()->findOrFail($s->getKey());

            if ($service->state !== ServiceState::Draft) {
                throw ValidationException::withMessages(['state' => 'This record is already published.']);
            }

            $this->assertComplete($service);

            $service->state = ServiceState::Published;
            $service->published_by = $by->id;
            $service->published_at = now();

            if ($service->code === null) {
                $this->codes->assign($service, $service->start_date?->year ?? (int) now()->year);
            } else {
                $service->save();
            }

            $this->log->log($by, 'published', $service, ['code' => $service->code]);
        });

        return $s->refresh();
    }

    /**
     * Checks the saved record against config('extrack.publish.required').
     *
     * @throws ValidationException
     */
    private function assertComplete(Service $service): void
    {
        $data = [
            'report_file' => $service->reportDocument()->exists() ? true : null,
            'start_date' => $service->start_date?->toDateString(),
            'end_date' => $service->end_date?->toDateString(),
        ];
        foreach (ServiceFields::COLUMNS as $column) {
            $data[$column] ??= $service->{$column};
        }
        foreach (ServiceFields::LISTS as $field => $relation) {
            $related = $service->{$relation}();
            $data[$field] = $related->pluck($related->getRelated()->getQualifiedKeyName())->all();
        }

        $rules = [];
        foreach ((array) config('extrack.publish.required') as $field) {
            $rules[$field] = array_key_exists($field, ServiceFields::LISTS) ? ['required', 'array', 'min:1'] : ['required'];
        }
        if ($data['start_date'] !== null && $data['end_date'] !== null) {
            $rules['end_date'][] = 'after_or_equal:start_date';
        }

        Validator::make($data, $rules, [
            'report_file.required' => 'Upload the accomplishment report before publishing.',
        ])->validate();
    }
}
