import { SelectField, TextField } from '@/components/ui';
import type { SchoolYearOption, SectionOption, StatusOption, StudentIdentity } from '@/types';
import { useState } from 'react';

type Errors = Record<string, string>;

/** Name, LRN and birth date. Shared by the add and edit student forms. */
export function StudentIdentityFields({ errors, defaults }: { errors: Errors; defaults?: Partial<StudentIdentity> }) {
    return (
        <fieldset className="flex flex-col gap-5">
            <legend className="mono-label mb-4">01 · Student</legend>
            <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
                <TextField label="Last name" name="last_name" defaultValue={defaults?.lastName} required error={errors.last_name} autoComplete="off" />
                <TextField label="First name" name="first_name" defaultValue={defaults?.firstName} required error={errors.first_name} autoComplete="off" />
                <TextField label="Middle name" name="middle_name" defaultValue={defaults?.middleName ?? ''} error={errors.middle_name} autoComplete="off" />
                <TextField
                    label="Suffix"
                    name="suffix"
                    defaultValue={defaults?.suffix ?? ''}
                    error={errors.suffix}
                    hint="For example Jr. or III"
                    autoComplete="off"
                />
            </div>
            <div className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
                <TextField
                    label="LRN"
                    name="lrn"
                    defaultValue={defaults?.lrn}
                    required
                    inputMode="numeric"
                    maxLength={14}
                    error={errors.lrn}
                    hint="The 12-digit Learner Reference Number"
                    autoComplete="off"
                />
                <TextField
                    label="Date of birth"
                    name="birth_date"
                    type="date"
                    defaultValue={defaults?.birthDate ?? ''}
                    error={errors.birth_date}
                    hint="Optional until the department confirms it is needed"
                />
            </div>
        </fieldset>
    );
}

interface EnrollmentFieldsProps {
    errors: Errors;
    schoolYears?: SchoolYearOption[];
    sections: SectionOption[];
    statuses: StatusOption[];
    defaults?: { schoolYearId?: number; sectionId?: number; status?: string };
    optional?: boolean;
    idPrefix?: string;
}

/**
 * School year, grade, section and status. Picking a grade narrows the
 * section list. Without `schoolYears`, the year is fixed (editing).
 */
export function EnrollmentFields({ errors, schoolYears, sections, statuses, defaults, optional = false, idPrefix = 'enrollment' }: EnrollmentFieldsProps) {
    const initialGrade = sections.find((section) => section.id === defaults?.sectionId)?.gradeLevel;
    const [grade, setGrade] = useState<number | ''>(initialGrade ?? '');
    const grades = [...new Set(sections.map((section) => section.gradeLevel))];
    const sectionsForGrade = sections.filter((section) => section.gradeLevel === grade);

    return (
        <div className="grid grid-cols-[repeat(auto-fit,minmax(180px,1fr))] gap-5">
            {schoolYears && (
                <SelectField
                    id={`${idPrefix}-year`}
                    label="School year"
                    name="school_year_id"
                    required={!optional}
                    defaultValue={defaults?.schoolYearId ?? (optional ? '' : schoolYears.find((year) => year.isCurrent)?.id)}
                    error={errors.school_year_id}
                >
                    {optional && <option value="">Not enrolled yet</option>}
                    {schoolYears.map((year) => (
                        <option key={year.id} value={year.id}>
                            SY {year.name}
                            {year.isCurrent ? ' (current)' : ''}
                        </option>
                    ))}
                </SelectField>
            )}
            <SelectField
                id={`${idPrefix}-grade`}
                label="Grade"
                name="grade_level"
                required={!optional}
                value={grade}
                onChange={(event) => setGrade(event.target.value === '' ? '' : Number(event.target.value))}
            >
                <option value="">Choose a grade</option>
                {grades.map((level) => (
                    <option key={level} value={level}>
                        Grade {level}
                    </option>
                ))}
            </SelectField>
            <SelectField
                id={`${idPrefix}-section`}
                key={`${idPrefix}-section-${grade}`}
                label="Section"
                name="section_id"
                required={!optional}
                disabled={grade === ''}
                defaultValue={grade === initialGrade ? defaults?.sectionId : ''}
                error={errors.section_id}
                hint={grade === '' ? 'Choose a grade first' : undefined}
            >
                <option value="">{grade === '' ? '—' : 'Choose a section'}</option>
                {sectionsForGrade.map((section) => (
                    <option key={section.id} value={section.id}>
                        {section.name}
                    </option>
                ))}
            </SelectField>
            <SelectField id={`${idPrefix}-status`} label="Status" name="status" defaultValue={defaults?.status ?? 'enrolled'} error={errors.status}>
                {statuses.map((status) => (
                    <option key={status.value} value={status.value}>
                        {status.label}
                    </option>
                ))}
            </SelectField>
        </div>
    );
}
