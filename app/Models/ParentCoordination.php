<?php

namespace App\Models;

use App\Enums\ContactMethod;
use App\Models\Concerns\RecordsAuthors;
use Database\Factories\ParentCoordinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A contact with a student's parent or guardian. They have no accounts.
 */
#[Fillable(['student_id', 'guidance_case_id', 'coordinated_at', 'contact_name', 'relationship', 'method', 'purpose', 'outcome'])]
class ParentCoordination extends Model
{
    /** @use HasFactory<ParentCoordinationFactory> */
    use HasFactory, RecordsAuthors;

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<GuidanceCase, $this>
     */
    public function guidanceCase(): BelongsTo
    {
        return $this->belongsTo(GuidanceCase::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'coordinated_at' => 'datetime',
            'method' => ContactMethod::class,
        ];
    }
}
