@php($suggested = (array) ($service->reader_suggestions ?? []))
@php($selectedBarangays = collect(old('barangay_ids', $service->exists ? $service->barangays->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all())
@php($selectedSectors = collect(old('sector_ids', $service->exists ? $service->sectors->pluck('id')->all() : []))->map(fn ($id) => (int) $id)->all())

{{-- A field the report reader filled shows "Please check" until the uploader edits it (prototype: the reader suggests nothing). --}}
@php($check = fn (string $field) => array_key_exists($field, $suggested) ? '<span data-please-check="'.$field.'"> Please check</span>' : '')

<fieldset>
    <legend>Step 2: The details</legend>

    <p>
        <label for="title">Title</label>{!! $check('title') !!}<br>
        <input id="title" name="title" type="text" maxlength="255" value="{{ old('title', $service->title) }}" required>
    </p>
    <p>
        <label for="category_id">Category</label>{!! $check('category_id') !!}<br>
        <select id="category_id" name="category_id">
            <option value="">Choose a category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $service->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </p>
    <p>
        <label for="description">Description (optional)</label>{!! $check('description') !!}<br>
        <textarea id="description" name="description" rows="3">{{ old('description', $service->description) }}</textarea>
    </p>
    <p>
        <label for="municipality_id">Municipality</label>{!! $check('municipality_id') !!}<br>
        <select id="municipality_id" name="municipality_id" data-municipality>
            <option value="">Choose a municipality</option>
            @foreach ($municipalities as $municipality)
                <option value="{{ $municipality->id }}" @selected((string) old('municipality_id', $service->municipality_id) === (string) $municipality->id)>{{ $municipality->name }}</option>
            @endforeach
        </select>
    </p>
    <p>
        <label for="barangay_ids">Barangays (hold Ctrl to choose more than one; they must be in the chosen municipality)</label>{!! $check('barangay_ids') !!}<br>
        <select id="barangay_ids" name="barangay_ids[]" multiple size="8" data-barangays>
            @foreach ($municipalities as $municipality)
                @if ($barangays->has($municipality->id))
                    <optgroup label="{{ $municipality->name }}" data-municipality-id="{{ $municipality->id }}">
                        @foreach ($barangays[$municipality->id] as $barangay)
                            <option value="{{ $barangay->id }}" @selected(in_array($barangay->id, $selectedBarangays, true))>{{ $barangay->name }}</option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
        </select>
    </p>
    <p>
        <label for="start_date">Start date</label>{!! $check('start_date') !!}<br>
        <input id="start_date" name="start_date" type="date" value="{{ old('start_date', $service->start_date?->toDateString()) }}">
    </p>
    <p>
        <label for="end_date">End date</label>{!! $check('end_date') !!}<br>
        <input id="end_date" name="end_date" type="date" value="{{ old('end_date', $service->end_date?->toDateString()) }}">
    </p>
    <p>
        <label for="beneficiaries">People reached</label>{!! $check('beneficiaries') !!}<br>
        <input id="beneficiaries" name="beneficiaries" type="number" min="0" step="1" value="{{ old('beneficiaries', $service->beneficiaries) }}">
    </p>
    <fieldset>
        <legend>Sectors</legend>{!! $check('sector_ids') !!}
        @foreach ($sectors as $sector)
            <label>
                <input type="checkbox" name="sector_ids[]" value="{{ $sector->id }}" @checked(in_array($sector->id, $selectedSectors, true))>
                {{ $sector->name }}
            </label><br>
        @endforeach
    </fieldset>
    <p>
        <label for="partners">Partners (optional)</label>{!! $check('partners') !!}<br>
        <input id="partners" name="partners" type="text" maxlength="255" value="{{ old('partners', $service->partners) }}">
    </p>
    <p>
        <label for="amount_spent">Amount spent in pesos (optional)</label>{!! $check('amount_spent') !!}<br>
        <input id="amount_spent" name="amount_spent" type="number" min="0" step="0.01" value="{{ old('amount_spent', $service->amount_spent) }}">
    </p>
    <p>
        <label for="narrative">Narrative</label>{!! $check('narrative') !!}<br>
        <textarea id="narrative" name="narrative" rows="6">{{ old('narrative', $service->narrative) }}</textarea>
    </p>
</fieldset>
