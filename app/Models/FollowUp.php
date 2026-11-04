<?php

namespace App\Models;

use App\Enums\FollowUpStatus;
use App\Models\Concerns\RecordsAuthors;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['guidance_case_id', 'due_on', 'purpose', 'status', 'outcome', 'completed_at'])]
class FollowUp extends Model
{
    /** @use HasFactory<FollowUpFactory> */
    use HasFactory, RecordsAuthors;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return BelongsTo<GuidanceCase, $this>
     */
    public function guidanceCase(): BelongsTo
    {
        return $this->belongsTo(GuidanceCase::class);
    }

    public function isOverdue(): bool
    {
        return $this->status === FollowUpStatus::Pending && $this->due_on->isBefore(today());
    }

    /**
     * @param  Builder<FollowUp>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', FollowUpStatus::Pending);
    }

    /**
     * @param  Builder<FollowUp>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query->where('status', FollowUpStatus::Pending)->whereDate('due_on', '<', today());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_on' => 'date',
            'status' => FollowUpStatus::class,
            'completed_at' => 'datetime',
        ];
    }
}
