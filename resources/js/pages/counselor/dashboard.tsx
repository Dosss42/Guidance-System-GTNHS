import { Card, FlashStatus } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import { usePage } from '@inertiajs/react';

const longDate = new Intl.DateTimeFormat('en-PH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

export default function CounselorDashboard({ today }: { today: string }) {
    const { auth } = usePage().props;
    const firstName = auth.user?.name.split(' ')[0];

    return (
        <CounselorLayout title="Today's desk">
            <div className="flex flex-col gap-0.5">
                <span className="font-mono text-xs tracking-[0.08em] text-ink-2 uppercase">{longDate.format(new Date(`${today}T00:00:00`))}</span>
                <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Today's desk</h1>
                <p className="text-ink-2">Signed in as {firstName}. Everything you record here is signed with your name.</p>
            </div>

            <FlashStatus />

            <div className="flex flex-wrap items-start gap-6">
                <Card title="Docket" className="flex-[3_1_520px]">
                    <EmptyState
                        code="AP"
                        heading="No appointments yet"
                        body="Today's appointments will appear here as a timetable once the Appointments module is set up."
                    />
                </Card>

                <Card title="Needs attention" className="flex-[2_1_340px]">
                    <EmptyState
                        code="RF"
                        heading="Nothing waiting"
                        body="Overdue follow-ups and referrals that no one has reviewed yet will be listed here."
                    />
                </Card>
            </div>

            <Card title="Shared ledger" aside={<span className="text-[13px] text-ink-2">Every counselor sees the same records. Each entry is signed by whoever made it.</span>}>
                <EmptyState code="GC" heading="No entries yet" body="When counselors add students, referrals, cases and sessions, each signed entry appears here." />
            </Card>
        </CounselorLayout>
    );
}

function EmptyState({ code, heading, body }: { code: string; heading: string; body: string }) {
    return (
        <div className="flex items-start gap-4 px-5 py-8">
            <span
                aria-hidden="true"
                className="inline-flex h-[22px] w-7 shrink-0 items-center justify-center rounded-sm border border-dashed border-rule-2 font-mono text-[11px] text-ink-2"
            >
                {code}
            </span>
            <div className="flex flex-col gap-1">
                <p className="font-semibold">{heading}</p>
                <p className="max-w-prose text-sm text-ink-2">{body}</p>
            </div>
        </div>
    );
}
