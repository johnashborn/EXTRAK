@extends('layouts.plain')

@section('title', 'Dashboard - EXTRACK')

@section('content')
    <h1>Dashboard</h1>
    <p>Signed in as {{ auth()->user()->name }}.</p>
    @can('viewAny', \App\Models\User::class)
        <p><a href="{{ route('users.index') }}">Users</a></p>
    @endcan
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Sign out</button>
    </form>
@endsection
