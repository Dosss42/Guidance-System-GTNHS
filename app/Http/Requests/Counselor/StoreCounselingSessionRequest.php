<?php

namespace App\Http\Requests\Counselor;

use App\Enums\SessionType;
use App\Models\CounselingSession;
use App\Models\GuidanceCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Records a counseling session on a case, with an optional follow-up.
 */
class StoreCounselingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CounselingSession::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'session_at' => ['required', 'date', 'before_or_equal:now'],
            'session_type' => ['required', Rule::enum(SessionType::class)],
            'confidential_notes' => ['required', 'string', 'max:10000'],
            'next_steps' => ['nullable', 'string', 'max:2000'],
            'follow_up_on' => ['nullable', 'date', 'after_or_equal:today'],
            'follow_up_purpose' => ['required_with:follow_up_on', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var GuidanceCase $case */
                $case = $this->route('case');

                if ($case->isClosed()) {
                    $validator->errors()->add('session_at', 'This case is closed. Reopen it before adding a session.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'session_at' => 'session date and time',
            'confidential_notes' => 'session notes',
            'follow_up_on' => 'follow-up date',
            'follow_up_purpose' => 'follow-up purpose',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'session_at.before_or_equal' => "A session can't be recorded for a future time.",
        ];
    }
}
