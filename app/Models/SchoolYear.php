<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthors;
use Database\Factories\SchoolYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'start_date', 'end_date'])]
class SchoolYear extends Model
{
    /** @use HasFactory<SchoolYearFactory> */
    use HasFactory, RecordsAuthors;

    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Make this the only current school year.
     */
    public function markAsCurrent(): void
    {
        DB::transaction(function (): void {
            static::where('is_current', true)->whereKeyNot($this->getKey())->update(['is_current' => false]);

            $this->forceFill(['is_current' => true])->save();
        });
    }

    /**
     * @return HasMany<StudentEnrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }
}
