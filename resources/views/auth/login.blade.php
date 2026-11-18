@extends('layouts.plain')

@section('title', 'Sign in - EXTRACK')

@section('content')
    <h1>Sign in to EXTRACK</h1>

    @if ($errors->any())
        <ul role="alert">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ url('/login') }}">
        @csrf
        <p>
            <label for="email">Email</label><br>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" autofocus>
        </p>
        <p>
            <label for="password">Password</label><br>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </p>
        <p><button type="submit">Sign in</button></p>
    </form>

    @if ($demoUsers->isNotEmpty())
        <h2>Demo</h2>
        @foreach ($demoUsers as $demoUser)
            <form method="POST" action="{{ route('login.demo', $demoUser) }}">
                @csrf
                <button type="submit">Sign in as {{ $demoUser->name }} ({{ $demoUser->role->label() }})</button>
            </form>
        @endforeach
    @endif
@endsection
