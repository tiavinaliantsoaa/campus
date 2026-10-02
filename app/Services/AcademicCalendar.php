<?php

namespace App\Services;

use App\Models\AcademicYear;
use Illuminate\Validation\ValidationException;

class AcademicCalendar
{
    public function ensureOpen(AcademicYear $year): void
    {
        if ($year->allowsChanges()) {
            return;
        }

        throw ValidationException::withMessages([
            'academic_year' => 'Cette année académique est clôturée. Les modifications sont bloquées.',
        ]);
    }
}
