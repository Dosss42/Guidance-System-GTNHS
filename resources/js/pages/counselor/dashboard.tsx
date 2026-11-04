import { buttonClasses, Card, EmptyState, FlashStatus, formatDate, formatTime, Stamp, statusTone } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Appointment } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Fragment } from 'react';

interface Props {
    today: string;
    now: string;
    counts: {
        appointmentsToday: number;
        referralsToReview: number;
        followUpsOverdue: number;
        activeCases: number;
        studentsOnFile: number;
        currentSchoolYear: string | null;
    };
    docket: Appointment[];
    overdueFollowUps: { id: number; dueOn: string; daysOverdue: number; purpose: string; caseId: number; caseReference: string; studentName: string }[];
    referralsToReview: { id: number; reference: string; receivedOn: string; referringTeacher: string; studentName: string }[];
    ledger: { id: number; at: string; signedBy: string | null; summary: string; recordLabel: string | null; recordUrl: string | null }[];
}

const longDate = new Intl.DateTimeFormat('en-PH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const ledgerTime = new Intl.DateTimeFormat('en-PH', { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false });

export default function CounselorDashboard({ today, now, counts, docket, overdueFollowUps, referralsToReview, ledger }: Props) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0];
    const nowIndex = docket.findIndex((appointment) => appointment.scheduledAt > now);

    const tiles = [
        { value: counts.appointmentsToday, label: 'appointments today', href: '/counselor/appointments?view=today', tone: '' },
        { value: counts.referralsToReview, label: 'referrals awaiting review', href: '/counselor/referrals?status=received', tone: counts.referralsToReview > 0 ? 'text-ochre' : '' },
        { value: counts.followUpsOverdue, label: 'follow-ups overdue', href: '/counselor/follow-ups', tone: counts.followUpsOverdue > 0 ? 'text-brick' : '' },
        { value: counts.activeCases, label: 'active guidance cases', href: '/counselor/cases', tone: '' },
    ];

    return (
        <CounselorLayout title="Today's desk">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="font-mono text-xs tracking-[0.08em] text-ink-2 uppercase">
                        {longDate.format(new Date(`${today}T00:00:00`))}
                        {counts.currentSchoolYear && ` · SY ${counts.currentSchoolYear}`}
                    </span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Today's desk</h1>
                    <p className="text-ink-2">Signed in as {firstName}. Everything you record here is signed with your name.</p>
                </div>
                <div className="flex flex-wrap gap-2">
                    <Link href="/counselor/students" className={buttonClasses.quiet}>
                        Find a student
                    </Link>
                    <Link href="/counselor/referrals/create" className={buttonClasses.primary}>
                        Encode referral
                    </Link>
                </div>
            </div>

            <FlashStatus />

            <section aria-label="Desk counts" className="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] rounded border border-rule bg-surface">
                {tiles.map((tile) => (
                    <Link key={tile.label} href={tile.href} className="flex flex-col gap-0.5 border-r border-b border-rule px-5 py-4 text-ink last:border-r-0 hover:bg-sunk">
                        <span className={`font-mono text-[30px] leading-tight font-medium ${tile.tone}`}>{String(tile.value).padStart(2, '0')}</span>
                        <span className="text-[13px] text-ink-2">{tile.label}</span>
                    </Link>
                ))}
            </section>

            {!counts.currentSchoolYear && (
                <p className="rounded border-[1.5px] border-ochre bg-ochre-soft px-4 py-3 text-sm">
                    No school year is marked current.{' '}
                    <Link href="/counselor/school-years" className="font-semibold text-green hover:text-ink">
                        Set one up
                    </Link>{' '}
                    so the student list and enrollments know which year it is.
                </p>
            )}

            <div className="flex flex-wrap items-start gap-6">
                <Card
                    title="Docket"
                    className="flex-[3_1_520px]"
                    aside={
                        <Link href="/counselor/appointments/create" className={buttonClasses.link}>
                            Book appointment
                        </Link>
                    }
                >
                    {docket.length === 0 ? (
                        <EmptyState code="AP" heading="No appointments today." body="Walk-ins can be booked and recorded as they arrive." />
                    ) : (
                        <ol className="px-5 pt-1 pb-3">
                            {docket.map((appointment, index) => (
                                <Fragment key={appointment.id}>
                                    {index === nowIndex && <NowLine now={now} />}
                                    <li className="grid grid-cols-[64px_minmax(0,1fr)] border-b border-rule last:border-b-0">
                                        <span className="py-3.5 font-mono text-xs text-ink-2">{formatTime(appointment.scheduledAt)}</span>
                                        <Link
                                            href={`/counselor/appointments/${appointment.id}/edit`}
                                            className={`my-2 flex flex-wrap items-center gap-x-3.5 gap-y-1.5 rounded border px-3 py-2.5 text-ink ${appointment.status === 'scheduled' ? 'border-green bg-green-soft' : 'border-rule bg-sunk'}`}
                                        >
                                            <span className="font-semibold">{appointment.student?.name}</span>
                                            <span className="text-[13px] text-ink-2">{appointment.student?.detail}</span>
                                            <span className="text-[13px] text-ink-2">{appointment.purposeLabel}</span>
                                            <span className="ml-auto">
                                                <Stamp tone={statusTone(appointment.status)}>{appointment.statusLabel}</Stamp>
                                            </span>
                                        </Link>
                                    </li>
                                </Fragment>
                            ))}
                            {nowIndex === -1 && <NowLine now={now} />}
                        </ol>
                    )}
                </Card>

                <Card title="Needs attention" className="flex-[2_1_340px]">
                    {overdueFollowUps.length === 0 && referralsToReview.length === 0 ? (
                        <EmptyState code="FU" heading="Nothing waiting." body="Overdue follow-ups and referrals no one has reviewed yet appear here." />
                    ) : (
                        <div className="flex flex-col px-5 pb-4">
                            {overdueFollowUps.length > 0 && (
                                <>
                                    <span className="mono-label pt-3.5 pb-1 !text-brick">Overdue follow-ups</span>
                                    {overdueFollowUps.map((item) => (
                                        <Link key={item.id} href={`/counselor/cases/${item.caseId}`} className="flex items-center gap-3.5 border-b border-rule py-2.5 text-ink hover:bg-sunk">
                                            <span className="w-16 shrink-0 font-mono text-xs text-brick">−{item.daysOverdue} {item.daysOverdue === 1 ? 'day' : 'days'}</span>
                                            <span className="min-w-0 flex-1 text-sm">
                                                {item.purpose}
                                                <span className="block text-[13px] text-ink-2">
                                                    {item.caseReference} · {item.studentName}
                                                </span>
                                            </span>
                                        </Link>
                                    ))}
                                </>
                            )}
                            {referralsToReview.length > 0 && (
                                <>
                                    <span className="mono-label pt-4 pb-1 !text-ochre">Referrals not yet reviewed</span>
                                    {referralsToReview.map((item) => (
                                        <Link key={item.id} href={`/counselor/referrals/${item.id}`} className="flex items-center gap-3.5 border-b border-rule py-2.5 text-ink last:border-b-0 hover:bg-sunk">
                                            <span className="w-16 shrink-0 font-mono text-xs text-ink-2">{item.reference.replace(/^RF-\d{4}-/, 'RF-')}</span>
                                            <span className="min-w-0 flex-1 text-sm">
                                                {item.studentName} · from {item.referringTeacher}
                                                <span className="block text-[13px] text-ink-2">Received {formatDate(item.receivedOn)}</span>
                                            </span>
                                        </Link>
                                    ))}
                                </>
                            )}
                        </div>
                    )}
                </Card>
            </div>

            <Card title="Shared ledger" aside={<span className="text-[13px] text-ink-2">Every counselor sees the same records. Each entry is signed by whoever made it.</span>}>
                {ledger.length === 0 ? (
                    <EmptyState code="ST" heading="No entries yet." body="Signed entries appear here as counselors add and update records." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[640px] border-collapse text-sm">
                            <thead>
                                <tr className="text-left">
                                    {['When', 'Signed', 'Entry', 'Record'].map((heading) => (
                                        <th key={heading} scope="col" className="mono-label border-b border-rule px-3 py-2.5 first:pl-5 last:pr-5">
                                            {heading}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {ledger.map((entry) => (
                                    <tr key={entry.id} className="border-b border-rule last:border-b-0">
                                        <td className="px-3 py-3 pl-5 font-mono text-[13px] whitespace-nowrap text-ink-2">{ledgerTime.format(new Date(entry.at))}</td>
                                        <td className="px-3 py-3">{entry.signedBy ?? '—'}</td>
                                        <td className="px-3 py-3">{entry.summary}</td>
                                        <td className="px-3 py-3 pr-5">
                                            {entry.recordUrl ? (
                                                <Link href={entry.recordUrl} className="font-semibold text-green hover:text-ink">
                                                    {entry.recordLabel}
                                                </Link>
                                            ) : (
                                                <span className="text-ink-2">{entry.recordLabel ?? '—'}</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>
        </CounselorLayout>
    );
}

function NowLine({ now }: { now: string }) {
    return (
        <li aria-hidden="true" className="relative my-1 h-0 border-t-2 border-brick">
            <span className="absolute -top-[11px] left-0 rounded-sm bg-brick px-1.5 py-px font-mono text-[11px] font-semibold text-surface">{formatTime(now)}</span>
        </li>
    );
}
