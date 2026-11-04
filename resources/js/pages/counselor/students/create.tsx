import { EnrollmentFields, StudentIdentityFields } from '@/components/student-fields';
import { buttonClasses } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { SchoolYearOption, SectionOption, StatusOption } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface Props {
    schoolYears: SchoolYearOption[];
    sections: SectionOption[];
    statuses: StatusOption[];
}

export default function CreateStudent({ schoolYears, sections, statuses }: Props) {
    const hasSetup = schoolYears.length > 0 && sections.length > 0;

    return (
        <CounselorLayout title="Add student">
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/students" className="text-green hover:text-ink">
                    Students
                </Link>{' '}
                <span aria-hidden="true">/</span> Add student
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">Add a student record</h1>
                <p className="text-ink-2">
                    Enter only what the office needs. <span className="font-bold text-brick" aria-hidden="true">*</span> marks a required field.
                </p>
            </div>

            <Form action="/counselor/students" method="post" className="flex max-w-4xl flex-col gap-7 rounded border border-rule bg-surface p-6">
                {({ errors, processing }) => (
                    <>
                        <StudentIdentityFields errors={errors} />

                        <fieldset className="flex flex-col gap-4">
                            <legend className="mono-label mb-4">02 · Enrollment</legend>
                            {hasSetup ? (
                                <EnrollmentFields errors={errors} schoolYears={schoolYears} sections={sections} statuses={statuses} optional />
                            ) : (
                                <p className="text-sm text-ink-2">
                                    No school years or sections yet.{' '}
                                    <Link href="/counselor/school-years" className="font-semibold text-green hover:text-ink">
                                        Set them up
                                    </Link>{' '}
                                    to enroll this student. You can save the record now and add the enrollment later.
                                </p>
                            )}
                        </fieldset>

                        <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                            <Link href="/counselor/students" className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Saving…' : 'Save student record'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
