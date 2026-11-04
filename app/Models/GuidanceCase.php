<?php

namespace App\Models;

use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use App\Models\Concerns\HasReference;
use App\Models\Concerns\RecordsAuthors;
use Database\Factories\GuidanceCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An administrative record of a guidance concern or support process.
 * It never implies a diagnosis or a disciplinary finding. All counselors
 * share every case; `created_by` records who opened it, not who owns it.
 */
#[Fillable(['student_id', 'referral_id', 'category', 'status', 'opened_on', 'summary', 'closed_on', 'closure_summary'])]
class GuidanceCase extends Model
{
    /** @use HasFactory<GuidanceCaseFactory> */
    use HasFactory, HasReference, RecordsAuthors;

    protected const REFERENCE_PREFIX = 'GC';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<Referral, $this>
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    /**
     * @return HasMany<CounselingSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(CounselingSession::class);
    }

    /**
     * @return HasMany<FollowUp, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /**
     * @return HasMany<ParentCoordination, $this>
     */
    public function coordinations(): HasMany
    {
        return $this->hasMany(ParentCoordination::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function isClosed(): bool
    {
        return $this->status === CaseStatus::Closed;
    }

    /**
     * @param  Builder<GuidanceCase>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereIn('status', CaseStatus::active());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ConcernCategory::class,
            'status' => CaseStatus::class,
            'opened_on' => 'date',
            'closed_on' => 'date',
        ];
    }
}
