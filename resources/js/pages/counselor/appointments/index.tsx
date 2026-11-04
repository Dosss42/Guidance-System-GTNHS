import { buttonClasses, Card, EmptyState, FilterTabs, FlashStatus, formatDate, formatTime, Pagination, Stamp, statusTone } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Appointment, Paginated } from '@/types';
import { Link } from '@inertiajs/react';

const views = [
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'today', label: 'Today' },
    { value: 'past', label: 'Past' },
];

export default function AppointmentsIndex({ appointments, view }: { appointments: Paginated<Appointment>; view: string }) {
    const byDay = appointments.data.reduce<Record<string, Appointment[]>>((groups, appointment) => {
        const day = appointment.scheduledAtLocal.slice(0, 10);
        (groups[day] ??= []).push(appointment);
        return groups;
    }, {});

    return (
        <CounselorLayout title="Appointments">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Desk · Appointments</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Appointments</h1>
                </div>
                <Link href="/counselor/appointments/create" className={buttonClasses.primary}>
                    Book appointment
                </Link>
            </div>

            <FlashStatus />

            <Card
                title={`${appointments.total} ${appointments.total === 1 ? 'appointment' : 'appointments'}`}
                aside={<FilterTabs label="Show" options={views} current={view} href={(value) => `/counselor/appointments?view=${value}`} />}
            >
                {appointments.data.length === 0 ? (
                    <EmptyState code="AP" heading={view === 'past' ? 'No past appointments.' : 'Nothing booked yet.'} body="Book an appointment from here or from a student's record." />
                ) : (
                    Object.entries(byDay).map(([day, items]) => (
                        <section key={day} aria-label={formatDate(day)} className="border-b border-rule last:border-b-0">
                            <h2 className="mono-label bg-sunk px-5 py-2">{formatDate(day)}</h2>
                            <ul>
                                {items.map((appointment) => (
                                    <li key={appointment.id} className="border-t border-rule first:border-t-0">
                                        <Link
                                            href={`/counselor/appointments/${appointment.id}/edit`}
                                            className="grid grid-cols-[72px_minmax(0,1fr)] items-center gap-x-4 gap-y-1 px-5 py-3 text-ink hover:bg-sunk sm:grid-cols-[72px_minmax(0,1fr)_auto]"
                                        >
                                            <span className="font-mono text-[13px] text-ink-2">{formatTime(appointment.scheduledAt)}</span>
                                            <span className="min-w-0">
                                                <span className="font-semibold">{appointment.student?.name}</span>
                                                <span className="block text-[13px] text-ink-2">
                                                    {appointment.student?.detail} · {appointment.purposeLabel} · {appointment.durationMinutes} min
                                                    {appointment.caseReference && ` · ${appointment.caseReference}`}
                                                </span>
                                            </span>
                                            <span className="col-start-2 sm:col-start-auto">
                                                <Stamp tone={statusTone(appointment.status)}>{appointment.statusLabel}</Stamp>
                                            </span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))
                )}
                <Pagination page={appointments} noun={['Earlier', 'Later']} />
            </Card>
        </CounselorLayout>
    );
}
