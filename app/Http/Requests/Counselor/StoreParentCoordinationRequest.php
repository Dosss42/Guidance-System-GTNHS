<?php

namespace App\Http\Requests\Counselor;

use App\Enums\ContactMethod;
use App\Models\ParentCoordination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Records a contact with a student's parent or guardian.
 */
class StoreParentCoordinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ParentCoordination::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'guidance_case_id' => ['nullable', 'integer', Rule::exists('guidance_cases', 'id')->where('student_id', $this->integer('student_id'))],
            'coordinated_at' => ['required', 'date', 'before_or_equal:now'],
            'contact_name' => ['required', 'string', 'max:120'],
            'relationship' => ['required', 'string', 'max:40'],
            'method' => ['required', Rule::enum(ContactMethod::class)],
            'purpose' => ['required', 'string', 'max:255'],
            'outcome' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'guidance_case_id' => 'guidance case',
            'coordinated_at' => 'date and time',
            'contact_name' => 'name of parent or guardian',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'guidance_case_id.exists' => 'That case belongs to a different student.',
            'coordinated_at.before_or_equal' => "A contact can't be recorded for a future time.",
        ];
    }
}
