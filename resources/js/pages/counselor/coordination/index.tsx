import { buttonClasses, Card, EmptyState, FlashStatus, formatDateTime, Pagination } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Paginated, Signature, StudentSummary } from '@/types';
import { Link } from '@inertiajs/react';

interface CoordinationRow extends Signature {
    id: number;
    coordinatedAt: string;
    contactName: string;
    relationship: string;
    method: string;
    purpose: string;
    outcome: string | null;
    student: StudentSummary | null;
    caseId: number | null;
    caseReference: string | null;
}

export default function CoordinationIndex({ coordinations }: { coordinations: Paginated<CoordinationRow> }) {
    return (
        <CounselorLayout title="Parent / guardian coordination">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Records · Parent / guardian</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Parent / guardian coordination</h1>
                    <p className="text-ink-2">Parents and guardians don't have accounts. Record each contact the office makes with them.</p>
                </div>
                <Link href="/counselor/coordination/create" className={buttonClasses.primary}>
                    Record a contact
                </Link>
            </div>

            <FlashStatus />

            <Card title={`${coordinations.total} ${coordinations.total === 1 ? 'contact' : 'contacts'}`}>
                {coordinations.data.length === 0 ? (
                    <EmptyState code="PG" heading="No contacts recorded yet." />
                ) : (
                    <ul>
                        {coordinations.data.map((item) => (
                            <li key={item.id} className="grid grid-cols-[110px_minmax(0,1fr)] gap-x-4 gap-y-1 border-b border-rule px-5 py-4 last:border-b-0">
                                <span className="font-mono text-[13px] text-ink-2">{formatDateTime(item.coordinatedAt)}</span>
                                <div className="flex min-w-0 flex-col gap-1">
                                    <span className="font-semibold">
                                        {item.method} with {item.contactName} <span className="font-normal text-ink-2">({item.relationship})</span>
                                    </span>
                                    <span className="text-[13px] text-ink-2">
                                        {item.student && (
                                            <Link href={`/counselor/students/${item.student.id}`} className="font-semibold text-green hover:text-ink">
                                                {item.student.name}
                                            </Link>
                                        )}
                                        {item.caseReference && (
                                            <>
                                                {' · '}
                                                <Link href={`/counselor/cases/${item.caseId}`} className="font-mono text-green hover:text-ink">
                                                    {item.caseReference}
                                                </Link>
                                            </>
                                        )}
                                    </span>
                                    <p className="text-sm">{item.purpose}</p>
                                    {item.outcome && <p className="text-sm whitespace-pre-line text-ink-2">{item.outcome}</p>}
                                    <span className="text-[13px] text-ink-2">— signed {item.createdBy ?? '—'}</span>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
                <Pagination page={coordinations} noun={['Newer', 'Older']} />
            </Card>
        </CounselorLayout>
    );
}
