import { buttonClasses } from '@/components/ui';
import { useEffect, useState } from 'react';

interface UnsealedNotes {
    notes: string;
    nextSteps: string | null;
    unsealedAt: string;
}

const unsealedTime = new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });

/**
 * Counseling notes are not part of the page. They are fetched only when a
 * counselor chooses to unseal them, and the server logs every unseal.
 * Leaving the page or choosing "Seal again" drops them from memory.
 */
export function SealedNotes({ sessionId, signerName }: { sessionId: number; signerName: string }) {
    const [unsealed, setUnsealed] = useState<UnsealedNotes | null>(null);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);

    useEffect(() => () => setUnsealed(null), []);

    const unseal = async () => {
        setLoading(true);
        setFailed(false);

        try {
            const response = await fetch(`/counselor/sessions/${sessionId}/notes`, { headers: { Accept: 'application/json' }, cache: 'no-store' });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            setUnsealed((await response.json()) as UnsealedNotes);
        } catch {
            setFailed(true);
        } finally {
            setLoading(false);
        }
    };

    if (unsealed === null) {
        return (
            <div className="flex flex-wrap items-center gap-x-4 gap-y-3 rounded border-[1.5px] border-dashed border-brick bg-brick-soft p-4">
                <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="shrink-0 text-brick">
                    <rect x="5" y="11" width="14" height="9" rx="1" />
                    <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                </svg>
                <p className="flex-[1_1_240px] text-sm">
                    Session notes are sealed. If you unseal them, that is recorded in the activity log under your name.
                    {failed && <span className="mt-1 block font-semibold text-brick">The notes couldn't be loaded. Check your connection and try again.</span>}
                </p>
                <button type="button" onClick={unseal} disabled={loading} className={`${buttonClasses.quiet} border-brick text-brick`}>
                    {loading ? 'Unsealing…' : 'Unseal notes'}
                </button>
            </div>
        );
    }

    return (
        <div className="ruled-card relative rounded border border-rule bg-surface bg-[position:0_6px] py-1.5 pr-4 pl-10 text-[15px] leading-7">
            <div aria-hidden="true" className="absolute inset-y-0 left-6 w-[1.5px] bg-margin/70" />
            <p className="whitespace-pre-line">{unsealed.notes}</p>
            {unsealed.nextSteps && (
                <p className="whitespace-pre-line">
                    <span className="font-bold">Next steps:</span> {unsealed.nextSteps}
                </p>
            )}
            <div className="mt-1.5 flex flex-wrap justify-between gap-2 text-[13px] leading-normal text-ink-2">
                <span>
                    Unsealed by {signerName} · {unsealedTime.format(new Date(unsealed.unsealedAt))} · logged
                </span>
                <button type="button" onClick={() => setUnsealed(null)} className={buttonClasses.link}>
                    Seal again
                </button>
            </div>
        </div>
    );
}
