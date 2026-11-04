import { buttonClasses, Card, EmptyState, FlashStatus, formatDateTime, Stamp } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import { Form } from '@inertiajs/react';

interface Backup {
    name: string;
    size: number;
    sizeLabel: string;
    createdAt: string;
    verification: { ok: boolean; problems: string[]; verifiedAt: string; tableCounts: Record<string, number> | null } | null;
}

interface Props {
    backups: Backup[];
    passwordConfigured: boolean;
    keep: number;
    lastFailure: { description: string; created_at: string } | null;
}

export default function BackupsIndex({ backups, passwordConfigured, keep, lastFailure }: Props) {
    return (
        <AdminLayout title="Backup and recovery">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Administration · Backups</span>
                    <h1 className="text-[32px] font-bold tracking-[-0.015em]">Backup and recovery</h1>
                    <p className="max-w-3xl text-ink-2">
                        Each backup holds the database and the private documents. Every file inside is AES-256 encrypted, and a checksum list shows whether
                        anything has changed. A backup runs every night at 2:00 AM and is checked straight away. The {keep} newest are kept.
                    </p>
                </div>
                {passwordConfigured && (
                    <Form action="/admin/backups" method="post" options={{ preserveScroll: true }}>
                        {({ processing }) => (
                            <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                {processing ? 'Backing up…' : 'Back up now'}
                            </button>
                        )}
                    </Form>
                )}
            </div>

            <FlashStatus />

            {!passwordConfigured && (
                <p role="alert" className="rounded border-[1.5px] border-brick bg-brick-soft px-4 py-3 text-sm">
                    Backups are switched off because <span className="font-mono">BACKUP_PASSWORD</span> is not set (at least 16 characters) in the server's{' '}
                    <span className="font-mono">.env</span> file. Set it, store it somewhere safe away from the server, and reload this page.
                </p>
            )}

            {lastFailure && (
                <p className="rounded border-[1.5px] border-ochre bg-ochre-soft px-4 py-3 text-sm">
                    Last problem, {formatDateTime(lastFailure.created_at)}: {lastFailure.description}
                </p>
            )}

            <Card title={`${backups.length} ${backups.length === 1 ? 'backup' : 'backups'} on this server`}>
                {backups.length === 0 ? (
                    <EmptyState code="BK" heading="No backups yet." body="Run one now, or wait for the nightly backup." />
                ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[720px] border-collapse text-sm">
                            <thead>
                                <tr className="text-left">
                                    {['Archive', 'Created', 'Size', 'Last check', ''].map((heading) => (
                                        <th key={heading} scope="col" className="mono-label border-b border-rule px-3 py-2.5 first:pl-5 last:pr-5">
                                            {heading || <span className="sr-only">Actions</span>}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {backups.map((backup) => (
                                    <tr key={backup.name} className="border-b border-rule align-top last:border-b-0">
                                        <td className="px-3 py-3 pl-5 font-mono text-[13px] break-all">{backup.name}</td>
                                        <td className="px-3 py-3 whitespace-nowrap">{formatDateTime(backup.createdAt)}</td>
                                        <td className="px-3 py-3 font-mono text-[13px]">{backup.sizeLabel}</td>
                                        <td className="px-3 py-3">
                                            {backup.verification === null ? (
                                                <Stamp tone="ochre">Not checked</Stamp>
                                            ) : backup.verification.ok ? (
                                                <>
                                                    <Stamp tone="filed">Verified</Stamp>
                                                    <span className="mt-1 block text-xs text-ink-2">{formatDateTime(backup.verification.verifiedAt)}</span>
                                                </>
                                            ) : (
                                                <>
                                                    <Stamp tone="brick">Failed</Stamp>
                                                    <ul className="mt-1 list-disc pl-4 text-xs text-brick">
                                                        {backup.verification.problems.map((problem) => (
                                                            <li key={problem}>{problem}</li>
                                                        ))}
                                                    </ul>
                                                </>
                                            )}
                                        </td>
                                        <td className="px-3 py-3 pr-5 text-right">
                                            <Form action={`/admin/backups/${backup.name}`} method="patch" options={{ preserveScroll: true }}>
                                                {({ processing }) => (
                                                    <button type="submit" className={buttonClasses.link} disabled={processing}>
                                                        {processing ? 'Checking…' : 'Check again'}
                                                    </button>
                                                )}
                                            </Form>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </Card>

            <Card title="Restoring a backup">
                <div className="flex flex-col gap-2 px-5 py-4 text-sm">
                    <p>Restoring replaces every record, so it is done from the server's command line on purpose, never from this page:</p>
                    <ol className="list-decimal space-y-1 pl-5 font-mono text-[13px]">
                        <li>php artisan down</li>
                        <li>php artisan guidance:backup-verify &lt;archive name&gt;</li>
                        <li>php artisan guidance:restore &lt;archive name&gt;</li>
                        <li>php artisan up</li>
                    </ol>
                    <p className="text-ink-2">
                        Before restoring, the system saves the current state as an encrypted "pre-restore" backup. Keep copies of these archives somewhere other than
                        this server, such as an approved external drive. Keep the backup password separately.
                    </p>
                </div>
            </Card>
        </AdminLayout>
    );
}
