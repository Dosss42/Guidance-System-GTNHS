import { StudentPicker } from '@/components/student-picker';
import { buttonClasses, formatDateTime, SelectField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Appointment, Option, StudentSummary } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface Props {
    appointment: Appointment | null;
    student: StudentSummary | null;
    caseId: number | null;
    purposes: Option[];
    statuses: Option[];
    cases: { id: number; label: string }[];
}

export default function AppointmentForm({ appointment, student, caseId, purposes, statuses, cases }: Props) {
    const isEditing = appointment !== null;
    const title = isEditing ? 'Update appointment' : 'Book an appointment';

    return (
        <CounselorLayout title={title}>
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/appointments" className="text-green hover:text-ink">
                    Appointments
                </Link>{' '}
                <span aria-hidden="true">/</span> {isEditing ? 'Update' : 'Book'}
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">{title}</h1>
                {isEditing && (
                    <p className="text-sm text-ink-2">
                        Booked {formatDateTime(appointment.createdAt)} by {appointment.createdBy ?? '—'}
                        {appointment.updatedBy && ` · last updated by ${appointment.updatedBy}`}
                    </p>
                )}
            </div>

            <Form
                action={isEditing ? `/counselor/appointments/${appointment.id}` : '/counselor/appointments'}
                method={isEditing ? 'put' : 'post'}
                className="flex max-w-4xl flex-col gap-6 rounded border border-rule bg-surface p-6"
            >
                {({ errors, processing }) => (
                    <>
                        {isEditing ? (
                            <>
                                <input type="hidden" name="student_id" value={student?.id} />
                                <div>
                                    <p className="mono-label mb-1">Student</p>
                                    <p className="font-semibold">
                                        {student?.name} <span className="font-normal text-ink-2">· {student?.detail}</span>
                                    </p>
                                </div>
                            </>
                        ) : (
                            <StudentPicker student={student} error={errors.student_id} reloadOnly={['student', 'cases']} />
                        )}

                        <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
                            <TextField
                                label="Date and time"
                                name="scheduled_at"
                                type="datetime-local"
                                defaultValue={appointment?.scheduledAtLocal}
                                required
                                error={errors.scheduled_at}
                            />
                            <SelectField label="Length" name="duration_minutes" defaultValue={appointment?.durationMinutes ?? 30} required error={errors.duration_minutes}>
                                {[15, 30, 45, 60, 90].map((minutes) => (
                                    <option key={minutes} value={minutes}>
                                        {minutes} minutes
                                    </option>
                                ))}
                            </SelectField>
                            <SelectField label="Purpose" name="purpose" defaultValue={appointment?.purpose ?? 'counseling'} required error={errors.purpose} hint="Proposed list, pending department approval">
                                {purposes.map((purpose) => (
                                    <option key={purpose.value} value={purpose.value}>
                                        {purpose.label}
                                    </option>
                                ))}
                            </SelectField>
                            {isEditing && (
                                <SelectField label="Status" name="status" defaultValue={appointment.status} required error={errors.status}>
                                    {statuses.map((status) => (
                                        <option key={status.value} value={status.value}>
                                            {status.label}
                                        </option>
                                    ))}
                                </SelectField>
                            )}
                        </div>

                        <div className="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-5">
                            <SelectField
                                key={cases.map((item) => item.id).join('-')}
                                label="Linked guidance case"
                                name="guidance_case_id"
                                defaultValue={appointment?.caseId ?? caseId ?? ''}
                                error={errors.guidance_case_id}
                                hint={cases.length === 0 ? 'This student has no active cases' : undefined}
                            >
                                <option value="">None</option>
                                {cases.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.label}
                                    </option>
                                ))}
                            </SelectField>
                            <TextField
                                label="Remarks"
                                name="remarks"
                                defaultValue={appointment?.remarks ?? ''}
                                maxLength={255}
                                error={errors.remarks}
                                hint="Logistics only, such as the room. Not for counseling notes."
                            />
                        </div>

                        <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                            <Link href="/counselor/appointments" className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Saving…' : isEditing ? 'Save appointment' : 'Book appointment'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
