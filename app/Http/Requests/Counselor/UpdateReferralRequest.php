<?php

namespace App\Http\Requests\Counselor;

use App\Enums\ReferralStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Records a referral review: its status and, when closing or referring
 * out, the outcome.
 */
class UpdateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('referral')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ReferralStatus::class)],
            'review_outcome' => [
                Rule::requiredIf(in_array($this->input('status'), [ReferralStatus::Closed->value, ReferralStatus::ReferredOut->value], true)),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'review_outcome.required' => 'Write a short outcome before closing or referring this out.',
        ];
    }
}
