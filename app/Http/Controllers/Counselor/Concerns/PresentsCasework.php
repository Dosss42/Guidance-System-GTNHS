<?php

namespace App\Http\Controllers\Counselor\Concerns;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

/**
 * Small, consistent shapes for casework pages. Never include counseling
 * note contents here — notes are only released by the unseal endpoint.
 */
trait PresentsCasework
{
    /**
     * Eager-load this with `student.currentEnrollment.section`.
     *
     * @return array{id: int, name: string, detail: string}
     */
    protected static function studentSummary(Student $student): array
    {
        $section = $student->currentEnrollment?->section;

        return [
            'id' => $student->id,
            'name' => $student->filingName(),
            'detail' => $section ? "Grade {$section->grade_level} · {$section->name}" : 'Not enrolled this school year',
        ];
    }

    /**
     * "— signed J. Ramos" details. Eager-load `creator:id,name` and `updater:id,name`.
     *
     * @return array{createdBy: ?string, updatedBy: ?string, createdAt: ?string, updatedAt: ?string}
     */
    protected static function signature(Model $record): array
    {
        return [
            'createdBy' => $record->creator?->name,
            'updatedBy' => $record->updater?->name,
            'createdAt' => $record->created_at?->toIso8601String(),
            'updatedAt' => $record->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Field names that changed, for audit descriptions. Status changes show
     * their labels ("status: Received → Under review"); other values never appear.
     *
     * @param  array<string, mixed>  $original
     */
    protected static function describeChanges(Model $record, array $original): string
    {
        return collect($record->getChanges())
            ->except(['updated_at', 'updated_by'])
            ->keys()
            ->map(function (string $field) use ($record, $original): string {
                if ($field !== 'status') {
                    return $field;
                }

                $before = $original['status'] ?? null;
                $after = $record->status;

                return 'status: '.(is_object($before) && method_exists($before, 'label') ? $before->label() : (string) $before)
                    .' → '.(is_object($after) && method_exists($after, 'label') ? $after->label() : (string) $after);
            })
            ->implode(', ');
    }
}
