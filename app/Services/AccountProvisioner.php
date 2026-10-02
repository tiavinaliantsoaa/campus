<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountProvisioner
{
    /**
     * @return array{user: User, password: ?string}
     */
    public function provision(string $name, string $email, string $roleSlug, ?User $existing = null): array
    {
        $role = Role::query()->where('slug', $roleSlug)->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'email' => 'Le rôle demandé est introuvable.',
            ]);
        }

        $password = null;

        if ($existing) {
            $existing->roles()->syncWithoutDetaching([$role->id]);

            return ['user' => $existing, 'password' => null];
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->roles()->syncWithoutDetaching([$role->id]);

            return ['user' => $user, 'password' => null];
        }

        $password = Str::password(12);
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->roles()->sync([$role->id]);

        return ['user' => $user, 'password' => $password];
    }
}
