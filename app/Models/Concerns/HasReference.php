<?php

namespace App\Models\Concerns;

/**
 * Gives a record a human-readable reference such as "GC-2026-0042".
 * The number comes from the row id, so references never collide.
 *
 * The using model defines `protected const REFERENCE_PREFIX = 'GC';`.
 */
trait HasReference
{
    public static function bootHasReference(): void
    {
        static::created(function (self $model): void {
            $model->reference = sprintf('%s-%d-%04d', static::REFERENCE_PREFIX, now()->year, $model->getKey());
            $model->saveQuietly();
        });
    }
}
