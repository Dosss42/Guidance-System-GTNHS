import { StudentPicker } from '@/components/student-picker';
import { buttonClasses, FieldError, SelectField, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Option, StudentSummary } from '@/types';
import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    student: StudentSummary | null;
    caseId: number | null;
    cases: { id: number; label: string }[];
    categories: Option[];
    allowedExtensions: string[];
    maxMegabytes: number;
}

export default function UploadDocument({ student, caseId, cases, categories, allowedExtensions, maxMegabytes }: Props) {
    const [fileName, setFileName] = useState<string | null>(null);
    const [tooLarge, setTooLarge] = useState(false);
    const accept = allowedExtensions.map((extension) => `.${extension}`).join(',');
    const typeList = allowedExtensions.filter((extension) => extension !== 'jpeg').map((extension) => extension.toUpperCase()).join(', ');
    const backUrl = caseId ? `/counselor/cases/${caseId}` : '/counselor/documents';

    return (
        <CounselorLayout title="Upload document">
            <nav aria-label="Breadcrumb" className="text-[13px] text-ink-2">
                <Link href="/counselor/documents" className="text-green hover:text-ink">
                    Documents
                </Link>{' '}
                <span aria-hidden="true">/</span> Upload
            </nav>
            <div className="flex max-w-3xl flex-col gap-1">
                <h1 className="font-display text-[40px] leading-[1.1] font-medium">Upload a document</h1>
                <p className="text-ink-2">Upload only documents the department has approved for keeping. The file is stored privately under a generated name.</p>
            </div>

            <Form action="/counselor/documents" method="post" className="flex max-w-4xl flex-col gap-6 rounded border border-rule bg-surface p-6">
                {({ errors, processing, progress }) => (
                    <>
                        <StudentPicker student={student} error={errors.student_id} reloadOnly={['student', 'cases']} />

                        <div className="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-5">
                            <TextField label="Title" name="title" required maxLength={120} error={errors.title} hint="For example: Referral slip (scan)" autoComplete="off" />
                            <SelectField label="Type" name="category" required defaultValue="" error={errors.category} hint="Proposed list, pending department approval">
                                <option value="" disabled>
                                    Choose a type
                                </option>
                                {categories.map((category) => (
                                    <option key={category.value} value={category.value}>
                                        {category.label}
                                    </option>
                                ))}
                            </SelectField>
                            <SelectField key={cases.map((item) => item.id).join('-')} label="Linked guidance case" name="guidance_case_id" defaultValue={caseId ?? ''} error={errors.guidance_case_id}>
                                <option value="">None</option>
                                {cases.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.label}
                                    </option>
                                ))}
                            </SelectField>
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <span className="text-sm font-semibold">
                                File <span className="font-bold text-brick" aria-hidden="true">*</span>
                            </span>
                            <label
                                className={`flex cursor-pointer flex-wrap items-center gap-3 rounded border-[1.5px] border-dashed p-4 text-sm focus-within:outline-2 focus-within:outline-green ${errors.file || tooLarge ? 'border-brick' : 'border-rule-2'}`}
                            >
                                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="text-ink-2">
                                    <path d="M12 16V4M7 9l5-5 5 5M5 20h14" />
                                </svg>
                                <span>
                                    {fileName ? (
                                        <span className="font-semibold">{fileName}</span>
                                    ) : (
                                        <span className="font-semibold text-green">Choose a file</span>
                                    )}{' '}
                                    · {typeList}, up to {maxMegabytes} MB. Kept in private storage.
                                </span>
                                <input
                                    type="file"
                                    name="file"
                                    accept={accept}
                                    required
                                    className="sr-only"
                                    onChange={(event) => {
                                        const file = event.target.files?.[0];
                                        setFileName(file?.name ?? null);
                                        setTooLarge(file !== undefined && file.size > maxMegabytes * 1024 * 1024);
                                    }}
                                />
                            </label>
                            {tooLarge && <FieldError>The file is larger than {maxMegabytes} MB.</FieldError>}
                            {errors.file && <FieldError>{errors.file}</FieldError>}
                            {progress && (
                                <progress value={progress.percentage} max={100} className="h-2 w-full accent-green">
                                    {progress.percentage}%
                                </progress>
                            )}
                        </div>

                        <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                            <Link href={backUrl} className={buttonClasses.quiet}>
                                Cancel
                            </Link>
                            <button type="submit" className={buttonClasses.primary} disabled={processing || tooLarge}>
                                {processing ? 'Uploading…' : 'Upload document'}
                            </button>
                        </div>
                    </>
                )}
            </Form>
        </CounselorLayout>
    );
}
