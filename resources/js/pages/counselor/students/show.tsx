import { DocumentList } from '@/components/document-list';
import { EnrollmentFields } from '@/components/student-fields';
import { buttonClasses, Card, enrollmentTone, FlashStatus, formatDate, formatDateTime, Stamp, statusTone } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { DocumentFile, Enrollment, SchoolYearOption, SectionOption, StatusOption, StudentIdentity } from '@/types';
import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';

interface StudentRecord extends StudentIdentity {
    maskedLrn: string;
    createdBy: string | null;
    createdAt: string | null;
    updatedBy: string | null;
    updatedAt: string | null;
}

interface Casework {
    cases: { id: number; reference: string; category: string; status: string; statusLabel: string; openedOn: string; sessionCount: number }[];
    referrals: { id: number; reference: string; category: string; receivedOn: string; referringTeacher: string; status: string; statusLabel: string }[];
    appointments: { id: number; scheduledAt: string; purpose: string; status: string; statusLabel: string; signedBy: string | null }[];
    coordinations: { id: number; coordinatedAt: string; contactName: string; relationship: string; method: string; signedBy: string | null }[];
    documents: DocumentFile[];
}

interface Props {
    student: StudentRecord;
    enrollments: Enrollment[];
    schoolYears: SchoolYearOption[];
    sections: SectionOption[];
    statuses: StatusOption[];
    casework: Casework;
}

const birthDateFormat = new Intl.DateTimeFormat('en-PH', { dateStyle: 'long' });

