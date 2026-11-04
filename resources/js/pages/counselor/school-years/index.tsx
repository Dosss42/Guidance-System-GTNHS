import { buttonClasses, Card, FlashStatus, SelectField, Stamp, TextField } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import { Form, Link } from '@inertiajs/react';
import { useState } from 'react';

interface SchoolYearRow {
    id: number;
    name: string;
    startDate: string;
    endDate: string;
    isCurrent: boolean;
    enrollmentCount: number;
}

interface SectionRow {
    id: number;
    gradeLevel: number;
    name: string;
    enrollmentCount: number;
}

interface Props {
    schoolYears: SchoolYearRow[];
    sections: SectionRow[];
    gradeLevels: number[];
}

const shortDate = new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' });
const formatDate = (iso: string) => shortDate.format(new Date(`${iso}T00:00:00`));

export default function SchoolYearsIndex({ schoolYears, sections, gradeLevels }: Props) {
    const [editingYear, setEditingYear] = useState<number | null>(null);
    const [editingSection, setEditingSection] = useState<number | null>(null);
    const nextStart = schoolYears.length > 0 ? Number(schoolYears[0].name.slice(0, 4)) + 1 : new Date().getFullYear();

    return (
        <CounselorLayout title="School years and sections">
            <div className="flex flex-col gap-0.5">
                <span className="mono-label">Records · Setup</span>
                <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">School years and sections</h1>
                <p className="max-w-3xl text-ink-2">
                    Enrollments point to these. Mark one school year as current; the student list opens on it. Nothing here is ever deleted, so past enrollments
                    keep their year and section.
                </p>
            </div>

            <FlashStatus />

            <div className="flex flex-wrap items-start gap-6">
                <Card title="School years" className="flex-[1_1_440px]">
                    <ul className="flex flex-col">
                        {schoolYears.map((year) => (
                            <li key={year.id} className="border-b border-rule px-5 py-3.5">
                                {editingYear === year.id ? (
                                    <SchoolYearForm
                                        action={`/counselor/school-years/${year.id}`}
                                        method="patch"
                                        defaults={year}
                                        idPrefix={`year-${year.id}`}
                                        submitLabel="Save"
                                        onDone={() => setEditingYear(null)}
                                    />
                                ) : (
                                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span className="w-24 shrink-0 font-mono text-sm font-medium">SY {year.name}</span>
                                        <span className="min-w-0 flex-1 text-[13px] text-ink-2">
                                            {formatDate(year.startDate)} – {formatDate(year.endDate)}
                                            <span className="block">
                                                {year.enrollmentCount} {year.enrollmentCount === 1 ? 'enrollment' : 'enrollments'}
                                            </span>
                                        </span>
                                        {year.isCurrent ? (
                                            <Stamp tone="green">Current</Stamp>
                                        ) : (
                                            <Link
                                                href={`/counselor/school-years/${year.id}/current`}
                                                method="patch"
                                                as="button"
                                                preserveScroll
                                                className={`${buttonClasses.link} cursor-pointer`}
                                            >
                                                Make current
                                            </Link>
                                        )}
                                        <button type="button" className={buttonClasses.link} onClick={() => setEditingYear(year.id)}>
                                            Edit
                                        </button>
                                    </div>
                                )}
                            </li>
                        ))}
                    </ul>
                    <div className="bg-sunk px-5 py-4">
                        <p className="mb-3 text-sm font-semibold">Add a school year</p>
                        <SchoolYearForm
                            action="/counselor/school-years"
                            method="post"
                            defaults={{ name: `${nextStart}–${nextStart + 1}`, startDate: `${nextStart}-06-15`, endDate: `${nextStart + 1}-04-15` }}
                            idPrefix="new-year"
                            submitLabel="Add school year"
                        />
                    </div>
                </Card>

                <Card title="Sections" className="flex-[1_1_440px]">
                    {gradeLevels.map((grade) => {
                        const gradeSections = sections.filter((section) => section.gradeLevel === grade);

                        return (
                            <div key={grade} className="border-b border-rule px-5 py-3">
                                <p className="mono-label mb-1.5">Grade {grade}</p>
                                {gradeSections.length === 0 ? (
                                    <p className="text-[13px] text-ink-2">No sections yet.</p>
                                ) : (
                                    <ul className="flex flex-col">
                                        {gradeSections.map((section) => (
                                            <li key={section.id} className="py-1">
                                                {editingSection === section.id ? (
                                                    <SectionForm
                                                        action={`/counselor/sections/${section.id}`}
                                                        method="patch"
                                                        gradeLevels={gradeLevels}
                                                        defaults={section}
                                                        idPrefix={`section-${section.id}`}
                                                        submitLabel="Save"
                                                        onDone={() => setEditingSection(null)}
                                                    />
                                                ) : (
                                                    <div className="flex items-center gap-3 text-sm">
                                                        <span className="flex-1">{section.name}</span>
                                                        <span className="text-[13px] text-ink-2">
                                                            {section.enrollmentCount} {section.enrollmentCount === 1 ? 'enrollment' : 'enrollments'}
                                                        </span>
                                                        <button type="button" className={buttonClasses.link} onClick={() => setEditingSection(section.id)}>
                                                            Edit
                                                        </button>
                                                    </div>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        );
                    })}
                    <div className="bg-sunk px-5 py-4">
                        <p className="mb-3 text-sm font-semibold">Add a section</p>
                        <SectionForm action="/counselor/sections" method="post" gradeLevels={gradeLevels} idPrefix="new-section" submitLabel="Add section" />
                    </div>
                </Card>
            </div>
        </CounselorLayout>
    );
}

interface SchoolYearFormProps {
    action: string;
    method: 'post' | 'patch';
    defaults: { name: string; startDate: string; endDate: string };
    idPrefix: string;
    submitLabel: string;
    onDone?: () => void;
}

function SchoolYearForm({ action, method, defaults, idPrefix, submitLabel, onDone }: SchoolYearFormProps) {
    return (
        <Form action={action} method={method} options={{ preserveScroll: true }} onSuccess={onDone} className="flex flex-col gap-4">
            {({ errors, processing }) => (
                <>
                    <div className="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-4">
                        <TextField id={`${idPrefix}-name`} label="Name" name="name" defaultValue={defaults.name} required error={errors.name} hint="e.g. 2026–2027" />
                        <TextField id={`${idPrefix}-start`} label="Starts" name="start_date" type="date" defaultValue={defaults.startDate} required error={errors.start_date} />
                        <TextField id={`${idPrefix}-end`} label="Ends" name="end_date" type="date" defaultValue={defaults.endDate} required error={errors.end_date} />
                    </div>
                    <div className="flex flex-wrap justify-end gap-3">
                        {onDone && (
                            <button type="button" className={buttonClasses.quiet} onClick={onDone}>
                                Cancel
                            </button>
                        )}
                        <button type="submit" className={buttonClasses.primary} disabled={processing}>
                            {submitLabel}
                        </button>
                    </div>
                </>
            )}
        </Form>
    );
}

interface SectionFormProps {
    action: string;
    method: 'post' | 'patch';
    gradeLevels: number[];
    defaults?: { gradeLevel: number; name: string };
    idPrefix: string;
    submitLabel: string;
    onDone?: () => void;
}

function SectionForm({ action, method, gradeLevels, defaults, idPrefix, submitLabel, onDone }: SectionFormProps) {
    return (
        <Form action={action} method={method} options={{ preserveScroll: true }} onSuccess={onDone} resetOnSuccess={method === 'post'} className="flex flex-col gap-4">
            {({ errors, processing }) => (
                <>
                    <div className="grid grid-cols-[minmax(120px,1fr)_minmax(160px,2fr)] gap-4">
                        <SelectField id={`${idPrefix}-grade`} label="Grade" name="grade_level" defaultValue={defaults?.gradeLevel ?? gradeLevels[0]} required error={errors.grade_level}>
                            {gradeLevels.map((level) => (
                                <option key={level} value={level}>
                                    Grade {level}
                                </option>
                            ))}
                        </SelectField>
                        <TextField id={`${idPrefix}-name`} label="Section name" name="name" defaultValue={defaults?.name} required error={errors.name} autoComplete="off" />
                    </div>
                    <div className="flex flex-wrap justify-end gap-3">
                        {onDone && (
                            <button type="button" className={buttonClasses.quiet} onClick={onDone}>
                                Cancel
                            </button>
                        )}
                        <button type="submit" className={buttonClasses.primary} disabled={processing}>
                            {submitLabel}
                        </button>
                    </div>
                </>
            )}
        </Form>
    );
}
