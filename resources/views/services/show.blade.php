@extends('layouts.plain')

@section('title', $service->title.' - EXTRACK')

@section('content')
    {{-- Stub record page; Task 8 builds the full one. --}}
    <h1>{{ $service->title }}</h1>
    <p>{{ $service->code ?? 'Draft' }} &middot; {{ $service->state === \App\Enums\ServiceState::Published ? 'Published' : 'Draft' }}</p>

    <h2>Documents</h2>
    @if ($service->documents->isEmpty())
        <p>No documents.</p>
    @else
        <ul>
            @foreach ($service->documents as $document)
                <li>{{ $document->original_name }} ({{ $document->kind->label() }})</li>
            @endforeach
        </ul>
    @endif

    <p>
        @can('update', $service)
            <a href="{{ route('services.edit', $service) }}">Edit</a>
        @endcan
        <a href="{{ route('dashboard') }}">Back to the dashboard</a>
    </p>

    @can('publish', $service)
        <form method="POST" action="{{ route('services.publish', $service) }}">
            @csrf
            <button type="submit">Publish record</button>
        </form>
    @endcan
@endsection
