import { buttonClasses, Card, EmptyState, FilterTabs, FlashStatus, formatDate, Pagination, Stamp, statusTone } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, Paginated, Signature, StudentSummary } from '@/types';
import { Link } from '@inertiajs/react';

interface ReferralRow extends Signature {
    id: number;
    reference: string;
    receivedOn: string;
    referringTeacher: string;
    category: string;
    status: string;
    statusLabel: string;
    priority: string;
    student: StudentSummary;
    caseId: number | null;
    caseReference: string | null;
}

export default function ReferralsIndex({ referrals, status, statuses }: { referrals: Paginated<ReferralRow>; status: string; statuses: Option[] }) {
    const filters = [{ value: 'open', label: 'Not closed' }, ...statuses, { value: 'all', label: 'All' }];

    return (
        <CounselorLayout title="Referrals">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Casework · Referrals</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Teacher referrals</h1>
                    <p className="text-ink-2">Teachers don't sign in. Encode each referral you receive through the usual school process.</p>
                </div>
                <Link href="/counselor/referrals/create" className={buttonClasses.primary}>
                    Encode referral
                </Link>
            </div>

            <FlashStatus />

            <Card title={`${referrals.total} ${referrals.total === 1 ? 'referral' : 'referrals'}`}>
                <div className="border-b border-rule px-4 py-2.5">
                    <FilterTabs label="Filter by status" options={filters} current={status} href={(value) => `/counselor/referrals?status=${value}`} />
                </div>
                {referrals.data.length === 0 ? (
                    <EmptyState code="RF" heading="No referrals here." body="Encoded referrals appear in this list and in every counselor's “Needs attention” panel." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[760px] border-collapse text-sm">
                            <thead>
                                <tr className="text-left">
                                    {['Reference', 'Student', 'From', 'Category', 'Received', 'Status'].map((heading) => (
                                        <th key={heading} scope="col" className="mono-label border-b border-rule px-3 py-2.5 first:pl-5 last:pr-5">
                                            {heading}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {referrals.data.map((referral) => (
                                    <tr key={referral.id} className="border-b border-rule last:border-b-0 hover:bg-sunk">
                                        <td className="px-3 py-3 pl-5">
                                            <Link href={`/counselor/referrals/${referral.id}`} className="font-mono text-[13px] font-semibold text-green hover:text-ink">
                                                {referral.reference}
                                            </Link>
                                        </td>
                                        <td className="px-3 py-3">
                                            <span className="font-semibold">{referral.student.name}</span>
                                            <span className="block text-xs text-ink-2">{referral.student.detail}</span>
                                        </td>
                                        <td className="px-3 py-3">{referral.referringTeacher}</td>
                                        <td className="px-3 py-3">
                                            {referral.category}
                                            {referral.priority === 'today' && <span className="ml-2 font-mono text-[11px] font-semibold text-brick uppercase">Today</span>}
                                        </td>
                                        <td className="px-3 py-3 font-mono text-[13px] whitespace-nowrap">{formatDate(referral.receivedOn)}</td>
                                        <td className="px-3 py-3 pr-5">
                                            <Stamp tone={statusTone(referral.status)}>{referral.statusLabel}</Stamp>
                                            {referral.caseReference && <span className="mt-1 block font-mono text-xs text-ink-2">{referral.caseReference}</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <Pagination page={referrals} />
            </Card>
        </CounselorLayout>
    );
}
