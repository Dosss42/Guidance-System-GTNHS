<?php

namespace App\Http\Requests\Counselor;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
        return [
            ...StudentIdentityRules::rules(),
            'lrn' => ['required', 'digits:12', Rule::unique(Student::class)->ignore($this->route('student'))],
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

    protected function prepareForValidation(): void
    {
        $this->merge(StudentIdentityRules::normalise($this->all()));
    }
}
