<?php

namespace App\Services;

use App\Enums\TeacherStatus;
use App\Models\GroupAssignment;
use App\Models\Teacher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TeacherService
{
    public function __construct(private AccountProvisioner $accounts) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{teacher: Teacher, password: ?string}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $password = null;
            $userId = null;

            if (! empty($data['create_account'])) {
                if (empty($data['email'])) {
                    throw ValidationException::withMessages([
                        'email' => 'Un e-mail est requis pour créer un accès enseignant.',
                    ]);
                }

                $account = $this->accounts->provision($data['first_name'].' '.$data['last_name'], $data['email'], 'teacher');

                if ($account['user']->teacher()->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ce compte est déjà lié à un enseignant.',
                    ]);
                }

                $userId = $account['user']->id;
                $password = $account['password'] ? 'Enseignant '.$data['email'].' : '.$account['password'] : null;
            }

            $teacher = Teacher::query()->create([
                'user_id' => $userId,
                'employee_number' => $this->nextEmployeeNumber(),
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? TeacherStatus::Active,
                'biography' => $data['biography'] ?? null,
            ]);

            if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $teacher->update(['photo_path' => $data['photo']->store('photos/teachers', 'local')]);
            }

            $teacher->subjects()->sync($data['subject_ids'] ?? []);
            $this->syncAssignments($teacher, $data['assignments'] ?? []);

            return ['teacher' => $teacher->refresh(), 'password' => $password];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): ?string
    {
        return DB::transaction(function () use ($teacher, $data): ?string {
            $password = null;

            $teacher->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => $data['status'] ?? $teacher->status,
                'biography' => $data['biography'] ?? null,
            ]);

            if (! empty($data['photo']) && $data['photo'] instanceof UploadedFile) {
                if ($teacher->photo_path) {
                    Storage::disk('local')->delete($teacher->photo_path);
                }

                $teacher->update(['photo_path' => $data['photo']->store('photos/teachers', 'local')]);
            }

            if (! empty($data['create_account']) && ! $teacher->user_id) {
                if (empty($data['email'])) {
                    throw ValidationException::withMessages([
                        'email' => 'Un e-mail est requis pour créer un accès enseignant.',
                    ]);
                }

                $account = $this->accounts->provision($teacher->full_name, $data['email'], 'teacher');

                if ($account['user']->teacher()->whereKeyNot($teacher->id)->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'Ce compte est déjà lié à un autre enseignant.',
                    ]);
                }

                $teacher->update(['user_id' => $account['user']->id]);
                $password = $account['password'] ? 'Enseignant '.$data['email'].' : '.$account['password'] : null;
            }

            $teacher->subjects()->sync($data['subject_ids'] ?? []);
            $this->syncAssignments($teacher, $data['assignments'] ?? []);

            return $password;
        });
    }

    /**
     * @param  list<array{student_group_id?: int, subject_id?: int}>  $assignments
     */
    private function syncAssignments(Teacher $teacher, array $assignments): void
    {
        $teacher->assignments()->delete();

        foreach ($assignments as $assignment) {
            $groupId = $assignment['student_group_id'] ?? null;
            $subjectId = $assignment['subject_id'] ?? null;

            if (! $groupId || ! $subjectId) {
                continue;
            }

            GroupAssignment::query()->create([
                'teacher_id' => $teacher->id,
                'student_group_id' => $groupId,
                'subject_id' => $subjectId,
            ]);
        }
    }

    private function nextEmployeeNumber(): string
    {
        $last = Teacher::withTrashed()->orderByDesc('id')->lockForUpdate()->value('employee_number');
        $sequence = $last ? ((int) preg_replace('/\D/', '', (string) $last)) + 1 : 1;

        return 'ENS-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
