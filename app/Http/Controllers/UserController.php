<?php

namespace App\Http\Controllers;

use App\Actions\Users\InviteUser;
use App\Actions\Users\SetUserActive;
use App\Actions\Users\UpdateUser;
use App\Http\Requests\InviteUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('users.index', [
            'users' => User::with('campus')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('users.form', ['user' => new User, 'campuses' => Campus::orderBy('name')->get()]);
    }

    public function store(InviteUserRequest $request, InviteUser $invite): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $result = $invite->handle($request->user(), $request->validated());

        return redirect()->route('users.index')
            ->with('status', "Invite created for {$result['user']->name}.")
            ->with('invite_url', $result['url']);
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('users.form', ['user' => $user, 'campuses' => Campus::orderBy('name')->get()]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $update): RedirectResponse
    {
        Gate::authorize('update', $user);

        $update->handle($request->user(), $user, $request->validated());

        return redirect()->route('users.index')->with('status', 'User updated.');
    }

    public function setActive(Request $request, User $user, SetUserActive $setActive): RedirectResponse
    {
        Gate::authorize('setActive', $user);

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $setActive->handle($request->user(), $user, (bool) $data['is_active']);

        return redirect()->route('users.index')->with('status', 'User updated.');
    }
}
