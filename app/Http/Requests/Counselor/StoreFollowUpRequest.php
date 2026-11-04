<?php

namespace App\Http\Requests\Counselor;

use App\Models\FollowUp;
use App\Models\GuidanceCase;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Schedules a follow-up on a guidance case.
 */
class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FollowUp::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'due_on' => ['required', 'date', 'after_or_equal:today'],
            'purpose' => ['required', 'string', 'max:255'],
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
                    $validator->errors()->add('due_on', 'This case is closed. Reopen it before scheduling a follow-up.');
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
            'due_on' => 'follow-up date',
        ];
    }
}
