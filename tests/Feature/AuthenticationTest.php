<?php

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a user can sign in and reach the campus dashboard', function () {
    $user = userWithRole('administration');
    AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);

    $this->post('/connexion', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Une vision claire de votre campus.');
});

test('unknown credentials are rejected', function () {
    $this->post('/connexion', [
        'email' => 'inconnu@escm.mg',
        'password' => 'password',
    ])->assertSessionHasErrors('email');
});

test('a signed out user cannot open the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
