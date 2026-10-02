<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function edit(): View
    {
        return view('account.edit', ['user' => request()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('status', 'Mot de passe mis à jour.');
    }

    public function selectChild(Request $request): RedirectResponse
    {
        $guardian = $request->user()->guardian;
        abort_unless($guardian, 403);

        $data = $request->validate([
            'student_id' => ['required', 'integer'],
        ]);

        abort_unless($guardian->students()->whereKey($data['student_id'])->exists(), 403);
        session(['guardian_student_id' => (int) $data['student_id']]);

        return back();
    }

    public function readNotification(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $url = (string) ($item->data['url'] ?? '/');
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return redirect(str_starts_with($path, '/') ? $path : '/');
    }
}
