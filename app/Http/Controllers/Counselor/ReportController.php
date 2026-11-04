<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\GuidanceCase;
use App\Models\SchoolYear;
use App\Services\AuditLogger;
use App\Services\GuidanceReport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Summary statistics for a school year (default: the current one) or custom dates.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', GuidanceCase::class);

        $report = $this->resolveReport($request);

        return Inertia::render('counselor/reports/index', [
            'report' => $report->toArray(),
            'period' => [
                'label' => $report->periodLabel(),
                'from' => $report->from->toDateString(),
                'to' => $report->to->toDateString(),
                'year' => $report->schoolYear ? (string) $report->schoolYear->id : 'custom',
            ],
            'schoolYears' => SchoolYear::orderByDesc('start_date')->get()
                ->map(fn (SchoolYear $year): array => ['id' => $year->id, 'name' => $year->name, 'isCurrent' => $year->is_current]),
        ]);
    }

    /**
     * Download the same summary as a CSV spreadsheet. Every export is logged.
     */
    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('viewAny', GuidanceCase::class);

        $report = $this->resolveReport($request);
        $data = $report->toArray();
        $generatedBy = $request->user()->name;

        $audit->record(AuditAction::ReportExported, 'reports', description: "Summary report · {$report->periodLabel()}");

        $filename = sprintf('gtnhs-guidance-summary-%s-to-%s.csv', $report->from->toDateString(), $report->to->toDateString());

        return response()->streamDownload(function () use ($report, $data, $generatedBy): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\u{FEFF}");

            $row = fn (array $cells) => fputcsv($out, array_map(self::safeCell(...), $cells), escape: '');

            $row(['GTNHS Guidance Office — Summary report']);
            $row(['Period', $report->periodLabel()]);
            $row(['Generated', now()->format('Y-m-d H:i'), 'by', $generatedBy]);
            $row(['Counts only. No student names or counseling notes are included.']);
            $row([]);
            $row(['Section', 'Item', 'Count']);

            foreach (self::totalLabels() as $key => $label) {
                $row(['Totals', $label, $data['totals'][$key] ?? '']);
            }

            foreach ($data['breakdowns'] as $breakdown) {
                foreach ($breakdown['rows'] as $item) {
                    $row([$breakdown['title'], $item['label'], $item['count']]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function totalLabels(): array
    {
        return [
            'referralsReceived' => 'Referrals received',
            'casesOpened' => 'Cases opened',
            'casesClosed' => 'Cases closed',
            'activeCasesNow' => 'Active cases today',
            'sessionsHeld' => 'Counseling sessions held',
            'appointments' => 'Appointments',
            'appointmentsCompleted' => 'Appointments completed',
            'appointmentsNoShow' => 'Appointments missed (no-show)',
            'followUpsDue' => 'Follow-ups due',
            'followUpsCompleted' => 'Follow-ups completed',
            'followUpCompletionRate' => 'Follow-up completion rate (%)',
            'parentContacts' => 'Parent / guardian contacts',
            'documentsUploaded' => 'Documents uploaded',
        ];
    }

    /**
     * `?year=<id>` uses that school year; `?year=custom&from=&to=` uses dates.
     * Without either, the current school year, or the last 30 days.
     */
    private function resolveReport(Request $request): GuidanceReport
    {
        if ($request->query('year') === 'custom') {
            $validated = $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
            ]);

            return new GuidanceReport(Carbon::parse($validated['from'])->startOfDay(), Carbon::parse($validated['to'])->startOfDay());
        }

        $schoolYear = $request->integer('year') ? SchoolYear::find($request->integer('year')) : SchoolYear::current();

        if ($schoolYear !== null) {
            return new GuidanceReport($schoolYear->start_date->copy(), $schoolYear->end_date->copy(), $schoolYear);
        }

        return new GuidanceReport(today()->subDays(29), today());
    }

    /**
     * Stop spreadsheet apps from treating a cell as a formula.
     */
    private static function safeCell(mixed $value): string
    {
        $text = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $text) ? "'{$text}" : $text;
    }
}
