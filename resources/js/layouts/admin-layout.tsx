import { EnvironmentBanner } from '@/components/ui';
import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

const navigation: { code: string; label: string; href?: string }[] = [
    { code: 'OV', label: 'Overview', href: '/admin' },
    { code: 'AC', label: 'Accounts', href: '/admin/users' },
    { code: 'AL', label: 'Audit log', href: '/admin/audit-logs' },
    { code: 'CF', label: 'Settings' },
    { code: 'BK', label: 'Backup & recovery' },
];

/**
 * The administrator workspace uses a dark band so it never looks like the
 * counselor desk. It never shows student records or counseling content.
 */
export default function AdminLayout({ title, children }: { title: string; children: ReactNode }) {
    const { auth } = usePage().props;
    const currentPath = usePage().url.split('?')[0];

    return (
        <div className="min-h-screen">
            <Head title={title} />
            <EnvironmentBanner />
            <div className="flex flex-wrap">
                <nav aria-label="Administration" className="flex max-w-full flex-[1_1_236px] flex-col gap-5 bg-admin-band px-3.5 pt-5 pb-7 text-admin-band-ink">
                    <div className="flex items-center gap-2.5 px-1.5">
                        <span aria-hidden="true" className="flex size-[38px] shrink-0 items-center justify-center rounded-sm border-[1.5px] border-current font-mono text-xs font-semibold">
                            SA
                        </span>
                        <div className="flex flex-col leading-tight">
                            <span className="text-sm font-bold">Administration</span>
                            <span className="text-xs text-admin-band-2">Guidance system · GTNHS</span>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-0.5">
                        {navigation.map((item) => {
                            const isCurrent = item.href !== undefined && currentPath === item.href;
                            const base = 'flex min-h-11 flex-[1_1_190px] items-center gap-2.5 rounded px-2.5 text-sm';
                            const code = (
                                <span
                                    className={`inline-flex h-[22px] w-7 shrink-0 items-center justify-center rounded-sm border font-mono text-[11px] ${isCurrent ? 'border-admin-band-ink bg-admin-band-ink text-admin-band' : 'border-current'}`}
                                >
                                    {item.code}
                                </span>
                            );

                            if (item.href === undefined) {
                                return (
                                    <span key={item.code} className={`${base} text-admin-band-2/70`} aria-disabled="true" title="Coming in a later phase">
                                        {code}
                                        {item.label}
                                        <span className="ml-auto font-mono text-[10px] tracking-wider uppercase">Soon</span>
                                    </span>
                                );
                            }

                            return (
                                <Link
                                    key={item.code}
                                    href={item.href}
                                    aria-current={isCurrent ? 'page' : undefined}
                                    className={`${base} focus-visible:outline-admin-band-ink ${isCurrent ? 'bg-admin-band-on font-semibold text-admin-band-ink' : 'text-admin-band-2 hover:bg-admin-band-on hover:text-admin-band-ink'}`}
                                >
                                    {code}
                                    {item.label}
                                </Link>
                            );
                        })}
                    </div>

                    <p className="rounded border border-dashed border-admin-band-2 px-2.5 py-3 text-xs text-admin-band-2">
                        This workspace doesn't show student records, case files or counseling notes.
                    </p>

                    <div className="mt-auto flex items-center gap-2.5 border-t border-admin-band-on px-2.5 pt-3">
                        <span aria-hidden="true" className="flex size-8 shrink-0 items-center justify-center rounded-full bg-admin-band-on font-mono text-xs font-semibold">
                            {auth.user?.initials}
                        </span>
                        <div className="flex min-w-0 flex-col leading-snug">
                            <span className="truncate text-[13px] font-semibold">{auth.user?.name}</span>
                            <span className="text-xs text-admin-band-2">{auth.user?.role.label}</span>
                        </div>
                        <Link href="/logout" method="post" as="button" className="ml-auto min-h-9 cursor-pointer text-[13px] font-semibold text-admin-band-ink hover:underline">
                            Sign out
                        </Link>
                    </div>
                </nav>

                <main className="flex min-w-0 flex-[999_1_560px] flex-col gap-6 px-4 pt-6 pb-12 sm:px-6 lg:px-10">{children}</main>
            </div>
        </div>
    );
}
