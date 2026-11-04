import { buttonClasses, TextAreaField, TextField } from '@/components/ui';
import { Form } from '@inertiajs/react';
import { useState } from 'react';

type Mode = 'complete' | 'reschedule' | null;

/**
 * Inline actions for a pending follow-up: record its outcome, move it to a
 * new date (the old entry is kept as "Rescheduled"), or cancel it.
 */
export function FollowUpActions({ followUpId, today }: { followUpId: number; today: string }) {
    const [mode, setMode] = useState<Mode>(null);
    const action = `/counselor/follow-ups/${followUpId}`;

    if (mode === null) {
        return (
            <div className="flex flex-wrap gap-2">
                <button type="button" className={`${buttonClasses.quiet} min-h-9 text-[13px]`} onClick={() => setMode('complete')}>
                    Record outcome
                </button>
                <button type="button" className={`${buttonClasses.quiet} min-h-9 text-[13px]`} onClick={() => setMode('reschedule')}>
                    Reschedule
                </button>
                <Form action={action} method="patch" options={{ preserveScroll: true }}>
                    <input type="hidden" name="status" value="cancelled" />
                    <button type="submit" className={`${buttonClasses.link} min-h-9`}>
                        Cancel follow-up
                    </button>
                </Form>
            </div>
        );
    }

    return (
        <Form action={action} method="patch" options={{ preserveScroll: true }} onSuccess={() => setMode(null)} className="flex flex-col gap-3 rounded border border-rule bg-sunk p-3">
            {({ errors, processing }) => (
                <>
                    {mode === 'complete' ? (
                        <>
                            <input type="hidden" name="status" value="completed" />
                            <TextAreaField id={`outcome-${followUpId}`} label="Outcome" name="outcome" rows={3} required error={errors.outcome} autoFocus />
                        </>
                    ) : (
                        <>
                            <input type="hidden" name="status" value="rescheduled" />
                            <TextField id={`due-${followUpId}`} label="New date" name="due_on" type="date" min={today} required error={errors.due_on} />
                        </>
                    )}
                    <div className="flex flex-wrap justify-end gap-2">
                        <button type="button" className={`${buttonClasses.quiet} min-h-9 text-[13px]`} onClick={() => setMode(null)}>
                            Back
                        </button>
                        <button type="submit" className={`${buttonClasses.primary} min-h-9 text-[13px]`} disabled={processing}>
                            {mode === 'complete' ? 'Mark completed' : 'Move follow-up'}
                        </button>
                    </div>
                </>
            )}
        </Form>
    );
}
