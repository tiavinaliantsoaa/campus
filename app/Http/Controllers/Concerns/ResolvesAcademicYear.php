<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicYear;
use App\Services\AcademicYearContext;

trait ResolvesAcademicYear
{
    protected function academicYear(): AcademicYear
    {
        $year = app(AcademicYearContext::class)->current();

        abort_unless($year instanceof AcademicYear, 404, 'Aucune année académique n\'est configurée.');

        return $year;
    }
}
