<?php

namespace App\Http\Requests;

use App\Enums\CourseStatus;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        if ($course instanceof Course) {
            return $this->user()?->can('update', $course) ?? false;
        }

        return $this->user()?->can('create', Course::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'student_group_id' => ['required', 'integer', 'exists:student_groups,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'repeat_until' => ['nullable', 'date', 'after:starts_at'],
            'status' => ['sometimes', Rule::enum(CourseStatus::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ends_at.date' => 'L\'heure de fin est invalide.',
            'repeat_until.after' => 'La fin de récurrence doit être postérieure au premier cours.',
        ];
    }
}
