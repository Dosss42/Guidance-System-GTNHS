import { AuditTable } from '@/components/audit-table';
import { buttonClasses, Card } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import type { AuditEntry, Paginated } from '@/types';
import { Link } from '@inertiajs/react';

const filters = [
    { value: 'all', label: 'All events' },
    { value: 'sign-in', label: 'Sign-in' },
    { value: 'accounts', label: 'Account changes' },
];

export default function AuditLogIndex({ logs, filter }: { logs: Paginated<AuditEntry>; filter: string }) {
    return (
        <AdminLayout title="Audit log">
            <div className="flex flex-col gap-0.5">
                <span className="mono-label">Administration · Audit log</span>
                <h1 className="text-[32px] font-bold tracking-[-0.015em]">Technical audit log</h1>
                <p className="text-ink-2">Read-only. Entries can't be edited or deleted. Record contents are never shown here.</p>
            </div>

            <Card
                title={`${logs.total} ${logs.total === 1 ? 'entry' : 'entries'}`}
                aside={
                    <nav aria-label="Filter by event" className="flex flex-wrap gap-1">
                        {filters.map((option) => (
                            <Link
                                key={option.value}
                                href={option.value === 'all' ? '/admin/audit-logs' : `/admin/audit-logs?event=${option.value}`}
                                aria-current={filter === option.value ? 'page' : undefined}
                                preserveScroll
                                className={`inline-flex min-h-9 items-center rounded px-3 text-[13px] font-semibold ${filter === option.value ? 'bg-ink text-surface' : 'text-ink-2 hover:bg-sunk hover:text-ink'}`}
                            >
                                {option.label}
                            </Link>
                        ))}
                    </nav>
                }
            >
                <AuditTable entries={logs.data} />

                {logs.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-rule px-5 py-3 text-[13px] text-ink-2">
                        <span>
                            Showing {logs.from}–{logs.to} of {logs.total}
                        </span>
                        <div className="flex gap-2">
                            {logs.prev_page_url ? (
                                <Link href={logs.prev_page_url} className={buttonClasses.quiet} preserveScroll>
                                    Newer
                                </Link>
                            ) : null}
                            {logs.next_page_url ? (
                                <Link href={logs.next_page_url} className={buttonClasses.quiet} preserveScroll>
                                    Older
                                </Link>
                            ) : null}
                        </div>
                    </div>
                )}
            </Card>
        </AdminLayout>
    );
}
