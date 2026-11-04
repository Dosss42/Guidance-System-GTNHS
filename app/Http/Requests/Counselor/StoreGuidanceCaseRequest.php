<?php

namespace App\Http\Requests\Counselor;

use App\Enums\ConcernCategory;
use App\Models\GuidanceCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Opens a guidance case, optionally from a referral for the same student.
 */
class StoreGuidanceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', GuidanceCase::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'referral_id' => [
                'nullable',
                'integer',
                Rule::exists('referrals', 'id')->where('student_id', $this->integer('student_id')),
                Rule::unique('guidance_cases', 'referral_id'),
            ],
            'category' => ['required', Rule::enum(ConcernCategory::class)],
            'opened_on' => ['required', 'date', 'before_or_equal:today'],
            'summary' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'referral_id' => 'referral',
            'opened_on' => 'date opened',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'referral_id.exists' => 'That referral is for a different student.',
            'referral_id.unique' => 'A case has already been opened from this referral.',
        ];
    }
}
