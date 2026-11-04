import { StudentPicker } from '@/components/student-picker';
import { buttonClasses, SelectField, TextAreaField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, StudentSummary } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface Props {
    student: StudentSummary | null;
    caseId: number | null;
    cases: { id: number; label: string }[];
    methods: Option[];
    now: string;
}

export default function CreateCoordination({ student, caseId, cases, methods, now }: Props) {
    const backUrl = caseId ? `/counselor/cases/${caseId}` : '/counselor/coordination';

    return (
        <CounselorLayout title="Record a parent / guardian contact">
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/coordination" className="text-green hover:text-ink">
                    Parent / guardian
                </Link>{' '}
                <span aria-hidden="true">/</span> Record a contact
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">Record a parent / guardian contact</h1>
                <p className="text-ink-2">Collect only what the school's approved process needs.</p>
            </div>

            <Form action="/counselor/coordination" method="post" className="flex max-w-4xl flex-col gap-6 rounded border border-rule bg-surface p-6">
                {({ errors, processing }) => (
                    <>
                        <StudentPicker student={student} error={errors.student_id} reloadOnly={['student', 'cases']} />

                        <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
                            <TextField label="Name of parent or guardian" name="contact_name" required maxLength={120} error={errors.contact_name} autoComplete="off" />
                            <TextField label="Relationship to student" name="relationship" required maxLength={40} error={errors.relationship} hint="For example: Mother, Grandfather, Guardian" />
                            <TextField label="Date and time" name="coordinated_at" type="datetime-local" max={now} defaultValue={now} required error={errors.coordinated_at} />
                            <SelectField label="How" name="method" defaultValue="phone" required error={errors.method}>
                                {methods.map((method) => (
                                    <option key={method.value} value={method.value}>
                                        {method.label}
                                    </option>
                                ))}
                            </SelectField>
                        </div>

                        <SelectField key={cases.map((item) => item.id).join('-')} label="Linked guidance case" name="guidance_case_id" defaultValue={caseId ?? ''} error={errors.guidance_case_id}>
                            <option value="">None</option>
                            {cases.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.label}
                                </option>
                            ))}
                        </SelectField>

                        <TextField label="Purpose" name="purpose" required maxLength={255} error={errors.purpose} hint="For example: Invite to the 11 Nov meeting" />
                        <TextAreaField label="Outcome or next step" name="outcome" rows={3} maxLength={2000} error={errors.outcome} />

                        <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                            <Link href={backUrl} className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Saving…' : 'Save contact'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
