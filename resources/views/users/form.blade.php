@extends('layouts.plain')

@php($editing = $user->exists)
@php($self = $editing && $user->is(auth()->user()))

@section('title', ($editing ? 'Edit user' : 'Invite user').' - EXTRACK')

@section('content')
    <h1>{{ $editing ? 'Edit user' : 'Invite a user' }}</h1>

    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif
        <p>
            <label for="name">Name</label><br>
            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
        </p>
        <p>
            <label for="email">Email</label><br>
            @if ($editing)
                <input id="email" type="email" value="{{ $user->email }}" disabled>
            @else
                <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            @endif
        </p>
        <p>
            <label for="role">Role</label><br>
            <select id="role" @unless($self) name="role" @endunless required @disabled($self)>
                @foreach (\App\Enums\Role::cases() as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </p>
        @if ($self)
            <input type="hidden" name="role" value="{{ $user->role->value }}">
            <input type="hidden" name="campus_id" value="{{ $user->campus_id }}">
        @endif
        <p>
            <label for="campus_id">Campus (not used for the University President)</label><br>
            <select id="campus_id" @unless($self) name="campus_id" @endunless @disabled($self)>
                <option value="">None</option>
                @foreach ($campuses as $campus)
                    <option value="{{ $campus->id }}" @selected((string) old('campus_id', $user->campus_id) === (string) $campus->id)>{{ $campus->name }}</option>
                @endforeach
            </select>
        </p>
        @if ($self)
            <p>You can't change your own role or campus.</p>
        @endif
        <p>
            <button type="submit">{{ $editing ? 'Save' : 'Create invite' }}</button>
            <a href="{{ route('users.index') }}">Cancel</a>
        </p>
    </form>
@endsection
