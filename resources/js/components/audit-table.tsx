import type { AuditEntry } from '@/types';

const timestamp = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
});

const actionTone: Record<string, string> = {
    login_failed: 'text-ochre',
    account_deactivated: 'text-brick',
};

/** Read-only audit entries. Shows what happened and to which record, never record contents. */
export function AuditTable({ entries }: { entries: AuditEntry[] }) {
    if (entries.length === 0) {
        return <p className="px-5 py-6 text-sm text-ink-2">No activity recorded yet.</p>;
    }

    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[760px] border-collapse font-mono text-[13px]">
                <thead>
                    <tr className="text-left">
                        {['Timestamp', 'User', 'Action', 'Module', 'Record', 'Note', 'IP'].map((heading) => (
                            <th key={heading} scope="col" className="mono-label border-b border-rule px-3 py-2.5 first:pl-5 last:pr-5">
                                {heading}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {entries.map((entry) => (
                        <tr key={entry.id} className="border-b border-rule last:border-b-0">
                            <td className="px-3 py-3 pl-5 whitespace-nowrap">{timestamp.format(new Date(entry.at)).replace(',', '')}</td>
                            <td className="px-3 py-3">{entry.user ?? '—'}</td>
                            <td className={`px-3 py-3 font-semibold whitespace-nowrap ${actionTone[entry.action] ?? ''}`}>{entry.actionLabel}</td>
                            <td className="px-3 py-3">{entry.module}</td>
                            <td className="px-3 py-3">{entry.record ?? '—'}</td>
                            <td className="px-3 py-3 font-sans text-[13px] text-ink-2">{entry.description ?? '—'}</td>
                            <td className="px-3 py-3 pr-5">{entry.ip ?? '—'}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
