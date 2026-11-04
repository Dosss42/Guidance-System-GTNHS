<?php

namespace App\Http\Requests\Counselor;

use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Updates a case's status and details. Closing requires a closure summary.
 */
class UpdateGuidanceCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('case')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(CaseStatus::class)],
            'category' => ['required', Rule::enum(ConcernCategory::class)],
            'summary' => ['nullable', 'string', 'max:3000'],
            'closure_summary' => ['required_if:status,'.CaseStatus::Closed->value, 'nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'closure_summary.required_if' => 'Write a closure summary before closing the case.',
        ];
    }
}
