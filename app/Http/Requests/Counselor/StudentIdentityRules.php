<?php

namespace App\Http\Requests\Counselor;

use Illuminate\Support\Str;

/**
 * Validation shared by the create and edit student forms.
 * Field list is proposed and still needs Guidance Department approval.
 */
final class StudentIdentityRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1990-01-01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributes(): array
    {
        return [
            'lrn' => 'LRN',
            'birth_date' => 'date of birth',
            'school_year_id' => 'school year',
            'section_id' => 'section',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'lrn.digits' => 'The LRN must be exactly 12 digits.',
            'lrn.unique' => 'Another student record already uses this LRN.',
        ];
    }

    /**
     * Trim and collapse spaces in names; strip spaces from the LRN.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalise(array $input): array
    {
        $normalised = [];

        foreach (['first_name', 'middle_name', 'last_name', 'suffix'] as $field) {
            if (is_string($input[$field] ?? null)) {
                $normalised[$field] = Str::squish($input[$field]);
            }
        }

        if (is_string($input['lrn'] ?? null)) {
            $normalised['lrn'] = preg_replace('/\s+/', '', $input['lrn']);
        }

        return $normalised;
    }
}
