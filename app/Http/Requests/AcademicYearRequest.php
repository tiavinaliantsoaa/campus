<?php

namespace App\Http\Requests;

use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic-years.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $year = $this->route('academicYear');
        $id = $year instanceof AcademicYear ? $year->id : null;

        return [
            'name' => ['required', 'string', 'max:20', Rule::unique('academic_years', 'name')->ignore($id)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'ranking_enabled' => ['sometimes', 'boolean'],
            'semesters' => ['nullable', 'array', 'max:4'],
            'semesters.*.name' => ['nullable', 'string', 'max:80'],
            'semesters.*.starts_on' => ['nullable', 'date', 'required_with:semesters.*.name'],
            'semesters.*.ends_on' => ['nullable', 'date', 'required_with:semesters.*.name', 'after:semesters.*.starts_on'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Cette année académique existe déjà.',
            'ends_on.after' => 'La date de fin doit être postérieure à la date de début.',
        ];
    }
}
