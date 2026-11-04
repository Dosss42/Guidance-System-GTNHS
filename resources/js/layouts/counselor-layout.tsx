import { EnvironmentBanner, Seal } from '@/components/ui';
import { Head, Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface NavItem {
    code: string;
    label: string;
    href?: string;
}

/**
 * Modules without an `href` are not built yet (Phase 3–5). They stay visible
 * so counselors can see what is coming, but they are not links.
 */
const navigation: { group: string; items: NavItem[] }[] = [
    {
        group: 'Desk',
        items: [
            { code: 'TD', label: 'Today', href: '/counselor' },
            { code: 'AP', label: 'Appointments' },
            { code: 'FU', label: 'Follow-ups' },
        ],
    },
    {
        group: 'Records',
        items: [
            { code: 'ST', label: 'Students' },
            { code: 'DC', label: 'Documents' },
            { code: 'PG', label: 'Parent / guardian' },
        ],
    },
    {
        group: 'Casework',
        items: [
            { code: 'RF', label: 'Referrals' },
            { code: 'GC', label: 'Guidance cases' },
            { code: 'CS', label: 'Sessions' },
            { code: 'RP', label: 'Reports' },
        ],
    },
];

export default function CounselorLayout({ title, children }: { title: string; children: ReactNode }) {
    const { auth } = usePage().props;
    const currentUrl = usePage().url;

    return (
        <div className="min-h-screen">
            <Head title={title} />
            <EnvironmentBanner />
            <div className="flex flex-wrap">
                <nav aria-label="Main" className="flex max-w-full flex-[1_1_236px] flex-col gap-5 border-r border-rule px-3.5 pt-5 pb-7">
                    <div className="flex items-center gap-2.5 px-1.5">
                        <Seal />
                        <div className="flex flex-col leading-tight">
                            <span className="text-sm font-bold">Guidance Office</span>
                            <span className="text-xs text-ink-2">General Tinio NHS</span>
                        </div>
                    </div>

                    {navigation.map((section) => (
                        <div key={section.group} className="flex flex-col gap-1">
                            <span className="mono-label px-2.5">{section.group}</span>
                            <div className="flex flex-wrap gap-0.5">
                                {section.items.map((item) => (
                                    <NavEntry key={item.code} item={item} isCurrent={item.href !== undefined && currentUrl === item.href} />
                                ))}
                            </div>
                        </div>
                    ))}

                    <div className="mt-auto flex items-center gap-2.5 border-t border-rule px-2.5 pt-3">
                        <span aria-hidden="true" className="flex size-8 shrink-0 items-center justify-center rounded-full bg-green-soft font-mono text-xs font-semibold text-green">
                            {auth.user?.initials}
                        </span>
                        <div className="flex min-w-0 flex-col leading-snug">
                            <span className="truncate text-[13px] font-semibold">{auth.user?.name}</span>
                            <span className="text-xs text-ink-2">{auth.user?.role.label}</span>
                        </div>
                        <Link href="/logout" method="post" as="button" className="ml-auto min-h-9 cursor-pointer text-[13px] font-semibold text-green hover:text-ink">
                            Sign out
                        </Link>
                    </div>
                </nav>

                <main className="flex min-w-0 flex-[999_1_560px] flex-col gap-6 px-4 pt-6 pb-12 sm:px-6 lg:px-10">{children}</main>
            </div>
        </div>
    );
}

function NavEntry({ item, isCurrent }: { item: NavItem; isCurrent: boolean }) {
    const base = 'flex min-h-11 flex-[1_1_190px] items-center gap-2.5 rounded px-2.5 text-sm';
    const code = (filled: boolean) => (
        <span
            className={`inline-flex h-[22px] w-7 shrink-0 items-center justify-center rounded-sm border font-mono text-[11px] font-medium ${filled ? 'border-ink bg-ink text-surface' : 'border-rule-2'}`}
        >
            {item.code}
        </span>
    );

    if (item.href === undefined) {
        return (
            <span className={`${base} text-ink-2/70`} aria-disabled="true" title="Coming in a later phase">
                {code(false)}
                {item.label}
                <span className="ml-auto font-mono text-[10px] tracking-wider uppercase">Soon</span>
            </span>
        );
    }

    return (
        <Link
            href={item.href}
            aria-current={isCurrent ? 'page' : undefined}
            className={`${base} ${isCurrent ? 'bg-surface font-semibold text-ink shadow-[0_0_0_1px_var(--rule)]' : 'text-ink-2 hover:bg-sunk hover:text-ink'}`}
        >
            {code(isCurrent)}
            {item.label}
        </Link>
    );
}
