<?php

namespace App\Services;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearService
{
    public function create(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data): AcademicYear {
            $year = AcademicYear::query()->create([
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'status' => AcademicYearStatus::Draft,
                'ranking_enabled' => (bool) ($data['ranking_enabled'] ?? false),
            ]);

            $this->syncSemesters($year, $data['semesters'] ?? []);

            return $year;
        });
    }

    public function update(AcademicYear $year, array $data): AcademicYear
    {
        if ($year->status === AcademicYearStatus::Archived) {
            throw ValidationException::withMessages([
                'status' => 'Une année archivée ne peut plus être modifiée.',
            ]);
        }

        return DB::transaction(function () use ($year, $data): AcademicYear {
            $year->update([
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'ranking_enabled' => (bool) ($data['ranking_enabled'] ?? false),
            ]);

            $this->syncSemesters($year, $data['semesters'] ?? []);

            return $year->refresh();
        });
    }

    public function activate(AcademicYear $year): void
    {
        if ($year->status === AcademicYearStatus::Archived) {
            throw ValidationException::withMessages([
                'status' => 'Une année archivée ne peut pas être réactivée.',
            ]);
        }

        $other = AcademicYear::query()
            ->where('status', AcademicYearStatus::Active)
            ->whereKeyNot($year->id)
            ->first();

        if ($other) {
            throw ValidationException::withMessages([
                'status' => 'L\'année '.$other->name.' est déjà active. Clôturez-la avant d\'en activer une autre.',
            ]);
        }

        $year->update(['status' => AcademicYearStatus::Active]);
    }

    public function close(AcademicYear $year): void
    {
        if ($year->status !== AcademicYearStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'Seule l\'année active peut être clôturée.',
            ]);
        }

        $year->update(['status' => AcademicYearStatus::Closed]);
    }

    public function archive(AcademicYear $year): void
    {
        if ($year->status !== AcademicYearStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Seule une année clôturée peut être archivée.',
            ]);
        }

        $year->update(['status' => AcademicYearStatus::Archived]);
    }

    /**
     * @param  list<array{name?: string, starts_on?: string, ends_on?: string}>  $semesters
     */
    private function syncSemesters(AcademicYear $year, array $semesters): void
    {
        $kept = [];

        foreach (array_values($semesters) as $index => $semester) {
            $name = trim((string) ($semester['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $record = Semester::query()->updateOrCreate(
                ['academic_year_id' => $year->id, 'name' => $name],
                [
                    'starts_on' => $semester['starts_on'],
                    'ends_on' => $semester['ends_on'],
                    'sort_order' => $index + 1,
                ],
            );

            $kept[] = $record->id;
        }

        $year->semesters()->whereNotIn('id', $kept)->delete();
    }
}
