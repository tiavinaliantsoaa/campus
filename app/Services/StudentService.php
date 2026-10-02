<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\AcademicYear;
use App\Models\EmergencyContact;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function __construct(
        private AcademicCalendar $calendar,
        private AccountProvisioner $accounts,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{student: Student, passwords: list<string>}
     */
    public function create(array $data, AcademicYear $year, User $actor): array
    {
        $this->calendar->ensureOpen($year);
        $this->assertGroupBelongsToYear($data['student_group_id'] ?? null, $year);

        return DB::transaction(function () use ($data, $year): array {
            $passwords = [];
            $userId = null;

            if (! empty($data['create_account'])) {
                if (empty($data['email'])) {
                    throw ValidationException::withMessages([
                        'email' => 'Un e-mail est requis pour créer un accès étudiant.',
                    ]);
                }

                $account = $this->accounts->provision($data['first_name'].' '.$data['last_name'], $data['email'], 'student');
                $userId = $account['user']->id;

                if ($account['user']->student()->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ce compte est déjà lié à un étudiant.',
                    ]);
                }

                if ($account['password']) {
                    $passwords[] = 'Étudiant '.$data['email'].' : '.$account['password'];
                }
            }

            $student = Student::query()->create([
                'user_id' => $userId,
                'matricule' => $this->nextMatricule($year),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'Madagascar',
                'status' => $data['status'] ?? StudentStatus::Active,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $student->update([
                    'photo_path' => $data['photo']->store('photos/students', 'local'),
                ]);
            }

            $this->syncEnrollment($student, $year, $data);
            $this->syncEmergencyContacts($student, $data['emergency_contacts'] ?? []);
            $guardianPassword = $this->syncGuardian($student, $data);

            if ($guardianPassword) {
                $passwords[] = $guardianPassword;
            }

            return ['student' => $student->refresh(), 'passwords' => $passwords];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function update(Student $student, array $data, AcademicYear $year): array
    {
        $this->calendar->ensureOpen($year);
        $this->assertGroupBelongsToYear($data['student_group_id'] ?? null, $year);

        return DB::transaction(function () use ($student, $data, $year): array {
            $passwords = [];

            $student->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'Madagascar',
                'status' => $data['status'] ?? $student->status,
                'notes' => $data['notes'] ?? null,
            ]);

            if (! empty($data['remove_photo']) && $student->photo_path) {
                Storage::disk('local')->delete($student->photo_path);
                $student->update(['photo_path' => null]);
            }

            if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
                if ($student->photo_path) {
                    Storage::disk('local')->delete($student->photo_path);
                }

                $student->update([
                    'photo_path' => $data['photo']->store('photos/students', 'local'),
                ]);
            }

            if (! empty($data['create_account']) && ! $student->user_id) {
                if (empty($data['email'])) {
                    throw ValidationException::withMessages([
                        'email' => 'Un e-mail est requis pour créer un accès étudiant.',
                    ]);
                }

                $account = $this->accounts->provision($student->full_name, $data['email'], 'student');

                if ($account['user']->student()->whereKeyNot($student->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ce compte est déjà lié à un autre étudiant.',
                    ]);
                }

                $student->update(['user_id' => $account['user']->id]);

                if ($account['password']) {
                    $passwords[] = 'Étudiant '.$data['email'].' : '.$account['password'];
                }
            }

            $this->syncEnrollment($student, $year, $data);
            $this->syncEmergencyContacts($student, $data['emergency_contacts'] ?? []);
            $guardianPassword = $this->syncGuardian($student, $data);

            if ($guardianPassword) {
                $passwords[] = $guardianPassword;
            }

            return $passwords;
        });
    }

    public function archive(Student $student, AcademicYear $year): void
    {
        $this->calendar->ensureOpen($year);
        $student->update(['status' => StudentStatus::Archived]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncEnrollment(Student $student, AcademicYear $year, array $data): void
    {
        Enrollment::query()->updateOrCreate(
            ['student_id' => $student->id, 'academic_year_id' => $year->id],
            [
                'student_group_id' => $data['student_group_id'] ?? null,
                'level_id' => $data['level_id'] ?? null,
                'status' => ($data['status'] ?? StudentStatus::Active->value) === StudentStatus::Active->value ? 'active' : 'inactive',
                'enrolled_on' => $data['enrolled_on'] ?? now()->toDateString(),
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $contacts
     */
    private function syncEmergencyContacts(Student $student, array $contacts): void
    {
        $student->emergencyContacts()->delete();

        foreach ($contacts as $contact) {
            $name = trim((string) ($contact['name'] ?? ''));
            $phone = trim((string) ($contact['phone'] ?? ''));

            if ($name === '' || $phone === '') {
                continue;
            }

            EmergencyContact::query()->create([
                'student_id' => $student->id,
                'name' => $name,
                'relationship' => $contact['relationship'] ?? null,
                'phone' => $phone,
                'email' => $contact['email'] ?? null,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncGuardian(Student $student, array $data): ?string
    {
        $first = trim((string) ($data['guardian_first_name'] ?? ''));
        $last = trim((string) ($data['guardian_last_name'] ?? ''));

        if ($first === '' || $last === '') {
            return null;
        }

        $email = $data['guardian_email'] ?? null;
        $guardian = $email
            ? Guardian::query()->where('email', $email)->first()
            : null;

        if (! $guardian) {
            $guardian = Guardian::query()->create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'phone' => $data['guardian_phone'] ?? null,
            ]);
        }

        $password = null;

        if (! empty($data['guardian_create_account']) && $email && ! $guardian->user_id) {
            $account = $this->accounts->provision($guardian->full_name, $email, 'parent');
            $guardian->update(['user_id' => $account['user']->id]);

            if ($account['password']) {
                $password = 'Parent '.$email.' : '.$account['password'];
            }
        }

        $student->guardians()->syncWithoutDetaching([
            $guardian->id => [
                'relationship' => $data['guardian_relationship'] ?? 'Responsable',
                'is_primary' => true,
            ],
        ]);

        return $password;
    }

    private function assertGroupBelongsToYear(mixed $groupId, AcademicYear $year): void
    {
        if (! $groupId) {
            return;
        }

        $belongs = StudentGroup::query()
            ->whereKey($groupId)
            ->where('academic_year_id', $year->id)
            ->exists();

        if (! $belongs) {
            throw ValidationException::withMessages([
                'student_group_id' => 'Ce groupe n\'appartient pas à l\'année académique sélectionnée.',
            ]);
        }
    }

    private function nextMatricule(AcademicYear $year): string
    {
        $prefix = 'ESCM-'.$year->starts_on->format('Y').'-';
        $last = Student::withTrashed()
            ->where('matricule', 'like', $prefix.'%')
            ->orderByDesc('matricule')
            ->lockForUpdate()
            ->value('matricule');

        $sequence = $last ? ((int) substr((string) $last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
