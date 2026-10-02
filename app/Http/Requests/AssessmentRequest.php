<?php

namespace App\Http\Requests;

use App\Enums\AssessmentType;
use App\Models\Assessment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Assessment::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::enum(AssessmentType::class)],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'student_group_id' => ['required', 'integer', 'exists:student_groups,id'],
            'semester_id' => ['nullable', 'integer', 'exists:semesters,id'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:20'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
            'assessed_on' => ['required', 'date'],
        ];
    }
}
