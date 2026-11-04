<?php

namespace App\Models;

use App\Enums\AppointmentPurpose;
use App\Enums\AppointmentStatus;
use App\Models\Concerns\RecordsAuthors;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable(['student_id', 'guidance_case_id', 'scheduled_at', 'duration_minutes', 'purpose', 'status', 'remarks'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, RecordsAuthors;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'scheduled',
        'duration_minutes' => 30,
    ];

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
     * Another scheduled appointment for the same student that overlaps the given time.
     */
    public static function conflictFor(int $studentId, Carbon $start, int $durationMinutes, ?int $ignoreId = null): ?self
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        return static::where('student_id', $studentId)
            ->where('status', AppointmentStatus::Scheduled)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('scheduled_at', '<', $end)
            ->where('scheduled_at', '>', $start->copy()->subHours(4))
            ->get()
            ->first(fn (self $other): bool => $other->scheduled_at->copy()->addMinutes($other->duration_minutes)->isAfter($start));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'purpose' => AppointmentPurpose::class,
            'status' => AppointmentStatus::class,
        ];
    }
}
