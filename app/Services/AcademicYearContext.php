<?php

namespace App\Services;

use App\Models\AcademicYear;

class AcademicYearContext
{
    public function current(): ?AcademicYear
    {
        $selected = session('academic_year_id');

        if ($selected) {
            $year = AcademicYear::query()->find($selected);

            if ($year) {
                return $year;
            }
        }

        return AcademicYear::query()->where('status', 'active')->orderByDesc('starts_on')->first()
            ?? AcademicYear::query()->orderByDesc('starts_on')->first();
    }

    public function select(AcademicYear $year): void
    {
        session(['academic_year_id' => $year->id]);
    }
}
