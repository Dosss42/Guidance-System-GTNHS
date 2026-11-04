import { buttonClasses, FieldError, Seal, TextField } from '@/components/ui';
import { Form, Head, usePage } from '@inertiajs/react';
import { useState } from 'react';

const folderTabs = ['ST', 'AP', 'RF', 'GC', 'CS', 'FU'];

export default function Login() {
    const { flash } = usePage().props;
    const [passwordShown, setPasswordShown] = useState(false);

    return (
        <div className="flex min-h-screen flex-wrap">
            <Head title="Sign in" />

            <section className="flex flex-[1_1_420px] flex-col gap-8 bg-band p-7 text-band-ink sm:p-12 lg:p-16">
                <div className="flex items-center gap-3">
                    <Seal size={64} ring />
                    <div className="flex flex-col leading-snug">
                        <span className="font-bold">Guidance Office</span>
                        <span className="text-[13px] text-band-2">General Tinio National High School</span>
                    </div>
                </div>

                <div className="mt-auto flex max-w-[520px] flex-col gap-4">
                    <span className="font-mono text-xs tracking-[0.12em] text-band-2">GUIDANCE RECORDS · STAFF ONLY</span>
                    <h1 className="font-display text-4xl leading-[1.05] font-normal tracking-[-0.015em] lg:text-6xl">
                        Every folder in the cabinet, now in one place.
                    </h1>
                    <p className="max-w-[440px] text-base text-band-2">
                        Students, appointments, referrals, cases and follow-ups are shared by the guidance counselors. Every change is signed with your own account.
                    </p>
                </div>

                <div aria-hidden="true" className="flex items-end gap-1.5">
                    {folderTabs.map((tab) => (
                        <span
                            key={tab}
                            className={`rounded-t border border-b-0 border-current px-2.5 pb-1 font-mono text-[11px] ${tab === 'GC' ? 'border-t-[3px] border-t-gold pt-2' : 'pt-1.5 opacity-50'}`}
                        >
                            {tab}
                        </span>
                    ))}
                    <span className="flex-1 border-b border-current opacity-50" />
                </div>
            </section>

            <section className="flex flex-[1_1_480px] items-center justify-center px-4 py-10 sm:py-16">
                <div className="ruled-card relative w-full max-w-[440px] rounded border border-rule bg-surface bg-[position:0_76px] py-9 pr-8 pl-12">
                    <div aria-hidden="true" className="absolute inset-y-0 left-[30px] w-[1.5px] bg-margin/70" />
                    <h2 className="text-2xl font-bold tracking-[-0.01em]">Sign in</h2>
                    <p className="mt-1 mb-7 text-sm text-ink-2">Use the account the System Administrator issued to you.</p>

                    {flash.status && (
                        <p role="status" className="mb-5 rounded border-[1.5px] border-ochre bg-ochre-soft px-3 py-2.5 text-sm text-ink">
                            {flash.status}
                        </p>
                    )}

                    <Form action="/login" method="post" resetOnError={['password']} className="flex flex-col gap-5">
                        {({ errors, processing }) => (
                            <>
                                <TextField label="School email" name="email" type="email" autoComplete="username" required autoFocus error={errors.email} />

                                <TextField
                                    label="Password"
                                    name="password"
                                    type={passwordShown ? 'text' : 'password'}
                                    autoComplete="current-password"
                                    required
                                    error={errors.password}
                                    trailing={
                                        <button
                                            type="button"
                                            onClick={() => setPasswordShown((shown) => !shown)}
                                            aria-pressed={passwordShown}
                                            aria-controls="password"
                                            className={buttonClasses.link}
                                        >
                                            {passwordShown ? 'Hide' : 'Show'}
                                        </button>
                                    }
                                />

                                <label className="flex min-h-11 cursor-pointer items-center gap-2.5 text-sm">
                                    <input type="checkbox" name="remember" value="1" className="size-[18px] accent-green" />
                                    Keep me signed in on this computer
                                </label>

                                <button type="submit" disabled={processing} className={`${buttonClasses.primary} min-h-12 text-[15px]`}>
                                    {processing ? 'Signing in…' : 'Sign in'}
                                </button>

                                {errors.remember && <FieldError>{errors.remember}</FieldError>}

                                <p className="text-[13px] text-ink-2">
                                    Forgot your password? Ask the System Administrator to reset it. Teachers, students and parents don't have accounts. Referrals reach the
                                    office through the usual school process.
                                </p>
                            </>
                        )}
                    </Form>
                </div>
            </section>
        </div>
    );
}
