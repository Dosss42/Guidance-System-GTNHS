import { buttonClasses, Card, SelectField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

interface Breakdown {
    key: string;
    title: string;
    rows: { label: string; count: number }[];
}

interface Props {
    report: {
        totals: Record<string, number | null>;
        breakdowns: Breakdown[];
    };
    period: { label: string; from: string; to: string; year: string };
    schoolYears: { id: number; name: string; isCurrent: boolean }[];
}

const headline: { key: string; label: string }[] = [
    { key: 'referralsReceived', label: 'referrals received' },
    { key: 'casesOpened', label: 'cases opened' },
    { key: 'casesClosed', label: 'cases closed' },
    { key: 'sessionsHeld', label: 'counseling sessions' },
    { key: 'appointments', label: 'appointments' },
    { key: 'parentContacts', label: 'parent / guardian contacts' },
];

export default function ReportsIndex({ report, period, schoolYears }: Props) {
    const [yearChoice, setYearChoice] = useState(period.year);
    const query = new URLSearchParams(period.year === 'custom' ? { year: 'custom', from: period.from, to: period.to } : { year: period.year }).toString();
    const rate = report.totals.followUpCompletionRate;

    return (
        <CounselorLayout title="Reports">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Casework · Reports</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Summary report</h1>
                    <p className="text-ink-2">{period.label}</p>
                </div>
                <div className="flex flex-wrap gap-2 print:hidden">
                    <button type="button" className={buttonClasses.quiet} onClick={() => window.print()}>
                        Print or save as PDF
                    </button>
                    <a href={`/counselor/reports/export?${query}`} className={buttonClasses.primary}>
                        Export CSV
                    </a>
                </div>
            </div>

            <p className="max-w-3xl rounded border border-rule bg-surface px-4 py-3 text-sm text-ink-2">
                Counts only. Reports never include student names, case details or counseling notes. Exports are recorded in the activity log. Small counts can still
                point to a student, so share exports only with authorized staff.
            </p>

            <Form action="/counselor/reports" method="get" className="flex flex-wrap items-end gap-3 rounded border border-rule bg-surface p-4 print:hidden">
                <div className="min-w-[200px] flex-[1_1_220px]">
                    <SelectField label="Period" name="year" value={yearChoice} onChange={(event) => setYearChoice(event.target.value)}>
                        {schoolYears.map((year) => (
                            <option key={year.id} value={year.id}>
                                SY {year.name}
                                {year.isCurrent ? ' (current)' : ''}
                            </option>
                        ))}
                        <option value="custom">Custom dates</option>
                    </SelectField>
                </div>
                {yearChoice === 'custom' && (
                    <>
                        <div className="min-w-[160px] flex-[1_1_160px]">
                            <TextField label="From" name="from" type="date" defaultValue={period.from} required />
                        </div>
                        <div className="min-w-[160px] flex-[1_1_160px]">
                            <TextField label="To" name="to" type="date" defaultValue={period.to} required />
                        </div>
                    </>
                )}
                <button type="submit" className={buttonClasses.primary}>
                    Show report
                </button>
            </Form>

            <section aria-label="Headline counts" className="grid grid-cols-[repeat(auto-fit,minmax(160px,1fr))] rounded border border-rule bg-surface">
                {headline.map((tile) => (
                    <div key={tile.key} className="flex flex-col gap-0.5 border-r border-b border-rule px-5 py-4">
                        <span className="font-mono text-[30px] leading-tight font-medium">{report.totals[tile.key] ?? 0}</span>
                        <span className="text-[13px] text-ink-2">{tile.label}</span>
                    </div>
                ))}
            </section>

            <div className="grid grid-cols-[repeat(auto-fit,minmax(260px,1fr))] gap-6">
                <Card title="Follow-ups">
                    <div className="flex flex-col gap-1 px-5 py-4">
                        <span className="font-mono text-[30px] leading-tight font-medium">{rate === null ? '—' : `${rate}%`}</span>
                        <span className="text-[13px] text-ink-2">
                            completed: {report.totals.followUpsCompleted ?? 0} of {report.totals.followUpsDue ?? 0} due in this period
                        </span>
                    </div>
                </Card>
                <Card title="Appointments">
                    <dl className="grid grid-cols-[minmax(0,1fr)_auto] gap-x-4 gap-y-1 px-5 py-4 text-sm">
                        <dt className="text-ink-2">Completed</dt>
                        <dd className="text-right font-mono">{report.totals.appointmentsCompleted ?? 0}</dd>
                        <dt className="text-ink-2">Missed (no-show)</dt>
                        <dd className="text-right font-mono">{report.totals.appointmentsNoShow ?? 0}</dd>
                        <dt className="text-ink-2">Active cases today</dt>
                        <dd className="text-right font-mono">{report.totals.activeCasesNow ?? 0}</dd>
                        <dt className="text-ink-2">Documents uploaded</dt>
                        <dd className="text-right font-mono">{report.totals.documentsUploaded ?? 0}</dd>
                    </dl>
                </Card>
            </div>

            <div className="grid grid-cols-[repeat(auto-fit,minmax(340px,1fr))] gap-6">
                {report.breakdowns.map((breakdown) => (
                    <BarTable key={breakdown.key} breakdown={breakdown} />
                ))}
            </div>
        </CounselorLayout>
    );
}

/**
 * One series, one hue: every row carries its own label and number, so the
 * bar is never the only way to read the value. The table is the table view.
 */
function BarTable({ breakdown }: { breakdown: Breakdown }) {
    const max = Math.max(1, ...breakdown.rows.map((row) => row.count));
    const total = breakdown.rows.reduce((sum, row) => sum + row.count, 0);

    return (
        <Card title={breakdown.title} aside={<span className="font-mono text-[13px] text-ink-2">Total {total}</span>} className="break-inside-avoid">
            <table className="w-full border-collapse text-sm">
                <caption className="sr-only">
                    {breakdown.title}, total {total}
                </caption>
                <thead className="sr-only">
                    <tr>
                        <th scope="col">Item</th>
                        <th scope="col">Share</th>
                        <th scope="col">Count</th>
                    </tr>
                </thead>
                <tbody>
                    {breakdown.rows.map((row) => (
                        <tr key={row.label} className="border-b border-rule last:border-b-0">
                            <th scope="row" className="w-[42%] px-5 py-2 text-left font-normal">
                                {row.label}
                            </th>
                            <td className="py-2" aria-hidden="true">
                                <div
                                    className="h-3.5 rounded-r-[4px] bg-green print:border print:border-ink"
                                    style={{ width: row.count === 0 ? '2px' : `${(row.count / max) * 100}%`, opacity: row.count === 0 ? 0.3 : 1 }}
                                    title={`${row.label}: ${row.count}`}
                                />
                            </td>
                            <td className="w-14 px-5 py-2 text-right font-mono">{row.count}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </Card>
    );
}
