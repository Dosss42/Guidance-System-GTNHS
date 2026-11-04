<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthors;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * A student's identity. Grade and section belong to each year's enrollment.
 */
#[Fillable(['lrn', 'first_name', 'middle_name', 'last_name', 'suffix', 'birth_date'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, RecordsAuthors;

    /**
     * "Villanueva, Andrea R." — the filing-style name used across the office.
     */
    public function filingName(): string
    {
        $middleInitial = $this->middle_name ? ' '.Str::upper(Str::substr($this->middle_name, 0, 1)).'.' : '';
        $suffix = $this->suffix ? " {$this->suffix}" : '';

        return "{$this->last_name}, {$this->first_name}{$middleInitial}{$suffix}";
    }

    /**
     * The LRN with all but the last four digits hidden: "•••• •••• 0214".
     */
    public function maskedLrn(): string
    {
        return '•••• •••• '.Str::substr($this->lrn, -4);
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * The enrollment for whichever school year is marked current.
     *
     * @return HasOne<StudentEnrollment, $this>
     */
    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class)->whereRelation('schoolYear', 'is_current', true);
    }

    /**
     * @return HasMany<Referral, $this>
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    /**
     * @return HasMany<GuidanceCase, $this>
     */
    public function guidanceCases(): HasMany
    {
        return $this->hasMany(GuidanceCase::class);
    }

    /**
     * @return HasMany<Appointment, $this>
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * @return HasMany<ParentCoordination, $this>
     */
    public function coordinations(): HasMany
    {
        return $this->hasMany(ParentCoordination::class);
    }

    /**
     * Match a name or the start of an LRN. Values are bound, never interpolated.
     *
     * @param  Builder<Student>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            if (ctype_digit($term)) {
                $query->where('lrn', 'like', $term.'%');

                return;
            }

            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

            foreach (preg_split('/[\s,]+/', $escaped, flags: PREG_SPLIT_NO_EMPTY) as $word) {
                $query->where(function (Builder $query) use ($word): void {
                    $query->where('first_name', 'like', "%{$word}%")
                        ->orWhere('middle_name', 'like', "%{$word}%")
                        ->orWhere('last_name', 'like', "%{$word}%");
                });
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }
}
