@php($types = config('extrack.files.types'))
@php($supporting = $service->exists ? $service->documents->reject(fn ($d) => $d->kind === \App\Enums\DocumentKind::AccomplishmentReport) : collect())
@php($removing = collect(old('remove_document_ids', []))->map(fn ($id) => (int) $id)->all())

<fieldset>
    <legend>Step 3: Supporting files (optional)</legend>
    <p>Attendance sheets, photos, letters, the proposal, the MOA and other files.
        {{ strtoupper(implode(', ', $types)) }}, up to {{ $maxMb }} MB each.</p>

    @if ($supporting->isNotEmpty())
        <p>Files already attached (tick to remove):</p>
        <ul>
            @foreach ($supporting as $document)
                <li>
                    <label>
                        <input type="checkbox" name="remove_document_ids[]" value="{{ $document->id }}" @checked(in_array($document->id, $removing, true))>
                        Remove {{ $document->original_name }} ({{ $document->kind->label() }})
                    </label>
                </li>
            @endforeach
        </ul>
    @endif

    <div data-attachment-rows>
        @for ($i = 0; $i < 3; $i++)
            <div data-attachment-row>
                <label for="attachments-{{ $i }}-file">File</label>
                <input id="attachments-{{ $i }}-file" name="attachments[{{ $i }}][file]" type="file"
                       accept="{{ collect($types)->map(fn ($t) => '.'.$t)->implode(',') }}">

                <label for="attachments-{{ $i }}-kind">Kind</label>
                <select id="attachments-{{ $i }}-kind" name="attachments[{{ $i }}][kind]">
                    @foreach ($kinds as $kind)
                        <option value="{{ $kind->value }}">{{ $kind->label() }}</option>
                    @endforeach
                </select>

                <label for="attachments-{{ $i }}-copy">Copy</label>
                <select id="attachments-{{ $i }}-copy" name="attachments[{{ $i }}][copy_type]">
                    @foreach (\App\Enums\CopyType::cases() as $copy)
                        <option value="{{ $copy->value }}">{{ $copy === \App\Enums\CopyType::Scanned ? 'Scanned copy' : 'Soft copy' }}</option>
                    @endforeach
                </select>
            </div>
        @endfor
    </div>
    <p><button type="button" data-add-attachment hidden>Add another file</button></p>
</fieldset>
