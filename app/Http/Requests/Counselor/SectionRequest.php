<?php

namespace App\Http\Requests\Counselor;

use App\Models\Section;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Creates or renames a section within a grade level.
 */
class SectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $section = $this->route('section');

        return $section instanceof Section
            ? ($this->user()?->can('update', $section) ?? false)
            : ($this->user()?->can('create', Section::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grade_level' => ['required', 'integer', Rule::in(Section::GRADE_LEVELS)],
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique(Section::class)
                    ->where('grade_level', $this->integer('grade_level'))
                    ->ignore($this->route('section')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'That grade level already has a section with this name.',
            'grade_level.in' => 'Choose a grade from 7 to 12.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::squish($this->input('name'))]);
        }
    }
}
