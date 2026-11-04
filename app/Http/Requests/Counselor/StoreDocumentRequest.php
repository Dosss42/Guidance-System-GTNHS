<?php

namespace App\Http\Requests\Counselor;

use App\Enums\DocumentCategory;
use App\Models\Document;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Validates a document upload: the file's real content type, its
 * extension and its size are all checked.
 */
class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'guidance_case_id' => ['nullable', 'integer', Rule::exists('guidance_cases', 'id')->where('student_id', $this->integer('student_id'))],
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'file' => [
                'required',
                File::types(Document::ALLOWED_EXTENSIONS)->extensions(Document::ALLOWED_EXTENSIONS)->max(Document::MAX_KILOBYTES),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'guidance_case_id' => 'guidance case',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.uploaded' => "The file didn't reach the server. It may be larger than the server's upload limit.",
            'file.mimes' => 'Upload a PDF, JPG, PNG or Word (.docx) file.',
            'file.extensions' => 'Upload a PDF, JPG, PNG or Word (.docx) file.',
            'file.max' => 'The file is larger than 5 MB.',
            'guidance_case_id.exists' => 'That case belongs to a different student.',
        ];
    }
}
