<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        if ($student instanceof Student) {
            return $this->user()?->can('update', $student) ?? false;
        }

        return $this->user()?->can('create', Student::class) ?? false;
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
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:120'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'student_group_id' => ['nullable', 'integer', 'exists:student_groups,id'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
            'enrolled_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
            'create_account' => ['sometimes', 'boolean'],
            'emergency_contacts' => ['nullable', 'array', 'max:5'],
            'emergency_contacts.*.name' => ['nullable', 'string', 'max:120'],
            'emergency_contacts.*.relationship' => ['nullable', 'string', 'max:80'],
            'emergency_contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'emergency_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'guardian_first_name' => ['nullable', 'string', 'max:100'],
            'guardian_last_name' => ['nullable', 'string', 'max:100'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:80'],
            'guardian_create_account' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'prénom',
            'last_name' => 'nom',
            'birth_date' => 'date de naissance',
            'student_group_id' => 'groupe',
            'level_id' => 'niveau',
            'photo' => 'photo',
        ];
    }
}
