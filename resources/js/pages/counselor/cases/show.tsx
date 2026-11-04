import { DocumentList } from '@/components/document-list';
import { FollowUpActions } from '@/components/follow-up-actions';
import { SealedNotes } from '@/components/sealed-notes';
import { buttonClasses, Card, FlashStatus, formatDate, formatDateTime, SelectField, Stamp, statusTone, TextAreaField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { DocumentFile, Option, Signature, StudentSummary } from '@/types';
import { Form, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface GuidanceCase extends Signature {
    id: number;
    reference: string;
    category: string;
    categoryLabel: string;
    status: string;
    statusLabel: string;
    openedOn: string;
    summary: string | null;
    closedOn: string | null;
    closureSummary: string | null;
    student: StudentSummary;
    referral: { id: number; reference: string } | null;
    nextFollowUp: { dueOn: string; isOverdue: boolean } | null;
    signers: { name: string; entries: number }[];
}

interface HistoryEntry {
    key: string;
    kind: 'session' | 'follow_up' | 'coordination' | 'appointment' | 'opened';
    id: number;
    at: string;
    title: string;
    detail?: string | null;
    status?: string;
    statusLabel?: string;
    isOverdue?: boolean;
    outcome?: string | null;
    referralId?: number | null;
    signedBy: string | null;
}

interface Props {
    guidanceCase: GuidanceCase;
    history: HistoryEntry[];
    documents: DocumentFile[];
    statuses: Option[];
    categories: Option[];
    sessionTypes: Option[];
    now: string;
    today: string;
}

type Panel = 'session' | 'follow-up' | null;

const dotStyles: Record<HistoryEntry['kind'], string> = {
    session: 'border-brick',
    follow_up: 'border-dashed border-ink',
    coordination: 'border-ink',
    appointment: 'border-green',
    opened: 'border-ink bg-ink',
};

export default function ShowCase({ guidanceCase, history, documents, statuses, categories, sessionTypes, now, today }: Props) {
    const { auth } = usePage().props;
    const [panel, setPanel] = useState<Panel>(null);
    const [closing, setClosing] = useState(guidanceCase.status === 'closed');
    const isClosed = guidanceCase.status === 'closed';
    const caseUrl = `/counselor/cases/${guidanceCase.id}`;

    return (
        <CounselorLayout title={`Case ${guidanceCase.reference}`}>
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/cases" className="text-green hover:text-ink">
                    Guidance cases
                </Link>{' '}
                <span aria-hidden="true">/</span> {guidanceCase.reference}
            </nav>

            <FlashStatus />

            <div className="flex flex-col">
                <div className="flex items-end">
                    <span className="rounded-t-[6px] border-t-[3px] border-sprout bg-folder px-[18px] pt-2.5 pb-2 font-mono text-sm font-semibold tracking-[0.06em] text-folder-ink">
                        {guidanceCase.reference}
                    </span>
                </div>
                <section aria-label="Case folder" className="rounded-tr-[6px] rounded-b-[6px] bg-folder px-[22px] pt-5 pb-[22px] text-folder-ink">
                    <div className="flex flex-wrap items-start justify-between gap-x-8 gap-y-4">
                        <div className="flex min-w-0 flex-col gap-1">
                            <h1 className="font-display text-[38px] leading-[1.1] font-medium">{guidanceCase.student.name}</h1>
                            <p className="text-sm">
                                {guidanceCase.student.detail} ·{' '}
                                <Link href={`/counselor/students/${guidanceCase.student.id}`} className="font-semibold text-folder-ink underline underline-offset-2">
                                    Open student record
                                </Link>
                            </p>
                        </div>
                        {!isClosed && (
                            <button type="button" className={buttonClasses.primary} onClick={() => setPanel('session')}>
                                Record session
                            </button>
                        )}
                    </div>
                    <dl className="mt-[18px] grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-x-6 gap-y-3 border-t border-dashed border-current pt-4">
                        <div>
                            <dt className="font-mono text-[11px] tracking-[0.08em] opacity-85">STATUS</dt>
                            <dd className="mt-1">
                                <span className="bg-surface">
                                    <Stamp tone={statusTone(guidanceCase.status)}>{guidanceCase.statusLabel}</Stamp>
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt className="font-mono text-[11px] tracking-[0.08em] opacity-85">CATEGORY</dt>
                            <dd className="mt-0.5 font-semibold">{guidanceCase.categoryLabel}</dd>
                        </div>
                        <div>
                            <dt className="font-mono text-[11px] tracking-[0.08em] opacity-85">OPENED</dt>
                            <dd className="mt-0.5 font-semibold">
                                {formatDate(guidanceCase.openedOn)}
                                {guidanceCase.createdBy && ` · ${guidanceCase.createdBy}`}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-mono text-[11px] tracking-[0.08em] opacity-85">SOURCE</dt>
                            <dd className="mt-0.5 font-semibold">
                                {guidanceCase.referral ? (
                                    <Link href={`/counselor/referrals/${guidanceCase.referral.id}`} className="text-folder-ink underline underline-offset-2">
                                        Referral {guidanceCase.referral.reference}
                                    </Link>
                                ) : (
                                    'Opened directly'
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="font-mono text-[11px] tracking-[0.08em] opacity-85">NEXT</dt>
                            <dd className={`mt-0.5 font-semibold ${guidanceCase.nextFollowUp?.isOverdue ? 'text-brick' : ''}`}>
                                {isClosed
                                    ? `Closed ${formatDate(guidanceCase.closedOn)}`
                                    : guidanceCase.nextFollowUp
                                      ? `Follow-up ${guidanceCase.nextFollowUp.isOverdue ? 'overdue' : 'due'} (${formatDate(guidanceCase.nextFollowUp.dueOn)})`
                                      : 'No follow-up scheduled'}
                            </dd>
                        </div>
                    </dl>
                    {guidanceCase.summary && <p className="mt-4 max-w-3xl text-[15px] whitespace-pre-line">{guidanceCase.summary}</p>}
                </section>
            </div>

            <div className="flex flex-wrap items-start gap-6">
                <div className="flex min-w-0 flex-[3_1_520px] flex-col gap-6">
                    {panel === 'session' && !isClosed && (
                        <Card title="Record a counseling session">
                            <Form action={`${caseUrl}/sessions`} method="post" options={{ preserveScroll: true }} onSuccess={() => setPanel(null)} className="flex flex-col gap-5 p-5">
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
                                            <TextField label="Session date and time" name="session_at" type="datetime-local" max={now} defaultValue={now} required error={errors.session_at} />
                                            <SelectField label="Type" name="session_type" defaultValue="individual" required error={errors.session_type}>
                                                {sessionTypes.map((type) => (
                                                    <option key={type.value} value={type.value}>
                                                        {type.label}
                                                    </option>
                                                ))}
                                            </SelectField>
                                        </div>
                                        <TextAreaField
                                            label="Confidential session notes"
                                            name="confidential_notes"
                                            rows={7}
                                            required
                                            maxLength={10000}
                                            error={errors.confidential_notes}
                                            hint="Stored encrypted. After saving, the notes stay sealed until a counselor unseals them, and each unseal is logged."
                                        />
                                        <TextAreaField label="Next steps" name="next_steps" rows={2} maxLength={2000} error={errors.next_steps} />
                                        <fieldset className="grid grid-cols-[minmax(160px,1fr)_minmax(200px,2fr)] gap-4 rounded border border-rule bg-sunk p-4">
                                            <legend className="px-1 text-sm font-semibold">Schedule a follow-up (optional)</legend>
                                            <TextField label="Follow-up date" name="follow_up_on" type="date" min={today} error={errors.follow_up_on} />
                                            <TextField label="Purpose" name="follow_up_purpose" maxLength={255} error={errors.follow_up_purpose} />
                                        </fieldset>
                                        <div className="flex flex-wrap justify-end gap-3">
                                            <button type="button" className={buttonClasses.quiet} onClick={() => setPanel(null)}>
                                                Cancel
                                            </button>
                                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                                {processing ? 'Saving…' : 'Save and seal session'}
                                            </button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </Card>
                    )}

                    {panel === 'follow-up' && !isClosed && (
                        <Card title="Schedule a follow-up">
                            <Form action={`${caseUrl}/follow-ups`} method="post" options={{ preserveScroll: true }} onSuccess={() => setPanel(null)} className="flex flex-col gap-4 p-5">
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid grid-cols-[minmax(160px,1fr)_minmax(220px,2fr)] gap-4">
                                            <TextField label="Date" name="due_on" type="date" min={today} required error={errors.due_on} />
                                            <TextField label="Purpose" name="purpose" required maxLength={255} error={errors.purpose} hint="For example: Check-in with adviser" />
                                        </div>
                                        <div className="flex flex-wrap justify-end gap-3">
                                            <button type="button" className={buttonClasses.quiet} onClick={() => setPanel(null)}>
                                                Cancel
                                            </button>
                                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                                Schedule follow-up
                                            </button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </Card>
                    )}

                    <Card title="Case history" aside={<span className="text-[13px] text-ink-2">Newest first · {history.length} entries</span>}>
                        <ol className="flex flex-col gap-6 px-5 pt-5 pb-3">
                            {history.map((entry) => (
                                <li key={entry.key} className="grid grid-cols-[84px_24px_minmax(0,1fr)] items-start gap-x-3">
                                    <span className="pt-px font-mono text-xs text-ink-2 uppercase">
                                        {formatDate(entry.at)}
                                        {entry.at.length > 10 && (
                                            <span className="block">{new Date(entry.at).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', hour12: false })}</span>
                                        )}
                                    </span>
                                    <span aria-hidden="true" className={`mx-auto mt-[5px] size-3 rounded-full border-2 bg-surface ${dotStyles[entry.kind]}`} />
                                    <div className="flex min-w-0 flex-col gap-2">
                                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <span className="font-bold">{entry.title}</span>
                                            {entry.kind === 'session' && <Stamp tone="brick">Confidential</Stamp>}
                                            {entry.statusLabel && <Stamp tone={entry.isOverdue ? 'brick' : statusTone(entry.status ?? '')}>{entry.statusLabel}</Stamp>}
                                        </div>
                                        {entry.detail && <p className="text-sm">{entry.detail}</p>}
                                        {entry.outcome && <p className="text-sm whitespace-pre-line">{entry.outcome}</p>}
                                        {entry.kind === 'session' && <SealedNotes sessionId={entry.id} signerName={auth.user?.name ?? 'you'} />}
                                        {entry.kind === 'follow_up' && entry.status === 'pending' && <FollowUpActions followUpId={entry.id} today={today} />}
                                        {entry.kind === 'appointment' && (
                                            <Link href={`/counselor/appointments/${entry.id}/edit`} className={`${buttonClasses.link} -ml-1.5 self-start`}>
                                                Open appointment
                                            </Link>
                                        )}
                                        <span className="text-[13px] text-ink-2">— signed {entry.signedBy ?? '—'}</span>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </Card>
                </div>

                <aside className="flex min-w-0 flex-[2_1_300px] flex-col gap-6">
                    {!isClosed && (
                        <Card title="Add to this case">
                            <div className="flex flex-col gap-2 p-5">
                                <button type="button" className={`${buttonClasses.quiet} justify-start`} onClick={() => setPanel('session')}>
                                    <span className="w-[26px] font-mono text-[11px]">CS</span>Counseling session
                                </button>
                                <button type="button" className={`${buttonClasses.quiet} justify-start`} onClick={() => setPanel('follow-up')}>
                                    <span className="w-[26px] font-mono text-[11px]">FU</span>Follow-up
                                </button>
                                <Link href={`/counselor/coordination/create?case=${guidanceCase.id}`} className={`${buttonClasses.quiet} justify-start`}>
                                    <span className="w-[26px] font-mono text-[11px]">PG</span>Parent / guardian contact
                                </Link>
                                <Link href={`/counselor/appointments/create?case=${guidanceCase.id}`} className={`${buttonClasses.quiet} justify-start`}>
                                    <span className="w-[26px] font-mono text-[11px]">AP</span>Appointment
                                </Link>
                                <Link href={`/counselor/documents/create?case=${guidanceCase.id}`} className={`${buttonClasses.quiet} justify-start`}>
                                    <span className="w-[26px] font-mono text-[11px]">DC</span>Document
                                </Link>
                            </div>
                        </Card>
                    )}

                    <Card title={isClosed ? 'Closed case' : 'Status and details'}>
                        <Form action={caseUrl} method="patch" options={{ preserveScroll: true }} className="flex flex-col gap-4 p-5">
                            {({ errors, processing }) => (
                                <>
                                    <SelectField
                                        label="Status"
                                        name="status"
                                        defaultValue={guidanceCase.status}
                                        required
                                        error={errors.status}
                                        onChange={(event) => setClosing(event.target.value === 'closed')}
                                    >
                                        {statuses.map((status) => (
                                            <option key={status.value} value={status.value}>
                                                {status.label}
                                            </option>
                                        ))}
                                    </SelectField>
                                    <SelectField label="Category" name="category" defaultValue={guidanceCase.category} required error={errors.category}>
                                        {categories.map((category) => (
                                            <option key={category.value} value={category.value}>
                                                {category.label}
                                            </option>
                                        ))}
                                    </SelectField>
                                    <TextAreaField label="Case summary" name="summary" rows={3} defaultValue={guidanceCase.summary ?? ''} maxLength={3000} error={errors.summary} />
                                    {closing && (
                                        <TextAreaField
                                            label="Closure summary"
                                            name="closure_summary"
                                            rows={3}
                                            required
                                            defaultValue={guidanceCase.closureSummary ?? ''}
                                            maxLength={3000}
                                            error={errors.closure_summary}
                                            hint="Required to close. Closed cases stay in the student's history and are never deleted."
                                        />
                                    )}
                                    <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                        {closing && !isClosed ? 'Close case' : 'Save'}
                                    </button>
                                    <p className="text-xs text-ink-2">
                                        Last updated {formatDateTime(guidanceCase.updatedAt)}
                                        {guidanceCase.updatedBy && ` by ${guidanceCase.updatedBy}`}
                                    </p>
                                </>
                            )}
                        </Form>
                    </Card>

                    <Card title="Documents" aside={<Link href={`/counselor/documents/create?case=${guidanceCase.id}`} className={buttonClasses.link}>Upload</Link>}>
                        <DocumentList documents={documents} empty="No documents on this case." />
                        <p className="border-t border-rule px-5 py-2.5 text-xs text-ink-2">Every download is recorded in the activity log.</p>
                    </Card>

                    <Card title="Signed on this case">
                        <div className="flex flex-col gap-1 px-5 py-4 text-sm">
                            {guidanceCase.signers.map((signer) => (
                                <p key={signer.name}>
                                    {signer.name}{' '}
                                    <span className="text-ink-2">
                                        · {signer.entries} {signer.entries === 1 ? 'entry' : 'entries'}
                                    </span>
                                </p>
                            ))}
                            <p className="mt-2 text-xs text-ink-2">This case isn't owned by one counselor. All counselors share it.</p>
                        </div>
                    </Card>
                </aside>
            </div>
        </CounselorLayout>
    );
}
