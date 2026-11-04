import { buttonClasses, FieldError } from '@/components/ui';
import type { StudentSummary } from '@/types';
import { router } from '@inertiajs/react';
import { useEffect, useId, useState } from 'react';

interface StudentPickerProps {
    /** The student already chosen, e.g. when the form was opened from a profile. */
    student: StudentSummary | null;
    error?: string;
    required?: boolean;
    /**
     * Props to reload with `?student=` after choosing, for forms whose other
     * options (cases, referrals) depend on the student.
     */
    reloadOnly?: string[];
}

interface LookupResult extends StudentSummary {
    maskedLrn: string;
}

/**
 * Search-as-you-type student chooser. Submits the student id as `student_id`.
 * Results come from the server; LRNs stay masked.
 */
export function StudentPicker({ student, error, required = true, reloadOnly }: StudentPickerProps) {
    const [selected, setSelected] = useState<StudentSummary | null>(student);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<LookupResult[]>([]);
    const [searching, setSearching] = useState(false);
    const inputId = useId();
    const resultsId = useId();

    useEffect(() => setSelected(student), [student?.id]);

    useEffect(() => {
        if (selected !== null || query.trim().length < 2) {
            setResults([]);
            return;
        }

        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setSearching(true);

            try {
                const response = await fetch(`/counselor/student-lookup?q=${encodeURIComponent(query.trim())}`, {
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });

                if (response.ok) {
                    setResults(((await response.json()) as { students: LookupResult[] }).students);
                }
            } catch {
                // Aborted or offline: keep the previous results.
            } finally {
                setSearching(false);
            }
        }, 250);

        return () => {
            controller.abort();
            window.clearTimeout(timer);
        };
    }, [query, selected]);

    const choose = (choice: StudentSummary) => {
        setSelected(choice);
        setQuery('');

        if (reloadOnly) {
            router.reload({ data: { student: choice.id }, only: reloadOnly });
        }
    };

    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <label htmlFor={inputId} className="text-sm font-semibold">
                Student {required && <span className="font-bold text-brick" aria-hidden="true">*</span>}
            </label>
            <input type="hidden" name="student_id" value={selected?.id ?? ''} />

            {selected ? (
                <div className="flex flex-wrap items-center gap-x-3.5 gap-y-2 rounded border border-green bg-green-soft px-3 py-2.5">
                    <span className="font-semibold">{selected.name}</span>
                    <span className="text-[13px] text-ink-2">{selected.detail}</span>
                    <button
                        id={inputId}
                        type="button"
                        className={`${buttonClasses.link} ml-auto`}
                        onClick={() => {
                            setSelected(null);
                            setResults([]);
                        }}
                    >
                        Change student
                    </button>
                </div>
            ) : (
                <div className="relative">
                    <input
                        id={inputId}
                        type="search"
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Type a last name, first name or LRN"
                        autoComplete="off"
                        aria-invalid={error ? true : undefined}
                        aria-controls={resultsId}
                        className={`h-11 w-full rounded bg-surface px-3 text-[15px] focus:border-green focus:outline-2 focus:outline-green-soft ${error ? 'border-[1.5px] border-brick' : 'border border-rule-2'}`}
                    />
                    <div id={resultsId} aria-live="polite">
                        {query.trim().length >= 2 && (
                            <ul className="mt-1 flex flex-col overflow-hidden rounded border border-rule bg-surface">
                                {results.map((result) => (
                                    <li key={result.id} className="border-b border-rule last:border-b-0">
                                        <button
                                            type="button"
                                            onClick={() => choose(result)}
                                            className="flex min-h-11 w-full flex-wrap items-center gap-x-3 px-3 py-2 text-left hover:bg-sunk"
                                        >
                                            <span className="font-semibold">{result.name}</span>
                                            <span className="text-[13px] text-ink-2">{result.detail}</span>
                                            <span className="ml-auto font-mono text-xs text-ink-2">{result.maskedLrn}</span>
                                        </button>
                                    </li>
                                ))}
                                {results.length === 0 && (
                                    <li className="px-3 py-2.5 text-sm text-ink-2">{searching ? 'Searching…' : 'No matching students. Add the student record first.'}</li>
                                )}
                            </ul>
                        )}
                    </div>
                </div>
            )}

            {error && <FieldError>{error}</FieldError>}
        </div>
    );
}
