<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StudentLookupController extends Controller
{
    use PresentsCasework;

    /**
     * Search students for the student picker on casework forms. LRNs stay masked.
     */
    public function __invoke(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        $term = $request->string('q')->trim()->toString();

        if (mb_strlen($term) < 2) {
            return response()->json(['students' => []]);
        }

        $students = Student::search($term)
            ->with('currentEnrollment.section')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(8)
            ->get()
            ->map(fn (Student $student): array => [
                ...self::studentSummary($student),
                'maskedLrn' => $student->maskedLrn(),
            ]);

        return response()->json(['students' => $students]);
    }
}
