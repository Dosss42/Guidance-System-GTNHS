import { buttonClasses, Card, FlashStatus, formatDateTime, TextField } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import CounselorLayout from '@/layouts/counselor-layout';
import { Form, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';

interface Props {
    account: { name: string; email: string; role: string | null; lastLoginAt: string | null };
    otherSessions: number;
}

export default function Account({ account, otherSessions }: Props) {
    const { auth } = usePage().props;
    const Layout = ({ children }: { children: ReactNode }) =>
        auth.user?.role.slug === 'administrator' ? <AdminLayout title="My account">{children}</AdminLayout> : <CounselorLayout title="My account">{children}</CounselorLayout>;

    return (
        <Layout>
            <div className="flex flex-col gap-0.5">
                <span className="mono-label">Account</span>
                <h1 className="text-[32px] font-bold tracking-[-0.015em]">My account</h1>
            </div>

            <FlashStatus />

            <div className="flex flex-wrap items-start gap-6">
                <Card title="Profile" className="flex-[1_1_320px]">
                    <dl className="grid grid-cols-[120px_minmax(0,1fr)] gap-x-4 gap-y-2 px-5 py-4 text-sm">
                        <dt className="text-ink-2">Name</dt>
                        <dd className="font-semibold">{account.name}</dd>
                        <dt className="text-ink-2">Email</dt>
                        <dd className="font-mono text-[13px]">{account.email}</dd>
                        <dt className="text-ink-2">Role</dt>
                        <dd>{account.role}</dd>
                        <dt className="text-ink-2">Last sign-in</dt>
                        <dd>{formatDateTime(account.lastLoginAt)}</dd>
                    </dl>
                    <p className="border-t border-rule px-5 py-3 text-xs text-ink-2">To change your name, email or role, ask the System Administrator.</p>
                </Card>

                <Card title="Change password" className="flex-[1_1_380px]">
                    <Form action="/account/password" method="put" resetOnSuccess resetOnError className="flex flex-col gap-4 px-5 py-4">
                        {({ errors, processing }) => (
                            <>
                                <TextField label="Current password" name="current_password" type="password" autoComplete="current-password" required error={errors.current_password} />
                                <TextField
                                    label="New password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    required
                                    error={errors.password}
                                    hint="At least 10 characters, with upper and lower case letters and a number."
                                />
                                <TextField label="Confirm new password" name="password_confirmation" type="password" autoComplete="new-password" required />
                                <p className="text-[13px] text-ink-2">
                                    {otherSessions > 0
                                        ? `You are also signed in on ${otherSessions} other ${otherSessions === 1 ? 'device' : 'devices'}. Changing your password signs them out.`
                                        : 'Changing your password signs out any other device using your account.'}
                                </p>
                                <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                    {processing ? 'Saving…' : 'Change password'}
                                </button>
                            </>
                        )}
                    </Form>
                </Card>
            </div>
        </Layout>
    );
}
