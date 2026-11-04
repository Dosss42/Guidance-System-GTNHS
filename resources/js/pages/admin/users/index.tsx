import { buttonClasses, Card, FieldError, FlashStatus, formatDateTime, Stamp, TextField } from '@/components/ui';
import AdminLayout from '@/layouts/admin-layout';
import type { RoleSlug } from '@/types';
import { Form, router } from '@inertiajs/react';
import { Fragment, useState } from 'react';

interface Account {
    id: number;
    name: string;
    email: string;
    role: RoleSlug | null;
    roleLabel: string | null;
    isActive: boolean;
    lastLoginAt: string | null;
    isCurrentUser: boolean;
}

interface RoleOption {
    value: RoleSlug;
    label: string;
}

type RowMode = { id: number; kind: 'edit' | 'confirm-deactivate' } | null;

export default function UsersIndex({ users, roles }: { users: Account[]; roles: RoleOption[] }) {
    const [creating, setCreating] = useState(false);
    const [rowMode, setRowMode] = useState<RowMode>(null);

    const changeStatus = (account: Account, isActive: boolean) => {
        router.patch(`/admin/users/${account.id}/status`, { is_active: isActive }, { preserveScroll: true, onSuccess: () => setRowMode(null) });
    };

    return (
        <AdminLayout title="User accounts">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Administration · Accounts</span>
                    <h1 className="text-[32px] font-bold tracking-[-0.015em]">User accounts</h1>
                    <p className="text-ink-2">Each person uses their own account. Shared logins are not allowed. Accounts are deactivated, never deleted.</p>
                </div>
                {!creating && (
                    <button type="button" className={buttonClasses.primary} onClick={() => setCreating(true)}>
                        <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Create account
                    </button>
                )}
            </div>

            <FlashStatus />

            {creating && (
                <Card title="New account">
                    <Form action="/admin/users" method="post" resetOnSuccess onSuccess={() => setCreating(false)} className="flex flex-col gap-5 p-5">
                        {({ errors, processing }) => (
                            <>
                                <AccountFields errors={errors} roles={roles} defaults={{ role: 'guidance_counselor' }} passwordRequired />
                                <div className="flex flex-wrap justify-end gap-3 border-t border-rule pt-5">
                                    <button type="button" className={buttonClasses.quiet} onClick={() => setCreating(false)}>
                                        Cancel
                                    </button>
                                    <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                        {processing ? 'Creating…' : 'Create account'}
                                    </button>
                                </div>
                            </>
                        )}
                    </Form>
                </Card>
            )}

            <Card title={`${users.length} ${users.length === 1 ? 'account' : 'accounts'}`}>
                <div className="overflow-x-auto">
                    <table className="w-full min-w-[760px] border-collapse text-sm">
                        <thead>
                            <tr className="text-left">
                                {['Name', 'Email', 'Role', 'Status', 'Last sign-in'].map((heading) => (
                                    <th key={heading} scope="col" className="mono-label border-b border-rule px-3 py-2.5 first:pl-5">
                                        {heading}
                                    </th>
                                ))}
                                <th scope="col" className="border-b border-rule px-3 py-2.5 pr-5">
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.map((account) => (
                                <Fragment key={account.id}>
                                    <tr className="border-b border-rule">
                                        <td className="px-3 py-3 pl-5 font-semibold">{account.name}</td>
                                        <td className="px-3 py-3 font-mono text-[13px]">{account.email}</td>
                                        <td className="px-3 py-3">{account.roleLabel}</td>
                                        <td className="px-3 py-3">
                                            {account.isActive ? <Stamp tone="filed">Active</Stamp> : <Stamp tone="neutral">Deactivated</Stamp>}
                                        </td>
                                        <td className="px-3 py-3 font-mono text-[13px]">{formatDateTime(account.lastLoginAt)}</td>
                                        <td className="px-3 py-3 pr-5 text-right whitespace-nowrap">
                                            <button type="button" className={buttonClasses.link} onClick={() => setRowMode({ id: account.id, kind: 'edit' })}>
                                                Edit
                                            </button>
                                            {account.isCurrentUser ? (
                                                <span className="px-1.5 text-[13px] text-ink-2">You</span>
                                            ) : account.isActive ? (
                                                <button
                                                    type="button"
                                                    className={`${buttonClasses.link} text-brick`}
                                                    onClick={() => setRowMode({ id: account.id, kind: 'confirm-deactivate' })}
                                                >
                                                    Deactivate
                                                </button>
                                            ) : (
                                                <button type="button" className={buttonClasses.link} onClick={() => changeStatus(account, true)}>
                                                    Reactivate
                                                </button>
                                            )}
                                        </td>
                                    </tr>

                                    {rowMode?.id === account.id && rowMode.kind === 'confirm-deactivate' && (
                                        <tr className="border-b border-rule">
                                            <td colSpan={6} className="px-5 pb-3.5">
                                                <div
                                                    role="alertdialog"
                                                    aria-labelledby={`deactivate-${account.id}`}
                                                    className="flex flex-wrap items-center gap-3 rounded border-[1.5px] border-brick bg-brick-soft px-4 py-3.5"
                                                >
                                                    <p id={`deactivate-${account.id}`} className="flex-[1_1_320px]">
                                                        Deactivate <strong>{account.name}</strong>? They won't be able to sign in. Their records and signed entries stay as they are.
                                                    </p>
                                                    <button type="button" className={buttonClasses.quiet} onClick={() => setRowMode(null)}>
                                                        Keep active
                                                    </button>
                                                    <button type="button" className={buttonClasses.danger} onClick={() => changeStatus(account, false)}>
                                                        Deactivate account
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    )}

                                    {rowMode?.id === account.id && rowMode.kind === 'edit' && (
                                        <tr className="border-b border-rule bg-sunk">
                                            <td colSpan={6} className="px-5 py-4">
                                                <Form
                                                    action={`/admin/users/${account.id}`}
                                                    method="patch"
                                                    options={{ preserveScroll: true }}
                                                    onSuccess={() => setRowMode(null)}
                                                    className="flex flex-col gap-5"
                                                >
                                                    {({ errors, processing }) => (
                                                        <>
                                                            <AccountFields
                                                                errors={errors}
                                                                roles={roles}
                                                                idPrefix={`edit-${account.id}`}
                                                                defaults={account}
                                                                roleLocked={account.isCurrentUser}
                                                            />
                                                            <div className="flex flex-wrap justify-end gap-3">
                                                                <button type="button" className={buttonClasses.quiet} onClick={() => setRowMode(null)}>
                                                                    Cancel
                                                                </button>
                                                                <button type="submit" className={buttonClasses.primary} disabled={processing}>
                                                                    {processing ? 'Saving…' : 'Save changes'}
                                                                </button>
                                                            </div>
                                                        </>
                                                    )}
                                                </Form>
                                            </td>
                                        </tr>
                                    )}
                                </Fragment>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AdminLayout>
    );
}

interface AccountFieldsProps {
    errors: Record<string, string>;
    roles: RoleOption[];
    defaults: { name?: string; email?: string; role?: RoleSlug | null };
    idPrefix?: string;
    passwordRequired?: boolean;
    roleLocked?: boolean;
}

function AccountFields({ errors, roles, defaults, idPrefix = 'new', passwordRequired = false, roleLocked = false }: AccountFieldsProps) {
    const roleId = `${idPrefix}-role`;

    return (
        <div className="grid grid-cols-[repeat(auto-fit,minmax(220px,1fr))] gap-5">
            <TextField id={`${idPrefix}-name`} label="Full name" name="name" defaultValue={defaults.name} required error={errors.name} autoComplete="off" />
            <TextField id={`${idPrefix}-email`} label="School email" name="email" type="email" defaultValue={defaults.email} required error={errors.email} autoComplete="off" />

            <div className="flex min-w-0 flex-col gap-1.5">
                <label htmlFor={roleId} className="text-sm font-semibold">
                    Role <span className="font-bold text-brick" aria-hidden="true">*</span>
                </label>
                {roleLocked ? (
                    <>
                        <input type="hidden" name="role" value={defaults.role ?? ''} />
                        <p id={roleId} className="flex h-11 items-center text-[15px]">
                            {roles.find((role) => role.value === defaults.role)?.label}
                        </p>
                        <span className="text-[13px] text-ink-2">You can't change your own role.</span>
                    </>
                ) : (
                    <select
                        id={roleId}
                        name="role"
                        defaultValue={defaults.role ?? undefined}
                        aria-invalid={errors.role ? true : undefined}
                        className="h-11 w-full rounded border border-rule-2 bg-surface px-3 text-[15px] text-ink"
                    >
                        {roles.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </select>
                )}
                {errors.role && <FieldError>{errors.role}</FieldError>}
            </div>

            <TextField
                id={`${idPrefix}-password`}
                label={passwordRequired ? 'Initial password' : 'New password'}
                name="password"
                type="password"
                required={passwordRequired}
                autoComplete="new-password"
                hint={passwordRequired ? 'At least 10 characters, with upper and lower case letters and a number.' : 'Leave blank to keep the current password.'}
                error={errors.password}
            />
            <TextField
                id={`${idPrefix}-password-confirmation`}
                label="Confirm password"
                name="password_confirmation"
                type="password"
                required={passwordRequired}
                autoComplete="new-password"
            />
        </div>
    );
}
