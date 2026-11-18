@extends('layouts.plain')

@php($editing = $service->exists)
@php($published = $service->state === \App\Enums\ServiceState::Published)
@php($maxMb = config('extrack.files.max_mb'))

@section('title', ($editing ? 'Edit record' : 'Add a record').' - EXTRACK')

@section('content')
    <h1>{{ $editing ? 'Edit record' : 'Add a completed service' }}</h1>
    @if ($editing)
        <p>{{ $service->code ?? 'Draft' }}</p>
    @endif

    {{-- Filled in by upload.js from a JSON error; the plain post shows the list below instead. --}}
    <div data-upload-errors role="alert" @if (! $errors->any()) hidden @endif>
        @if ($errors->any())
            <p>Please fix the following:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <form method="POST"
          action="{{ $editing ? route('services.update', $service) : route('services.store') }}"
          enctype="multipart/form-data"
          data-upload-form
          data-max-mb="{{ $maxMb }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @include('services._step-report')
        @include('services._step-details')
        @include('services._step-files')

        <div data-upload-progress hidden>
            <label for="upload-progress">Uploading</label>
            <progress id="upload-progress" max="100" value="0"></progress>
            <span data-upload-percent>0%</span>
        </div>

        <p>
            @if ($published)
                <button type="submit" name="intent" value="publish">Save changes</button>
            @else
                <button type="submit" name="intent" value="draft">Save draft</button>
                @can('publish', $service)
                    <button type="submit" name="intent" value="publish">Publish record</button>
                @endcan
            @endif
            <a href="{{ $editing ? route('services.show', $service) : route('dashboard') }}">Cancel</a>
        </p>
    </form>

    @if ($editing && ! $published)
        @can('discard', $service)
            <form method="POST" action="{{ route('services.discard', $service) }}"
                  onsubmit="return confirm('Discard this draft? The draft and its files are removed for good.')">
                @csrf
                @method('DELETE')
                <button type="submit">Discard draft</button>
            </form>
        @endcan
    @endif

    @vite('resources/js/upload.js')
@endsection
