import { StudentPicker } from '@/components/student-picker';
import { buttonClasses, SelectField, TextAreaField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, StudentSummary } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface Props {
    student: StudentSummary | null;
    referral: { id: number; reference: string; category: string } | null;
    openReferrals: { id: number; label: string }[];
    categories: Option[];
    today: string;
}

export default function CreateCase({ student, referral, openReferrals, categories, today }: Props) {
    return (
        <CounselorLayout title="Open a guidance case">
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/cases" className="text-green hover:text-ink">
                    Guidance cases
                </Link>{' '}
                <span aria-hidden="true">/</span> Open a case
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">Open a guidance case</h1>
                <p className="text-ink-2">The case is shared by all counselors. Its reference number is assigned when you save it.</p>
            </div>

            <Form action="/counselor/cases" method="post" className="flex max-w-4xl flex-col gap-6 rounded border border-rule bg-surface p-6">
                {({ errors, processing }) => (
                    <>
                        {referral ? (
                            <>
                                <input type="hidden" name="student_id" value={student?.id} />
                                <input type="hidden" name="referral_id" value={referral.id} />
                                <div className="rounded border border-green bg-green-soft px-4 py-3 text-sm">
                                    Opening from referral <span className="font-mono font-semibold">{referral.reference}</span> for{' '}
                                    <span className="font-semibold">{student?.name}</span> <span className="text-ink-2">· {student?.detail}</span>
                                </div>
                            </>
                        ) : (
                            <>
                                <StudentPicker student={student} error={errors.student_id} reloadOnly={['student', 'openReferrals']} />
                                {openReferrals.length > 0 && (
                                    <SelectField key={openReferrals.map((item) => item.id).join('-')} label="Link a referral" name="referral_id" defaultValue="" error={errors.referral_id}>
                                        <option value="">No referral</option>
                                        {openReferrals.map((item) => (
                                            <option key={item.id} value={item.id}>
                                                {item.label}
                                            </option>
                                        ))}
                                    </SelectField>
                                )}
                            </>
                        )}

                        <div className="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-5">
                            <SelectField
                                label="Category"
                                name="category"
                                required
                                defaultValue={referral?.category ?? ''}
                                error={errors.category}
                                hint="Proposed list, pending department approval"
                            >
                                <option value="" disabled>
                                    Choose a category
                                </option>
                                {categories.map((category) => (
                                    <option key={category.value} value={category.value}>
                                        {category.label}
                                    </option>
                                ))}
                            </SelectField>
                            <TextField label="Date opened" name="opened_on" type="date" max={today} defaultValue={today} required error={errors.opened_on} />
                        </div>

                        <TextAreaField
                            label="Case summary"
                            name="summary"
                            rows={4}
                            maxLength={3000}
                            error={errors.summary}
                            hint="A short, factual description of the concern. Confidential session notes go in each session, not here."
                        />

                        <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                            <Link href="/counselor/cases" className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Opening…' : 'Open case'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
