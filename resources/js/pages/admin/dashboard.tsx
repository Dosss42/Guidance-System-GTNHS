import { AuditTable } from '@/components/audit-table';
import { buttonClasses, Card, FlashStatus } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import type { AuditEntry } from '@/types';
import { Link } from '@inertiajs/react';

interface Stats {
    activeCounselors: number;
    activeAdministrators: number;
    deactivatedAccounts: number;
    failedSignIns: number;
}

export default function AdminDashboard({ stats, recentEvents }: { stats: Stats; recentEvents: AuditEntry[] }) {
    const tiles = [
        { label: 'active counselor accounts', value: stats.activeCounselors, href: '/admin/users' },
        { label: 'active administrator accounts', value: stats.activeAdministrators, href: '/admin/users' },
        { label: 'deactivated accounts', value: stats.deactivatedAccounts, href: '/admin/users' },
        { label: 'failed sign-ins in the last 24 hours', value: stats.failedSignIns, href: '/admin/audit-logs?event=sign-in', warn: stats.failedSignIns > 0 },
    ];

    return (
        <AdminLayout title="Overview">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Administration · Overview</span>
                    <h1 className="text-[32px] font-bold tracking-[-0.015em]">System overview</h1>
                </div>
                <Link href="/admin/users" className={buttonClasses.primary}>
                    Manage accounts
                </Link>
            </div>

            <FlashStatus />

            <section aria-label="Account summary" className="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] rounded border border-rule bg-surface">
                {tiles.map((tile) => (
                    <Link key={tile.label} href={tile.href} className="flex flex-col gap-0.5 border-r border-b border-rule px-5 py-4 text-ink last:border-r-0 hover:bg-sunk">
                        <span className={`font-mono text-[30px] leading-tight font-medium ${tile.warn ? 'text-ochre' : ''}`}>{String(tile.value).padStart(2, '0')}</span>
                        <span className="text-[13px] text-ink-2">{tile.label}</span>
                    </Link>
                ))}
            </section>

            <Card
                title="Recent activity"
                aside={
                    <Link href="/admin/audit-logs" className={buttonClasses.link}>
                        Open the full audit log
                    </Link>
                }
            >
                <AuditTable entries={recentEvents} />
            </Card>

            <Card title="Backup and recovery">
                <p className="px-5 py-4 text-sm text-ink-2">
                    Backups aren't set up in the application yet. Until they are, back up the MySQL database through the server. Keep the copies protected,
                    because they contain student information.
                </p>
            </Card>
        </AdminLayout>
    );
}
