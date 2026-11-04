<?php

namespace App\Http\Requests\Counselor;

use App\Enums\EnrollmentStatus;
use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Adds a student's enrollment for a school year they are not yet enrolled in.
 */
class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('student')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Student $student */
        $student = $this->route('student');

        return [
            'school_year_id' => [
                'required',
                'integer',
                Rule::exists('school_years', 'id'),
                Rule::unique('student_enrollments', 'school_year_id')->where('student_id', $student->id),
            ],
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')],
            'status' => ['required', Rule::enum(EnrollmentStatus::class)],
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
        return [
            'school_year_id.unique' => 'This student already has an enrollment for that school year. Edit it instead.',
        ];
    }
}
