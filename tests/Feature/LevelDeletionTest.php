<?php

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\Level;
use App\Models\StudentGroup;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('a level linked to a group cannot be deleted', function () {
    $admin = userWithRole('administration');
    $year = AcademicYear::query()->create([
        'name' => '2026-2027',
        'starts_on' => '2026-09-01',
        'ends_on' => '2027-07-31',
        'status' => AcademicYearStatus::Active,
    ]);
    $level = Level::query()->create(['name' => 'Master 1', 'code' => 'M1', 'sort_order' => 5]);
    StudentGroup::query()->create([
        'academic_year_id' => $year->id,
        'level_id' => $level->id,
        'name' => 'Master 1 — A',
        'code' => 'M1-A',
        'color' => '#d97706',
    ]);

    $this->actingAs($admin)
        ->from(route('levels.index'))
        ->delete(route('levels.destroy', $level))
        ->assertRedirect(route('levels.index'))
        ->assertSessionHasErrors([
            'level' => 'Impossible de supprimer le niveau « Master 1 » : il est encore lié au groupe Master 1 — A.',
        ]);

    expect($level->fresh())->not->toBeNull();
});

test('an unused level can be deleted', function () {
    $admin = userWithRole('administration');
    $level = Level::query()->create(['name' => 'Doctorat', 'code' => 'DOC', 'sort_order' => 9]);

    $this->actingAs($admin)
        ->from(route('levels.index'))
        ->delete(route('levels.destroy', $level))
        ->assertRedirect(route('levels.index'))
        ->assertSessionHas('status', 'Niveau supprimé.');

    expect($level->fresh())->toBeNull();
});
