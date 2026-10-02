<?php

namespace App\Http\Requests;

use App\Enums\TeacherStatus;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        if ($teacher instanceof Teacher) {
            return $this->user()?->can('update', $teacher) ?? false;
        }

        return $this->user()?->can('create', Teacher::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::enum(TeacherStatus::class)],
            'biography' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'create_account' => ['sometimes', 'boolean'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'assignments' => ['nullable', 'array', 'max:30'],
            'assignments.*.student_group_id' => ['nullable', 'integer', 'exists:student_groups,id'],
            'assignments.*.subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
        ];
    }
}
