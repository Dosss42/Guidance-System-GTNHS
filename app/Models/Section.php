<?php

namespace App\Models;

use App\Models\Concerns\RecordsAuthors;
use Database\Factories\SectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['grade_level', 'name'])]
class Section extends Model
{
    /** @use HasFactory<SectionFactory> */
    use HasFactory, RecordsAuthors;

    /**
     * Junior and senior high school grades served by GTNHS.
     *
     * @var list<int>
     */
    public const GRADE_LEVELS = [7, 8, 9, 10, 11, 12];

    /**
     * "Grade 10 · Sampaguita"
     */
    public function label(): string
    {
        return "Grade {$this->grade_level} · {$this->name}";
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
            'grade_level' => 'integer',
        ];
    }
}
