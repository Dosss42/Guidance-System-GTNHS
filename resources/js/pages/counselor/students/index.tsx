import { buttonClasses, Card, enrollmentTone, FlashStatus, formatDateTime, Stamp } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { Enrollment, Paginated } from '@/types';
import { Form, Link } from '@inertiajs/react';

interface StudentRow {
    id: number;
    name: string;
    maskedLrn: string;
    enrollment: Enrollment | null;
    updatedBy: string | null;
    updatedAt: string | null;
}

interface Props {
    students: Paginated<StudentRow>;
    filters: { q: string; year: string; grade: string };
    schoolYears: { id: number; name: string; isCurrent: boolean }[];
    gradeLevels: number[];
}

export default function StudentsIndex({ students, filters, schoolYears, gradeLevels }: Props) {
    const isFiltered = filters.q !== '' || filters.grade !== '';

    return (
        <CounselorLayout title="Students">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Records · Students</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Students</h1>
                </div>
                <Link href="/counselor/students/create" className={buttonClasses.primary}>
                    <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Add student
                </Link>
            </div>

            <FlashStatus />

            <Form action="/counselor/students" method="get" options={{ preserveState: true }} className="flex flex-wrap items-end gap-3 rounded border border-rule bg-surface p-4">
                <div className="flex min-w-[240px] flex-[3_1_320px] flex-col gap-1.5">
                    <label htmlFor="q" className="text-sm font-semibold">
                        Find a student
                    </label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        defaultValue={filters.q}
                        placeholder="Last name, first name or LRN"
                        className="h-11 w-full rounded border border-rule-2 bg-surface px-3 text-[15px] focus:border-green focus:outline-2 focus:outline-green-soft"
                    />
                </div>
                <div className="flex min-w-[160px] flex-[1_1_180px] flex-col gap-1.5">
                    <label htmlFor="year" className="text-sm font-semibold">
                        School year
                    </label>
                    <select id="year" name="year" defaultValue={filters.year} className="h-11 rounded border border-rule-2 bg-surface px-3 text-[15px]">
                        {schoolYears.map((year) => (
                            <option key={year.id} value={year.id}>
                                SY {year.name}
                                {year.isCurrent ? ' (current)' : ''}
                            </option>
                        ))}
                        <option value="all">All years</option>
                    </select>
                </div>
                <div className="flex min-w-[130px] flex-[1_1_140px] flex-col gap-1.5">
                    <label htmlFor="grade" className="text-sm font-semibold">
                        Grade
                    </label>
                    <select id="grade" name="grade" defaultValue={filters.grade} className="h-11 rounded border border-rule-2 bg-surface px-3 text-[15px]">
                        <option value="">All grades</option>
                        {gradeLevels.map((level) => (
                            <option key={level} value={level}>
                                Grade {level}
                            </option>
                        ))}
                    </select>
                </div>
                <button type="submit" className={buttonClasses.primary}>
                    Search
                </button>
                {isFiltered && (
                    <Link href={`/counselor/students?year=${filters.year}`} className={buttonClasses.quiet}>
                        Clear
                    </Link>
                )}
            </Form>

            <Card title={`${students.total} ${students.total === 1 ? 'student' : 'students'}`}>
                {students.data.length === 0 ? (
                    <div className="flex flex-col gap-1 px-5 py-8">
                        <p className="font-semibold">{isFiltered ? 'No students match this search.' : 'No students in this school year yet.'}</p>
                        <p className="text-sm text-ink-2">
                            {isFiltered ? 'Check the spelling, or search all school years.' : 'Add a student, or pick another school year above.'}
                        </p>
                    </div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[720px] border-collapse text-sm">
                            <thead>
                                <tr className="text-left">
                                    {['Name', 'LRN', 'Grade · section', 'Status', 'Last updated'].map((heading) => (
                                        <th key={heading} scope="col" className="mono-label border-b border-rule px-3 py-2.5 first:pl-5 last:pr-5">
                                            {heading}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {students.data.map((student) => (
                                    <tr key={student.id} className="border-b border-rule last:border-b-0 hover:bg-sunk">
                                        <td className="px-3 py-3 pl-5">
                                            <Link href={`/counselor/students/${student.id}`} className="font-semibold text-ink underline-offset-2 hover:text-green hover:underline">
                                                {student.name}
                                            </Link>
                                        </td>
                                        <td className="px-3 py-3 font-mono text-[13px] tracking-[0.04em] text-ink-2">{student.maskedLrn}</td>
                                        <td className="px-3 py-3">
                                            {student.enrollment ? (
                                                <>
                                                    Grade {student.enrollment.gradeLevel} · {student.enrollment.section}
                                                    {filters.year === 'all' && <span className="block text-xs text-ink-2">SY {student.enrollment.schoolYear}</span>}
                                                </>
                                            ) : (
                                                <span className="text-ink-2">Not enrolled</span>
                                            )}
                                        </td>
                                        <td className="px-3 py-3">
                                            {student.enrollment && <Stamp tone={enrollmentTone(student.enrollment.status)}>{student.enrollment.statusLabel}</Stamp>}
                                        </td>
                                        <td className="px-3 py-3 pr-5 text-[13px] text-ink-2">
                                            {formatDateTime(student.updatedAt)}
                                            {student.updatedBy && <span className="block">by {student.updatedBy}</span>}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {students.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-rule px-5 py-3 text-[13px] text-ink-2">
                        <span>
                            Showing {students.from}–{students.to} of {students.total}
                        </span>
                        <div className="flex gap-2">
                            {students.prev_page_url && (
                                <Link href={students.prev_page_url} className={buttonClasses.quiet}>
                                    Previous
                                </Link>
                            )}
                            {students.next_page_url && (
                                <Link href={students.next_page_url} className={buttonClasses.quiet}>
                                    Next
                                </Link>
                            )}
                        </div>
                    </div>
                )}
            </Card>
        </CounselorLayout>
    );
}
