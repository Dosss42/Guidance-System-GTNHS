import { StudentPicker } from '@/components/student-picker';
import { buttonClasses, FieldError, SelectField, TextAreaField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, StudentSummary } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface Props {
    student: StudentSummary | null;
    today: string;
    categories: Option[];
    channels: Option[];
    priorities: Option[];
}

function RadioGroup({ legend, name, options, defaultValue, error }: { legend: string; name: string; options: Option[]; defaultValue: string; error?: string }) {
    return (
        <fieldset className="flex min-w-0 flex-col gap-1.5">
            <legend className="mb-1.5 text-sm font-semibold">{legend}</legend>
            <div className="flex flex-wrap gap-2">
                {options.map((option) => (
                    <label
                        key={option.value}
                        className="flex min-h-11 flex-[1_1_130px] cursor-pointer items-center gap-2.5 rounded border border-rule-2 bg-surface px-3 text-sm has-[:checked]:border-green has-[:checked]:bg-green-soft has-[:checked]:font-semibold"
                    >
                        <input type="radio" name={name} value={option.value} defaultChecked={option.value === defaultValue} className="size-[18px] accent-green" />
                        {option.label}
                    </label>
                ))}
            </div>
            {error && <FieldError>{error}</FieldError>}
        </fieldset>
    );
}

export default function CreateReferral({ student, today, categories, channels, priorities }: Props) {
    return (
        <CounselorLayout title="Encode referral">
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/referrals" className="text-green hover:text-ink">
                    Referrals
                </Link>{' '}
                <span aria-hidden="true">/</span> Encode referral
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">Encode a teacher referral</h1>
                <p className="text-ink-2">
                    Copy the details from the referral slip or message you received. <span className="font-bold text-brick" aria-hidden="true">*</span> marks a required
                    field.
                </p>
            </div>

            <Form action="/counselor/referrals" method="post" className="flex max-w-4xl flex-col gap-7 rounded border border-rule bg-surface p-6">
                {({ errors, processing }) => (
                    <>
                        <fieldset className="flex flex-col gap-4">
                            <legend className="mono-label mb-4">01 · Student</legend>
                            <StudentPicker student={student} error={errors.student_id} />
                        </fieldset>

                        <fieldset className="flex flex-col gap-5">
                            <legend className="mono-label mb-4">02 · Who referred, and when</legend>
                            <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
                                <TextField label="Referring teacher" name="referring_teacher" required error={errors.referring_teacher} autoComplete="off" />
                                <TextField label="Teacher's role or subject" name="teacher_role" error={errors.teacher_role} hint="For example: Adviser, G8 Rizal" autoComplete="off" />
                                <TextField label="Date received" name="received_on" type="date" max={today} defaultValue={today} required error={errors.received_on} />
                            </div>
                            <RadioGroup legend="Received through" name="received_via" options={channels} defaultValue="paper_slip" error={errors.received_via} />
                        </fieldset>

                        <fieldset className="flex flex-col gap-5">
                            <legend className="mono-label mb-4">03 · Referral details</legend>
                            <div className="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-5">
                                <SelectField label="Category" name="category" required defaultValue="" error={errors.category} hint="Proposed list, pending department approval">
                                    <option value="" disabled>
                                        Choose a category
                                    </option>
                                    {categories.map((category) => (
                                        <option key={category.value} value={category.value}>
                                            {category.label}
                                        </option>
                                    ))}
                                </SelectField>
                                <RadioGroup legend="How soon?" name="priority" options={priorities} defaultValue="routine" error={errors.priority} />
                            </div>
                            <TextAreaField
                                label="Reason for referral"
                                name="reason"
                                rows={5}
                                required
                                maxLength={3000}
                                error={errors.reason}
                                hint="Write only what the referral needs. Leave out diagnoses and anything not on the slip."
                            />
                        </fieldset>

                        <div className="flex flex-wrap items-center gap-3 border-t border-rule pt-5">
                            <label className="mr-auto flex min-h-11 cursor-pointer items-center gap-2.5 text-sm">
                                <input type="checkbox" name="open_case" value="1" className="size-[18px] accent-green" />
                                Open a guidance case from this referral after saving
                            </label>
                            <Link href="/counselor/referrals" className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Saving…' : 'Save referral'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
