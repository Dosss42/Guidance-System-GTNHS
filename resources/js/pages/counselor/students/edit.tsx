import { StudentIdentityFields } from '@/components/student-fields';
import { buttonClasses } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { StudentIdentity } from '@/types';
import { Form, Link } from '@inertiajs/react';

export default function EditStudent({ student }: { student: StudentIdentity }) {
    const profileUrl = `/counselor/students/${student.id}`;

    return (
        <CounselorLayout title={`Edit ${student.filingName}`}>
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/students" className="text-green hover:text-ink">
                    Students
                </Link>{' '}
                <span aria-hidden="true">/</span>{' '}
                <Link href={profileUrl} className="text-green hover:text-ink">
                    {student.filingName}
                </Link>{' '}
                <span aria-hidden="true">/</span> Edit
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">Edit student details</h1>
                <p className="text-ink-2">Grade and section are changed in the enrollment history on the student's record, so past years stay as they were.</p>
            </div>

            <Form action={profileUrl} method="put" className="flex max-w-4xl flex-col gap-7 rounded border border-rule bg-surface p-6">
                {({ errors, processing }) => (
                    <>
                        <StudentIdentityFields errors={errors} defaults={student} />
                        <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                            <Link href={profileUrl} className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Saving…' : 'Save changes'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
