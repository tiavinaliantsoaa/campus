<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use App\Models\Course;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        if (! $course instanceof Course) {
            return false;
        }

        Gate::authorize('recordAttendance', $course);

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::enum(AttendanceStatus::class)],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statuses.required' => 'L\'appel doit indiquer le statut de chaque étudiant.',
            'statuses.*.required' => 'Chaque étudiant doit avoir un statut de présence.',
        ];
    }
}
