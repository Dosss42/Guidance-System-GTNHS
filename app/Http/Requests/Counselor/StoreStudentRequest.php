<?php

namespace App\Http\Requests\Counselor;

use App\Enums\EnrollmentStatus;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new student record, optionally with their enrollment for one school year.
 */
class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...StudentIdentityRules::rules(),
            'lrn' => ['required', 'digits:12', Rule::unique(Student::class)],
            'school_year_id' => ['nullable', 'required_with:section_id', 'integer', Rule::exists('school_years', 'id')],
            'section_id' => ['nullable', 'required_with:school_year_id', 'integer', Rule::exists('sections', 'id')],
            'status' => ['nullable', Rule::enum(EnrollmentStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return StudentIdentityRules::attributes();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return StudentIdentityRules::messages();
    }

    /**
     * Normalise spacing in typed names and LRN before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(StudentIdentityRules::normalise($this->all()));
    }
}
