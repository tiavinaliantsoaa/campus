<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->hasPermission('administration.manage'), 403);

        return view('users.index', [
            'users' => User::query()->with('roles')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->hasPermission('administration.manage'), 403);

        return view('users.form', [
            'user' => new User,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('administration.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->roles()->sync([$data['role_id']]);

        return redirect()->route('users.index')->with('status', 'Compte créé.');
    }

    public function edit(User $user): View
    {
        abort_unless(request()->user()->hasPermission('administration.manage'), 403);
        $user->load('roles');

        return view('users.form', [
            'user' => $user,
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('administration.manage'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...($data['password'] ? ['password' => $data['password']] : []),
        ]);
        $user->roles()->sync([$data['role_id']]);

        return redirect()->route('users.index')->with('status', 'Compte mis à jour.');
    }
}
