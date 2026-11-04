<?php

namespace App\Http\Requests\Counselor;

use App\Models\SchoolYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creates or edits a school year such as "2026–2027".
 */
class SchoolYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schoolYear = $this->route('school_year');

        return $schoolYear instanceof SchoolYear
            ? ($this->user()?->can('update', $schoolYear) ?? false)
            : ($this->user()?->can('create', SchoolYear::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:20', Rule::unique(SchoolYear::class)->ignore($this->route('school_year'))],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'That school year already exists.',
            'end_date.after' => 'The end date must be after the start date.',
        ];
    }
}
