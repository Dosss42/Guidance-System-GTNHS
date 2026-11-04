<?php

namespace App\Http\Requests\Counselor;

use App\Enums\FollowUpStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Records a follow-up's outcome, or moves it to a new date.
 */
class UpdateFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('followUp')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(FollowUpStatus::class)],
            'outcome' => ['required_if:status,'.FollowUpStatus::Completed->value, 'nullable', 'string', 'max:2000'],
            'due_on' => ['required_if:status,'.FollowUpStatus::Rescheduled->value, 'nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'outcome.required_if' => 'Write the outcome before marking this follow-up completed.',
            'due_on.required_if' => 'Choose the new date for this follow-up.',
        ];
    }
}