export default function ShowStudent({ student, enrollments, schoolYears, sections, statuses, casework }: Props) {
    const [lrnShown, setLrnShown] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [adding, setAdding] = useState(false);

    const current = enrollments.find((enrollment) => enrollment.isCurrentYear);
    const yearStrip = [...enrollments].reverse();

    return (
        <CounselorLayout title={student.filingName}>
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/students" className="text-green hover:text-ink">
                    Students
                </Link>{' '}
                <span aria-hidden="true">/</span> {student.filingName}
            </nav>

            <FlashStatus />

            <section aria-label="Student identity card" className="ruled-card relative rounded border border-rule bg-surface bg-[position:0_23px] py-6 pr-6 pl-14">
                <div aria-hidden="true" className="absolute inset-y-0 left-9 w-[1.5px] bg-margin/70" />
                <div className="flex flex-wrap items-start justify-between gap-x-8 gap-y-4">
                    <div className="flex min-w-0 flex-col gap-1">
                        <span className="mono-label">Student record · ST-{String(student.id).padStart(6, '0')}</span>
                        <h1 className="font-display text-[44px] leading-[1.05] font-medium tracking-[-0.01em]">
                            {student.lastName}, <span className="font-normal">{student.filingName.split(', ')[1]}</span>
                        </h1>
                        <div className="mt-1.5 flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
                            <span className="inline-flex items-center gap-2">
                                <span className="text-ink-2">LRN</span>
                                <span className="font-mono text-[15px] tracking-[0.04em]">{lrnShown ? student.lrn.replace(/(\d{4})(?=\d)/g, '$1 ') : student.maskedLrn}</span>
                                <button type="button" onClick={() => setLrnShown((shown) => !shown)} aria-pressed={lrnShown} className={buttonClasses.link}>
                                    {lrnShown ? 'Hide' : 'Show'}
                                </button>
                            </span>
                            {current ? (
                                <>
                                    <span>
                                        <span className="text-ink-2">Grade</span> {current.gradeLevel} · {current.section}
                                    </span>
                                    <Stamp tone={enrollmentTone(current.status)}>{current.statusLabel}</Stamp>
                                </>
                            ) : (
                                <span className="text-ink-2">Not enrolled in the current school year</span>
                            )}
                            {student.birthDate && (
                                <span>
                                    <span className="text-ink-2">Born</span> {birthDateFormat.format(new Date(`${student.birthDate}T00:00:00`))}
                                </span>
                            )}
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={`/counselor/students/${student.id}/edit`} className={buttonClasses.quiet}>
                            Edit details
                        </Link>
                        <Link href={`/counselor/appointments/create?student=${student.id}`} className={buttonClasses.quiet}>
                            Book appointment
                        </Link>
                        <Link href={`/counselor/cases/create?student=${student.id}`} className={buttonClasses.primary}>
                            Open a case
                        </Link>
                    </div>
                </div>

                {yearStrip.length > 0 && (
                    <div className="mt-6 flex flex-wrap rounded border border-rule bg-surface">
                        {yearStrip.map((enrollment) => (
                            <div
                                key={enrollment.id}
                                className={`flex-[1_1_160px] border-r border-rule px-3.5 py-2.5 last:border-r-0 ${enrollment.isCurrentYear ? 'bg-green-soft' : ''}`}
                            >
                                <span className={`block font-mono text-[11px] tracking-[0.08em] ${enrollment.isCurrentYear ? 'text-green' : 'text-ink-2'}`}>
                                    SY {enrollment.schoolYear}
                                    {enrollment.isCurrentYear ? ' · CURRENT' : ''}
                                </span>
                                <span className={`text-sm ${enrollment.isCurrentYear ? 'font-semibold' : ''}`}>
                                    Grade {enrollment.gradeLevel} · {enrollment.section}
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </section>

            <div className="flex flex-wrap items-start gap-6">
                <Card title="Guidance cases" className="flex-[3_1_520px]" aside={<Link href={`/counselor/cases/create?student=${student.id}`} className={buttonClasses.link}>Open a case</Link>}>
                    {casework.cases.length === 0 ? (
                        <p className="px-5 py-5 text-sm text-ink-2">No guidance cases.</p>
                    ) : (
                        <ul>
                            {casework.cases.map((item) => (
                                <li key={item.id} className="border-b border-rule last:border-b-0">
                                    <Link href={`/counselor/cases/${item.id}`} className="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3 text-ink hover:bg-sunk">
                                        <span className="w-28 shrink-0 font-mono text-[13px] font-semibold">{item.reference}</span>
                                        <span className="min-w-0 flex-1">
                                            {item.category}
                                            <span className="block text-[13px] text-ink-2">
                                                Opened {formatDate(item.openedOn)} · {item.sessionCount} {item.sessionCount === 1 ? 'session' : 'sessions'}
                                            </span>
                                        </span>
                                        <Stamp tone={statusTone(item.status)}>{item.statusLabel}</Stamp>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>

                <Card title="Referrals" className="flex-[2_1_300px]" aside={<Link href={`/counselor/referrals/create?student=${student.id}`} className={buttonClasses.link}>Encode referral</Link>}>
                    {casework.referrals.length === 0 ? (
                        <p className="px-5 py-5 text-sm text-ink-2">No referrals.</p>
                    ) : (
                        <ul>
                            {casework.referrals.map((item) => (
                                <li key={item.id} className="border-b border-rule last:border-b-0">
                                    <Link href={`/counselor/referrals/${item.id}`} className="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-ink hover:bg-sunk">
                                        <span className="min-w-0 flex-1">
                                            <span className="font-mono text-[13px] font-semibold">{item.reference}</span> · {item.category}
                                            <span className="block text-[13px] text-ink-2">
                                                {formatDate(item.receivedOn)} · from {item.referringTeacher}
                                            </span>
                                        </span>
                                        <Stamp tone={statusTone(item.status)}>{item.statusLabel}</Stamp>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </Card>
            </div>

            <div className="flex flex-wrap items-start gap-6">
                <Card
                    title="Enrollment history"
                    className="flex-[3_1_520px]"
                    aside={
                        schoolYears.length > 0 &&
                        !adding && (
                            <button type="button" className={buttonClasses.link} onClick={() => setAdding(true)}>
                                Add a school year
                            </button>
                        )
                    }
                >
                    {adding && (
                        <Form
                            action={`/counselor/students/${student.id}/enrollments`}
                            method="post"
                            options={{ preserveScroll: true }}
                            onSuccess={() => setAdding(false)}
                            className="flex flex-col gap-4 border-b border-rule bg-sunk px-5 py-4"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <EnrollmentFields errors={errors} schoolYears={schoolYears} sections={sections} statuses={statuses} idPrefix="new-enrollment" />
                                    <div className="flex flex-wrap justify-end gap-3">
                                        <button type="button" className={buttonClasses.quiet} onClick={() => setAdding(false)}>
                                            Cancel
                                        </button>
                                        <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                            Add enrollment
                                        </button>
                                    </div>
                                </>
                            )}
                        </Form>
                    )}

                    {enrollments.length === 0 && !adding ? (
                        <p className="px-5 py-6 text-sm text-ink-2">No enrollments recorded yet.</p>
                    ) : (
                        <ol className="flex flex-col">
                            {enrollments.map((enrollment) => (
                                <li key={enrollment.id} className="border-b border-rule px-5 py-3.5 last:border-b-0">
                                    {editingId === enrollment.id ? (
                                        <Form
                                            action={`/counselor/students/${student.id}/enrollments/${enrollment.id}`}
                                            method="patch"
                                            options={{ preserveScroll: true }}
                                            onSuccess={() => setEditingId(null)}
                                            className="flex flex-col gap-4"
                                        >
                                            {({ errors, processing }) => (
                                                <>
                                                    <p className="font-mono text-xs tracking-[0.08em] text-ink-2">SY {enrollment.schoolYear}</p>
                                                    <EnrollmentFields
                                                        errors={errors}
                                                        sections={sections}
                                                        statuses={statuses}
                                                        defaults={{ sectionId: enrollment.sectionId, status: enrollment.status }}
                                                        idPrefix={`enrollment-${enrollment.id}`}
                                                    />
                                                    <div className="flex flex-wrap justify-end gap-3">
                                                        <button type="button" className={buttonClasses.quiet} onClick={() => setEditingId(null)}>
                                                            Cancel
                                                        </button>
                                                        <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                                            Save enrollment
                                                        </button>
                                                    </div>
                                                </>
                                            )}
                                        </Form>
                                    ) : (
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                                            <span className="w-28 shrink-0 font-mono text-[13px]">SY {enrollment.schoolYear}</span>
                                            <span className="min-w-0 flex-1">
                                                Grade {enrollment.gradeLevel} · {enrollment.section}
                                                {enrollment.updatedBy && <span className="block text-[13px] text-ink-2">— signed {enrollment.updatedBy}</span>}
                                            </span>
                                            <Stamp tone={enrollmentTone(enrollment.status)}>{enrollment.statusLabel}</Stamp>
                                            <button type="button" className={buttonClasses.link} onClick={() => setEditingId(enrollment.id)}>
                                                Edit
                                            </button>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ol>
                    )}
                </Card>

                <aside className="flex min-w-0 flex-[2_1_300px] flex-col gap-6">
                    <Card title="Record">
                        <dl className="flex flex-col gap-3 px-5 py-4 text-sm">
                            <div>
                                <dt className="mono-label">Created</dt>
                                <dd>
                                    {formatDateTime(student.createdAt)}
                                    {student.createdBy && <span className="text-ink-2"> · {student.createdBy}</span>}
                                </dd>
                            </div>
                            <div>
                                <dt className="mono-label">Last updated</dt>
                                <dd>
                                    {formatDateTime(student.updatedAt)}
                                    {student.updatedBy && <span className="text-ink-2"> · {student.updatedBy}</span>}
                                </dd>
                            </div>
                        </dl>
                        <p className="border-t border-rule px-5 py-3 text-xs text-ink-2">
                            All counselors share this record. Names show who made each change, not who owns it.
                        </p>
                    </Card>

                    <Card title="Appointments" aside={<Link href={`/counselor/appointments/create?student=${student.id}`} className={buttonClasses.link}>Book</Link>}>
                        {casework.appointments.length === 0 ? (
                            <p className="px-5 py-4 text-sm text-ink-2">No appointments.</p>
                        ) : (
                            <ul>
                                {casework.appointments.map((item) => (
                                    <li key={item.id} className="border-b border-rule last:border-b-0">
                                        <Link href={`/counselor/appointments/${item.id}/edit`} className="flex items-center gap-3 px-5 py-2.5 text-sm text-ink hover:bg-sunk">
                                            <span className="min-w-0 flex-1">
                                                <span className="font-mono text-[13px]">{formatDateTime(item.scheduledAt)}</span>
                                                <span className="block text-[13px] text-ink-2">{item.purpose}</span>
                                            </span>
                                            <Stamp tone={statusTone(item.status)}>{item.statusLabel}</Stamp>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <Card title="Documents" aside={<Link href={`/counselor/documents/create?student=${student.id}`} className={buttonClasses.link}>Upload</Link>}>
                        <DocumentList documents={casework.documents} empty="No documents." />
                    </Card>

                    <Card title="Parent / guardian" aside={<Link href={`/counselor/coordination/create?student=${student.id}`} className={buttonClasses.link}>Record contact</Link>}>
                        {casework.coordinations.length === 0 ? (
                            <p className="px-5 py-4 text-sm text-ink-2">No contacts recorded.</p>
                        ) : (
                            <ul className="px-5 py-2">
                                {casework.coordinations.map((item) => (
                                    <li key={item.id} className="border-b border-rule py-2.5 text-sm last:border-b-0">
                                        {item.contactName} <span className="text-ink-2">· {item.relationship}</span>
                                        <span className="block text-[13px] text-ink-2">
                                            {item.method}, {formatDateTime(item.coordinatedAt)}
                                            {item.signedBy && ` · ${item.signedBy}`}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </aside>
            </div>
        </CounselorLayout>
    );
}
