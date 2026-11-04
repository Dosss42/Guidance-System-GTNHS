import { buttonClasses, Card, FlashStatus, formatDate, formatDateTime, SelectField, Stamp, statusTone, TextAreaField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, Signature, StudentSummary } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface Referral extends Signature {
    id: number;
    reference: string;
    receivedOn: string;
    referringTeacher: string;
    teacherRole: string | null;
    receivedVia: string;
    category: string;
    priority: string;
    priorityLabel: string;
    reason: string;
    status: string;
    statusLabel: string;
    reviewOutcome: string | null;
    student: StudentSummary;
    caseId: number | null;
    caseReference: string | null;
    caseStatus: string | null;
}

export default function ShowReferral({ referral, statuses }: { referral: Referral; statuses: Option[] }) {
    return (
        <CounselorLayout title={`Referral ${referral.reference}`}>
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/referrals" className="text-green hover:text-ink">
                    Referrals
                </Link>{' '}
                <span aria-hidden="true">/</span> {referral.reference}
            </nav>

            <FlashStatus />

            <div className="flex flex-wrap items-start gap-6">
                <section aria-label="Referral slip" className="ruled-card relative min-w-0 flex-[3_1_480px] rounded border border-rule bg-surface bg-[position:0_18px] py-5 pr-6 pl-12 leading-8">
                    <div aria-hidden="true" className="absolute inset-y-0 left-7 w-[1.5px] bg-margin/70" />
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <h1 className="font-mono text-sm font-semibold tracking-[0.08em]">REFERRAL · {referral.reference}</h1>
                        <Stamp tone={statusTone(referral.status)}>{referral.statusLabel}</Stamp>
                    </div>
                    <dl className="mt-2 grid grid-cols-[140px_minmax(0,1fr)] gap-x-4 text-[15px]">
                        <dt className="text-ink-2">Student</dt>
                        <dd>
                            <Link href={`/counselor/students/${referral.student.id}`} className="font-semibold text-green hover:text-ink">
                                {referral.student.name}
                            </Link>{' '}
                            <span className="text-ink-2">· {referral.student.detail}</span>
                        </dd>
                        <dt className="text-ink-2">From</dt>
                        <dd>
                            {referral.referringTeacher}
                            {referral.teacherRole && <span className="text-ink-2"> · {referral.teacherRole}</span>}
                        </dd>
                        <dt className="text-ink-2">Received</dt>
                        <dd>
                            {formatDate(referral.receivedOn)} · {referral.receivedVia}
                        </dd>
                        <dt className="text-ink-2">Category</dt>
                        <dd>{referral.category}</dd>
                        <dt className="text-ink-2">How soon</dt>
                        <dd className={referral.priority === 'today' ? 'font-semibold text-brick' : ''}>{referral.priorityLabel}</dd>
                        <dt className="text-ink-2">Reason</dt>
                        <dd className="whitespace-pre-line">{referral.reason}</dd>
                        {referral.reviewOutcome && (
                            <>
                                <dt className="text-ink-2">Outcome</dt>
                                <dd className="whitespace-pre-line">{referral.reviewOutcome}</dd>
                            </>
                        )}
                    </dl>
                    <p className="mt-2 text-[13px] text-ink-2">
                        — encoded by {referral.createdBy ?? '—'}, {formatDateTime(referral.createdAt)}
                        {referral.updatedBy && referral.updatedAt !== referral.createdAt && ` · last updated by ${referral.updatedBy}`}
                    </p>
                </section>

                <aside className="flex min-w-0 flex-[2_1_300px] flex-col gap-6">
                    <Card title="Guidance case">
                        <div className="flex flex-col gap-3 px-5 py-4 text-sm">
                            {referral.caseId ? (
                                <p>
                                    Case{' '}
                                    <Link href={`/counselor/cases/${referral.caseId}`} className="font-mono font-semibold text-green hover:text-ink">
                                        {referral.caseReference}
                                    </Link>{' '}
                                    was opened from this referral. <span className="text-ink-2">({referral.caseStatus})</span>
                                </p>
                            ) : (
                                <>
                                    <p className="text-ink-2">Does this referral need further guidance work? Open a case for it.</p>
                                    <Link href={`/counselor/cases/create?referral=${referral.id}`} className={buttonClasses.primary}>
                                        Open a case from this referral
                                    </Link>
                                </>
                            )}
                        </div>
                    </Card>

                    <Card title="Review">
                        <Form action={`/counselor/referrals/${referral.id}`} method="patch" options={{ preserveScroll: true }} className="flex flex-col gap-4 px-5 py-4">
                            {({ errors, processing }) => (
                                <>
                                    <SelectField label="Status" name="status" defaultValue={referral.status} required error={errors.status}>
                                        {statuses.map((status) => (
                                            <option key={status.value} value={status.value}>
                                                {status.label}
                                            </option>
                                        ))}
                                    </SelectField>
                                    <TextAreaField
                                        label="Review outcome"
                                        name="review_outcome"
                                        defaultValue={referral.reviewOutcome ?? ''}
                                        rows={3}
                                        error={errors.review_outcome}
                                        hint="Required when closing or referring to other support."
                                    />
                                    <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                        Save review
                                    </button>
                                </>
                            )}
                        </Form>
                    </Card>
                </aside>
            </div>
        </CounselorLayout>
    );
}
