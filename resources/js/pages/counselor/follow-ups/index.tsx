import { FollowUpActions } from '@/components/follow-up-actions';
import { Card, EmptyState, FilterTabs, FlashStatus, formatDate, Pagination, Stamp, statusTone } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Paginated, Signature, StudentSummary } from '@/types';
import { Link } from '@inertiajs/react';

interface FollowUpRow extends Signature {
    id: number;
    dueOn: string;
    purpose: string;
    status: string;
    statusLabel: string;
    isOverdue: boolean;
    outcome: string | null;
    caseId: number;
    caseReference: string;
    student: StudentSummary;
}

const views = [
    { value: 'pending', label: 'To do' },
    { value: 'done', label: 'Done or moved' },
];

export default function FollowUpsIndex({ followUps, view, today }: { followUps: Paginated<FollowUpRow>; view: string; today: string }) {
    return (
        <CounselorLayout title="Follow-ups">
            <div className="flex flex-col gap-0.5">
                <span className="mono-label">Desk · Follow-ups</span>
                <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Follow-ups</h1>
                <p className="text-ink-2">Every follow-up across all cases. Schedule new ones from a case or when recording a session.</p>
            </div>

            <FlashStatus />

            <Card
                title={`${followUps.total} ${followUps.total === 1 ? 'follow-up' : 'follow-ups'}`}
                aside={<FilterTabs label="Show" options={views} current={view} href={(value) => `/counselor/follow-ups?view=${value}`} />}
            >
                {followUps.data.length === 0 ? (
                    <EmptyState code="FU" heading={view === 'pending' ? 'Nothing to follow up.' : 'No completed follow-ups yet.'} />
                ) : (
                    <ul>
                        {followUps.data.map((followUp) => (
                            <li key={followUp.id} className="grid grid-cols-[96px_minmax(0,1fr)] gap-x-4 gap-y-2 border-b border-rule px-5 py-4 last:border-b-0">
                                <span className={`font-mono text-[13px] ${followUp.isOverdue ? 'font-semibold text-brick' : 'text-ink-2'}`}>{formatDate(followUp.dueOn)}</span>
                                <div className="flex min-w-0 flex-col gap-2">
                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span className="font-semibold">{followUp.purpose}</span>
                                        <Stamp tone={followUp.isOverdue ? 'brick' : statusTone(followUp.status)}>{followUp.statusLabel}</Stamp>
                                    </div>
                                    <p className="text-[13px] text-ink-2">
                                        <Link href={`/counselor/cases/${followUp.caseId}`} className="font-mono font-semibold text-green hover:text-ink">
                                            {followUp.caseReference}
                                        </Link>{' '}
                                        · {followUp.student.name} · {followUp.student.detail}
                                    </p>
                                    {followUp.outcome && <p className="text-sm whitespace-pre-line">{followUp.outcome}</p>}
                                    {followUp.status === 'pending' && <FollowUpActions followUpId={followUp.id} today={today} />}
                                    <span className="text-[13px] text-ink-2">— signed {followUp.updatedBy ?? followUp.createdBy ?? '—'}</span>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination page={followUps} />
            </Card>
        </CounselorLayout>
    );
}
