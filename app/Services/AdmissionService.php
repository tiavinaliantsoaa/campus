<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\AdmissionStatus;
use App\Models\Applicant;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionService
{
    public function __construct(
        private AcademicCalendar $calendar,
        private StudentService $students,
    ) {}

    public function changeStatus(Applicant $applicant, AdmissionStatus $status, User $actor): void
    {
        $this->calendar->ensureOpen($applicant->academicYear);

        if ($applicant->student_id && $status->slug !== 'converted') {
            throw ValidationException::withMessages([
                'admission_status_id' => 'Ce candidat a déjà été converti en étudiant.',
            ]);
        }

        $applicant->update([
            'admission_status_id' => $status->id,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function convert(Applicant $applicant, array $data, User $actor): Student
    {
        $year = $applicant->academicYear;
        $this->calendar->ensureOpen($year);

        if ($applicant->student_id) {
            throw ValidationException::withMessages([
                'applicant' => 'Ce candidat est déjà un étudiant.',
            ]);
        }

        $applicant->loadMissing('admissionStatus');

        if (! $applicant->admissionStatus->converts_to_student) {
            throw ValidationException::withMessages([
                'applicant' => 'Seule une candidature admise peut être convertie en étudiant.',
            ]);
        }

        return DB::transaction(function () use ($applicant, $data, $actor, $year): Student {
            $result = $this->students->create([
                'first_name' => $applicant->first_name,
                'last_name' => $applicant->last_name,
                'email' => $applicant->email,
                'phone' => $applicant->phone,
                'birth_date' => $applicant->birth_date?->toDateString(),
                'address' => $applicant->address,
                'level_id' => $data['level_id'] ?? $applicant->level_id,
                'student_group_id' => $data['student_group_id'] ?? null,
                'status' => StudentStatus::Active->value,
                'enrolled_on' => now()->toDateString(),
                'create_account' => ! empty($data['create_account']),
            ], $year, $actor);

            $converted = AdmissionStatus::query()->where('slug', 'converted')->first();

            $applicant->update([
                'student_id' => $result['student']->id,
                'admission_status_id' => $converted?->id ?? $applicant->admission_status_id,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);

            Enrollment::query()
                ->where('student_id', $result['student']->id)
                ->where('academic_year_id', $year->id)
                ->update([
                    'level_id' => $data['level_id'] ?? $applicant->level_id,
                    'student_group_id' => $data['student_group_id'] ?? null,
                ]);

            session()->flash('temporary_passwords', $result['passwords']);

            return $result['student'];
        });
    }
}
