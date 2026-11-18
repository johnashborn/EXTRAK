@extends('layouts.plain')

@section('title', 'Accept invite - EXTRACK')

@section('content')
    <h1>Welcome, {{ $invitee->name }}</h1>
    <p>Choose a password to activate your EXTRACK account ({{ $invitee->role->label() }}).</p>

    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ url()->full() }}">
        @csrf
        <p>
            <label for="password">Password</label><br>
            <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" autofocus>
        </p>
        <p>
            <label for="password_confirmation">Confirm password</label><br>
            <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
        </p>
        <p><button type="submit">Set password and sign in</button></p>
    </form>
@endsection
