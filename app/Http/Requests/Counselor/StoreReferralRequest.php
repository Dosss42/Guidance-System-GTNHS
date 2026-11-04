<?php

namespace App\Http\Requests\Counselor;

use App\Enums\ConcernCategory;
use App\Enums\ReferralChannel;
use App\Enums\ReferralPriority;
use App\Models\Referral;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A teacher referral encoded from a slip, conversation or message.
 */
class StoreReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Referral::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'received_on' => ['required', 'date', 'before_or_equal:today'],
            'referring_teacher' => ['required', 'string', 'max:120'],
            'teacher_role' => ['nullable', 'string', 'max:120'],
            'received_via' => ['required', Rule::enum(ReferralChannel::class)],
            'category' => ['required', Rule::enum(ConcernCategory::class)],
            'priority' => ['required', Rule::enum(ReferralPriority::class)],
            'reason' => ['required', 'string', 'max:3000'],
            'open_case' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'received_on' => 'date received',
            'received_via' => 'how it was received',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'received_on.before_or_equal' => "The date can't be later than today.",
        ];
    }
}
