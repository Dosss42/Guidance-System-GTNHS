<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleSlug;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $account */
        $account = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($account)],
            'role' => ['required', Rule::enum(RoleSlug::class)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Administrators cannot change their own role, so the system always keeps one.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $account */
                $account = $this->route('user');

                if ($account->is($this->user()) && $this->input('role') !== $account->role?->slug?->value) {
                    $validator->errors()->add('role', 'You cannot change your own role.');
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
            'email' => 'school email',
        ];
    }
}
