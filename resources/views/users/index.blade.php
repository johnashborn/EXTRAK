@extends('layouts.plain')

@section('title', 'Users - EXTRACK')

@section('content')
    <h1>Users</h1>

    @if (session('invite_url'))
        <section aria-labelledby="invite-heading">
            <h2 id="invite-heading">Invite link</h2>
            <p>Send this link to the invitee. It works once and expires in {{ config('extrack.invite_days') }} days.</p>
            <p>
                <label for="invite-url">Invite URL</label><br>
                <input id="invite-url" type="text" readonly size="80" value="{{ session('invite_url') }}">
                <button type="button" id="copy-invite">Copy</button>
                <span id="copy-result" role="status"></span>
            </p>
        </section>
        <script>
            document.getElementById('copy-invite').addEventListener('click', function () {
                var field = document.getElementById('invite-url');
                var done = function () { document.getElementById('copy-result').textContent = 'Copied'; };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(field.value).then(done);
                } else {
                    field.select();
                    document.execCommand('copy');
                    done();
                }
            });
        </script>
    @endif

    <p><a href="{{ route('users.create') }}">Invite a user</a> | <a href="{{ route('dashboard') }}">Dashboard</a></p>

    <table>
        <thead>
        <tr>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Role</th>
            <th scope="col">Campus</th>
            <th scope="col">Status</th>
            <th scope="col">Last sign-in</th>
            <th scope="col">Actions</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($users as $row)
            <tr>
                <td>{{ $row->name }}</td>
                <td>{{ $row->email }}</td>
                <td>{{ $row->role->label() }}</td>
                <td>{{ $row->campus?->name ?? 'All campuses' }}</td>
                <td>
                    @if (! $row->is_active) Deactivated
                    @elseif ($row->password === null) Invited
                    @else Active
                    @endif
                </td>
                <td>{{ $row->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                <td>
                    <a href="{{ route('users.edit', $row) }}">Edit</a>
                    @can('setActive', $row)
                        <form method="POST" action="{{ route('users.active', $row) }}" style="display:inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $row->is_active ? 0 : 1 }}">
                            <button type="submit">{{ $row->is_active ? 'Deactivate' : 'Reactivate' }}</button>
                        </form>
                    @endcan
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
