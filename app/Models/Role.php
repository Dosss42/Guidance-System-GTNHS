<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * Find the role row for a slug, creating it when the roles table has not been seeded.
     */
    public static function forSlug(RoleSlug $slug): self
    {
        return static::firstOrCreate(['slug' => $slug], ['name' => $slug->label()]);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slug' => RoleSlug::class,
        ];
    }
}
