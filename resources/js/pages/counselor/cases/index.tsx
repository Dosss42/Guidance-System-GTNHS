import { buttonClasses, Card, EmptyState, FilterTabs, FlashStatus, formatDate, Pagination, Stamp, statusTone } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, Paginated, Signature, StudentSummary } from '@/types';
import { Link } from '@inertiajs/react';

interface CaseRow extends Signature {
    id: number;
    reference: string;
    categoryLabel: string;
    status: string;
    statusLabel: string;
    openedOn: string;
    student: StudentSummary;
    sessionCount: number;
    nextFollowUpOn: string | null;
    followUpOverdue: boolean;
}

export default function CasesIndex({ cases, status, statuses }: { cases: Paginated<CaseRow>; status: string; statuses: Option[] }) {
    const filters = [{ value: 'active', label: 'Active' }, ...statuses, { value: 'all', label: 'All' }];

    return (
        <CounselorLayout title="Guidance cases">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Casework · Guidance cases</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Guidance cases</h1>
                    <p className="text-ink-2">All counselors share every case. A case records a support process; it is never a diagnosis or a disciplinary finding.</p>
                </div>
                <Link href="/counselor/cases/create" className={buttonClasses.primary}>
                    Open a case
                </Link>
            </div>

            <FlashStatus />

            <Card title={`${cases.total} ${cases.total === 1 ? 'case' : 'cases'}`}>
                <div className="border-b border-rule px-4 py-2.5">
                    <FilterTabs label="Filter by status" options={filters} current={status} href={(value) => `/counselor/cases?status=${value}`} />
                </div>
                {cases.data.length === 0 ? (
                    <EmptyState code="GC" heading="No cases here." body="Open a case from a referral or directly from a student's record." />
                ) : (
                    <ul>
                        {cases.data.map((item) => (
                            <li key={item.id} className="border-b border-rule last:border-b-0">
                                <Link href={`/counselor/cases/${item.id}`} className="flex flex-wrap items-center gap-x-5 gap-y-1.5 px-5 py-3.5 text-ink hover:bg-sunk">
                                    <span className="w-32 shrink-0 font-mono text-[13px] font-semibold">{item.reference}</span>
                                    <span className="min-w-[200px] flex-1">
                                        <span className="font-semibold">{item.student.name}</span>
                                        <span className="block text-[13px] text-ink-2">
                                            {item.categoryLabel} · opened {formatDate(item.openedOn)} · {item.sessionCount} {item.sessionCount === 1 ? 'session' : 'sessions'}
                                        </span>
                                    </span>
                                    {item.nextFollowUpOn && (
                                        <span className={`font-mono text-xs ${item.followUpOverdue ? 'font-semibold text-brick' : 'text-ink-2'}`}>
                                            {item.followUpOverdue ? 'Follow-up overdue · ' : 'Next follow-up · '}
                                            {formatDate(item.nextFollowUpOn)}
                                        </span>
                                    )}
                                    <Stamp tone={statusTone(item.status)}>{item.statusLabel}</Stamp>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination page={cases} />
            </Card>
        </CounselorLayout>
    );
}
