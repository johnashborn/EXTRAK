<?php

namespace App\Http\Requests;

use App\Actions\Services\ServiceFields;
use App\Enums\CopyType;
use App\Enums\DocumentKind;
use App\Enums\ServiceState;
use App\Models\Service;
use App\Rules\AllowedFileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * The add/edit form. Which fields are required depends on the button pressed (intent = draft | publish)
 * and comes from config("extrack.{intent}.required"); file types and sizes come from config('extrack.files').
 */
class SaveServiceRequest extends FormRequest
{
    public const INTENTS = ['draft', 'publish'];

    public function authorize(): bool
    {
        $user = $this->user();
        $service = $this->service();

        if ($user === null) {
            return false;
        }

        if ($service !== null) {
            return $user->can('update', $service)
                && ($this->intent() !== 'publish' || $service->state === ServiceState::Published || $user->can('publish', $service));
        }

        return $user->can('create', Service::class)
            && ($this->intent() !== 'publish' || $user->can('publish', $this->newDraft()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $required = (array) config("extrack.{$this->intent()}.required", []);
        $need = fn (string $field) => in_array($field, $required, true) ? 'required' : 'nullable';
        $maxKb = 'max:'.((int) config('extrack.files.max_mb') * 1024);
        $service = $this->service();

        // The report is part of every record: a new record needs one, an existing one already has it.
        $reportNeeded = $service === null || ! $service->reportDocument()->exists();

        $rules = [
            'intent' => ['required', Rule::in(self::INTENTS)],
            'title' => [$need('title'), 'string', 'max:255'],
            'description' => [$need('description'), 'string', 'max:5000'],
            'category_id' => [$need('category_id'), 'integer', 'exists:categories,id'],
            'municipality_id' => [$need('municipality_id'), 'integer', 'exists:municipalities,id'],
            'barangay_ids' => [$need('barangay_ids'), 'array', ...$this->atLeastOne('barangay_ids', $required)],
            'barangay_ids.*' => [
                'integer', 'distinct',
                Rule::exists('barangays', 'id')->where('municipality_id', $this->integer('municipality_id') ?: null),
            ],
            'start_date' => [$need('start_date'), 'date'],
            'end_date' => [$need('end_date'), 'date', ...($this->filled('start_date') ? ['after_or_equal:start_date'] : [])],
            'beneficiaries' => [$need('beneficiaries'), 'integer', 'min:0', 'max:4000000000'],
            'sector_ids' => [$need('sector_ids'), 'array', ...$this->atLeastOne('sector_ids', $required)],
            'sector_ids.*' => ['integer', 'distinct', 'exists:beneficiary_sectors,id'],
            'partners' => [$need('partners'), 'string', 'max:255'],
            'amount_spent' => [$need('amount_spent'), 'numeric', 'min:0', 'max:9999999999.99'],
            'narrative' => [$need('narrative'), 'string', 'max:20000'],

            'report_file' => [
                $reportNeeded ? 'required' : 'nullable', 'file', $maxKb,
                new AllowedFileType((array) config('extrack.files.report_types')),
            ],
            'report_copy_type' => ['nullable', 'required_with:report_file', Rule::enum(CopyType::class)],

            'attachments' => ['nullable', 'array'],
            'attachments.*.file' => ['required', 'file', $maxKb, new AllowedFileType((array) config('extrack.files.types'))],
            'attachments.*.kind' => ['required', Rule::enum(DocumentKind::class)->except([DocumentKind::AccomplishmentReport])],
            'attachments.*.copy_type' => ['required', Rule::enum(CopyType::class)],
        ];

        if ($service === null) {
            $rules['remove_document_ids'] = ['prohibited'];
        } else {
            $rules['remove_document_ids'] = ['nullable', 'array'];
            $rules['remove_document_ids.*'] = ['integer', Rule::exists('documents', 'id')->where('service_id', $service->id)];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tooLarge = 'This file is larger than the '.config('extrack.files.max_mb').' MB limit';

        return [
            'report_file.required' => 'Choose the accomplishment report file.',
            'report_file.max' => $tooLarge,
            'report_file.uploaded' => $tooLarge,
            'attachments.*.file.max' => $tooLarge,
            'attachments.*.file.uploaded' => $tooLarge,
            'barangay_ids.*.exists' => 'Each barangay must be in the chosen municipality.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'municipality_id' => 'municipality',
            'barangay_ids' => 'barangays',
            'sector_ids' => 'sectors',
            'beneficiaries' => 'people reached',
            'report_file' => 'report file',
            'report_copy_type' => 'soft or scanned choice for the report',
            'attachments.*.file' => 'file',
            'attachments.*.kind' => 'kind of file',
            'attachments.*.copy_type' => 'soft or scanned choice',
        ];
    }

    protected function prepareForValidation(): void
    {
        // A form row left without a file is just an unused row (the page shows a few empty ones).
        $rows = (array) $this->input('attachments', []);
        $files = (array) $this->file('attachments', []);
        $kept = array_filter($rows, fn ($row, $index) => isset($files[$index]['file']), ARRAY_FILTER_USE_BOTH);

        $this->merge(['attachments' => $kept]);
    }

    /** The button pressed. Editing a published record always uses the publish rules. */
    public function intent(): string
    {
        if ($this->service()?->state === ServiceState::Published) {
            return 'publish';
        }

        $intent = $this->input('intent');

        return in_array($intent, self::INTENTS, true) ? $intent : 'draft';
    }

    /**
     * Every form field, as validated. A field left out is cleared (the form always sends them all).
     *
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        $validated = $this->validated();
        $fields = [];

        foreach (ServiceFields::COLUMNS as $column) {
            $fields[$column] = $validated[$column] ?? null;
        }
        foreach (array_keys(ServiceFields::LISTS) as $list) {
            $fields[$list] = ServiceFields::ids($validated[$list] ?? []);
        }

        return $fields;
    }

    public function reportFile(): ?UploadedFile
    {
        $file = $this->validated('report_file');

        return $file instanceof UploadedFile ? $file : null;
    }

    public function reportCopy(): CopyType
    {
        return CopyType::tryFrom((string) $this->validated('report_copy_type')) ?? CopyType::Soft;
    }

    /**
     * @return list<array{file: UploadedFile, kind: DocumentKind, copy_type: CopyType}>
     */
    public function attachments(): array
    {
        return array_values(array_map(fn (array $row) => [
            'file' => $row['file'],
            'kind' => DocumentKind::from($row['kind']),
            'copy_type' => CopyType::from($row['copy_type']),
        ], (array) $this->validated('attachments', [])));
    }

    /**
     * @return list<int>
     */
    public function removeDocumentIds(): array
    {
        return ServiceFields::ids($this->validated('remove_document_ids', []));
    }

    private function service(): ?Service
    {
        $service = $this->route('service');

        return $service instanceof Service ? $service : null;
    }

    /** A stand-in for the record about to be created, so the publish policy can be asked about it. */
    private function newDraft(): Service
    {
        return new Service(['campus_id' => $this->user()->campus_id, 'state' => ServiceState::Draft]);
    }

    /**
     * @param  list<string>  $required
     * @return list<string>
     */
    private function atLeastOne(string $field, array $required): array
    {
        return in_array($field, $required, true) ? ['min:1'] : [];
    }
}
