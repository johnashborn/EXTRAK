@php($report = $service->exists ? $service->documents->firstWhere('kind', \App\Enums\DocumentKind::AccomplishmentReport) : null)
@php($reportTypes = config('extrack.files.report_types'))

<fieldset>
    <legend>Step 1: The accomplishment report</legend>

    @if ($report)
        <p>Current report: {{ $report->original_name }} ({{ $report->copy_type === \App\Enums\CopyType::Scanned ? 'scanned copy' : 'soft copy' }}).
            Choose a new file only to replace it.</p>
    @endif

    <p>
        <label for="report_file">Report file ({{ strtoupper(implode(', ', $reportTypes)) }}, up to {{ $maxMb }} MB)</label><br>
        <input id="report_file" name="report_file" type="file"
               accept="{{ collect($reportTypes)->map(fn ($t) => '.'.$t)->implode(',') }}"
               @unless($report) required @endunless>
    </p>

    <fieldset>
        <legend>Is the report a soft copy or a scanned copy?</legend>
        @foreach (\App\Enums\CopyType::cases() as $copy)
            <label>
                <input type="radio" name="report_copy_type" value="{{ $copy->value }}"
                       @checked(old('report_copy_type', $report?->copy_type?->value ?? \App\Enums\CopyType::Soft->value) === $copy->value)>
                {{ $copy === \App\Enums\CopyType::Scanned ? 'Scanned copy' : 'Soft copy' }}
            </label>
        @endforeach
    </fieldset>
</fieldset>
