<?php

namespace App\Models;

use App\Enums\SessionType;
use App\Models\Concerns\RecordsAuthors;
use Database\Factories\CounselingSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Highly confidential counseling documentation. Notes are encrypted at
 * rest, hidden from serialization, and only released through the
 * logged "unseal" endpoint.
 */
#[Fillable(['guidance_case_id', 'session_at', 'session_type', 'confidential_notes', 'next_steps'])]
#[Hidden(['confidential_notes', 'next_steps'])]
class CounselingSession extends Model
{
    /** @use HasFactory<CounselingSessionFactory> */
    use HasFactory, RecordsAuthors;

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
            'session_at' => 'datetime',
            'session_type' => SessionType::class,
            'confidential_notes' => 'encrypted',
            'next_steps' => 'encrypted',
        ];
    }
}
