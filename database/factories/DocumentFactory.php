<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Document;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Metadata only. Tests that download a document must also put a file at
 * `storage_path` on the faked "local" disk.
 *
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'title' => 'Sample referral slip',
            'category' => DocumentCategory::ReferralSlip,
            'original_name' => 'referral slip.pdf',
            'storage_path' => 'documents/'.now()->format('Y/m').'/'.Str::random(40).'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 2048,
            'sha256' => str_repeat('0', 64),
        ];
    }
}
