<?php

namespace App\Models;

use App\Enums\ConcernCategory;
use App\Enums\ReferralChannel;
use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\RecordsAuthors;
use Database\Factories\ReferralFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A teacher referral, encoded by a counselor. Teachers have no accounts.
 */
#[Fillable(['student_id', 'received_on', 'referring_teacher', 'teacher_role', 'received_via', 'category', 'priority', 'reason', 'status', 'review_outcome'])]
class Referral extends Model
{
    /** @use HasFactory<ReferralFactory> */
    use HasFactory, HasReference, RecordsAuthors;

    protected const REFERENCE_PREFIX = 'RF';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'received',
        'priority' => 'routine',
    ];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * The guidance case opened from this referral, if any.
     *
     * @return HasOne<GuidanceCase, $this>
     */
    public function guidanceCase(): HasOne
    {
        return $this->hasOne(GuidanceCase::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_on' => 'date',
            'received_via' => ReferralChannel::class,
            'category' => ConcernCategory::class,
            'priority' => ReferralPriority::class,
            'status' => ReferralStatus::class,
        ];
    }
}
