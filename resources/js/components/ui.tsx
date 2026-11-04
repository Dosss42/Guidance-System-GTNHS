import { Link, usePage } from '@inertiajs/react';
import type { InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes } from 'react';

/** The GTNHS seal. Decorative wherever the school name is written next to it. */
export function Seal({ size = 40, ring = false }: { size?: number; ring?: boolean }) {
    return (
        <img
            src="/images/GTNHS%20Logo.jpg"
            alt=""
            width={size}
            height={size}
            className={`shrink-0 rounded-full object-cover ${ring ? 'ring-3 ring-sprout' : ''}`}
        />
    );
}

export type StampTone = 'green' | 'ochre' | 'filed' | 'brick' | 'neutral';

const stampTones: Record<StampTone, string> = {
    green: 'text-green',
    ochre: 'text-ochre',
    filed: 'text-filed',
    brick: 'text-brick',
    neutral: 'text-ink-2',
};

/** Outlined status label, like an ink stamp. Always carries a written word. */
export function Stamp({ tone, children }: { tone: StampTone; children: ReactNode }) {
    return (
        <span
            className={`inline-flex h-[22px] items-center whitespace-nowrap rounded-sm border-[1.5px] border-current px-[7px] font-mono text-[11px] font-semibold tracking-[0.08em] uppercase ${stampTones[tone]}`}
        >
            {children}
        </span>
    );
}

export const buttonClasses = {
    primary:
        'inline-flex min-h-11 items-center justify-center gap-2 rounded border border-green bg-green px-4 text-sm font-semibold text-green-ink hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60',
    quiet: 'inline-flex min-h-11 items-center justify-center gap-2 rounded border border-rule-2 bg-transparent px-4 text-sm font-semibold text-ink hover:bg-sunk disabled:cursor-not-allowed disabled:opacity-60',
    danger: 'inline-flex min-h-11 items-center justify-center gap-2 rounded border border-brick bg-brick px-4 text-sm font-semibold text-surface hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60',
    link: 'inline-flex min-h-9 items-center px-1.5 text-[13px] font-semibold text-green hover:text-ink',
};

interface TextFieldProps extends InputHTMLAttributes<HTMLInputElement> {
    label: string;
    name: string;
    error?: string;
    hint?: string;
    required?: boolean;
    trailing?: ReactNode;
}

/** A labelled input with helper text and an error shown directly under it. */
export function TextField({ label, name, error, hint, required, trailing, id, ...inputProps }: TextFieldProps) {
    const fieldId = id ?? name;
    const describedBy = [hint ? `${fieldId}-hint` : null, error ? `${fieldId}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <div className="flex items-baseline justify-between gap-2">
                <label htmlFor={fieldId} className="text-sm font-semibold">
                    {label} {required && <span className="font-bold text-brick" aria-hidden="true">*</span>}
                </label>
                {trailing}
            </div>
            <input
                id={fieldId}
                name={name}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                className={`h-11 w-full rounded bg-surface px-3 text-[15px] text-ink outline-offset-0 focus:border-green focus:outline-2 focus:outline-green-soft ${error ? 'border-[1.5px] border-brick' : 'border border-rule-2'}`}
                {...inputProps}
            />
            {hint && !error && (
                <span id={`${fieldId}-hint`} className="text-[13px] text-ink-2">
                    {hint}
                </span>
            )}
            {error && <FieldError id={`${fieldId}-error`}>{error}</FieldError>}
        </div>
    );
}

interface SelectFieldProps extends SelectHTMLAttributes<HTMLSelectElement> {
    label: string;
    name: string;
    error?: string;
    hint?: string;
    required?: boolean;
    children: ReactNode;
}

/** A labelled select with helper text and an error shown directly under it. */
export function SelectField({ label, name, error, hint, required, id, children, ...selectProps }: SelectFieldProps) {
    const fieldId = id ?? name;
    const describedBy = [hint ? `${fieldId}-hint` : null, error ? `${fieldId}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <label htmlFor={fieldId} className="text-sm font-semibold">
                {label} {required && <span className="font-bold text-brick" aria-hidden="true">*</span>}
            </label>
            <select
                id={fieldId}
                name={name}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                className={`h-11 w-full rounded bg-surface px-3 text-[15px] text-ink ${error ? 'border-[1.5px] border-brick' : 'border border-rule-2'}`}
                {...selectProps}
            >
                {children}
            </select>
            {hint && !error && (
                <span id={`${fieldId}-hint`} className="text-[13px] text-ink-2">
                    {hint}
                </span>
            )}
            {error && <FieldError id={`${fieldId}-error`}>{error}</FieldError>}
        </div>
    );
}

/** Stamp colours for enrollment statuses: done → filed, ended → neutral. */
export function enrollmentTone(status: string): StampTone {
    return status === 'enrolled' || status === 'completed' ? 'filed' : 'neutral';
}

export function FieldError({ id, children }: { id?: string; children: ReactNode }) {
    return (
        <span id={id} className="flex items-start gap-1.5 text-[13px] font-semibold text-brick">
            <svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" className="mt-0.5 shrink-0">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7v6M12 16.5v.5" />
            </svg>
            {children}
        </span>
    );
}

/** Shows the latest flash status message from the server. */
export function FlashStatus() {
    const { flash } = usePage().props;

    if (!flash.status) {
        return null;
    }

    return (
        <p role="status" className="rounded border border-green bg-green-soft px-4 py-3 text-sm">
            {flash.status}
        </p>
    );
}

/** Reminds everyone on a non-production build to use synthetic records only. */
export function EnvironmentBanner() {
    const { environment } = usePage().props;

    if (environment === 'production') {
        return null;
    }

    return (
        <div className="border-b border-rule bg-ochre-soft px-4 py-1.5 text-center font-mono text-[11px] tracking-[0.08em] text-ochre print:hidden">
            {environment.toUpperCase()} BUILD · USE SYNTHETIC RECORDS ONLY · NO REAL STUDENT INFORMATION
        </div>
    );
}

export function Card({ title, aside, children, className = '' }: { title?: string; aside?: ReactNode; children: ReactNode; className?: string }) {
    return (
        <section className={`min-w-0 rounded border border-rule bg-surface ${className}`}>
            {title && (
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-rule px-5 py-3.5">
                    <h2 className="text-base font-bold">{title}</h2>
                    {aside}
                </div>
            )}
            {children}
        </section>
    );
}

interface TextAreaFieldProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
    label: string;
    name: string;
    error?: string;
    hint?: string;
    required?: boolean;
}

/** A ruled, index-card style text area with helper text and an inline error. */
export function TextAreaField({ label, name, error, hint, required, id, rows = 4, ...props }: TextAreaFieldProps) {
    const fieldId = id ?? name;
    const describedBy = [hint ? `${fieldId}-hint` : null, error ? `${fieldId}-error` : null].filter(Boolean).join(' ') || undefined;

    return (
        <div className="flex min-w-0 flex-col gap-1.5">
            <label htmlFor={fieldId} className="text-sm font-semibold">
                {label} {required && <span className="font-bold text-brick" aria-hidden="true">*</span>}
            </label>
            <textarea
                id={fieldId}
                name={name}
                rows={rows}
                required={required}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedBy}
                className={`w-full resize-y rounded bg-surface bg-[image:repeating-linear-gradient(to_bottom,transparent_0_27px,var(--card-rule)_27px_28px)] bg-[position:0_4px] bg-local px-3 py-1 text-[15px] leading-7 text-ink focus:border-green focus:outline-2 focus:outline-green-soft ${error ? 'border-[1.5px] border-brick' : 'border border-rule-2'}`}
                {...props}
            />
            {hint && !error && (
                <span id={`${fieldId}-hint`} className="text-[13px] text-ink-2">
                    {hint}
                </span>
            )}
            {error && <FieldError id={`${fieldId}-error`}>{error}</FieldError>}
        </div>
    );
}

/** Previous / next links for a Laravel paginator. */
export function Pagination({ page, noun }: { page: { last_page: number; from: number | null; to: number | null; total: number; prev_page_url: string | null; next_page_url: string | null }; noun?: [string, string] }) {
    if (page.last_page <= 1) {
        return null;
    }

    const [previousLabel, nextLabel] = noun ?? ['Previous', 'Next'];

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-rule px-5 py-3 text-[13px] text-ink-2">
            <span>
                Showing {page.from}–{page.to} of {page.total}
            </span>
            <div className="flex gap-2">
                {page.prev_page_url && (
                    <Link href={page.prev_page_url} className={buttonClasses.quiet} preserveScroll>
                        {previousLabel}
                    </Link>
                )}
                {page.next_page_url && (
                    <Link href={page.next_page_url} className={buttonClasses.quiet} preserveScroll>
                        {nextLabel}
                    </Link>
                )}
            </div>
        </div>
    );
}

/** Filter tabs that link to the same page with a query value. */
export function FilterTabs({ label, options, current, href }: { label: string; options: { value: string; label: string }[]; current: string; href: (value: string) => string }) {
    return (
        <nav aria-label={label} className="flex flex-wrap gap-1">
            {options.map((option) => (
                <Link
                    key={option.value}
                    href={href(option.value)}
                    aria-current={current === option.value ? 'page' : undefined}
                    preserveScroll
                    className={`inline-flex min-h-9 items-center rounded px-3 text-[13px] font-semibold ${current === option.value ? 'bg-ink text-surface' : 'text-ink-2 hover:bg-sunk hover:text-ink'}`}
                >
                    {option.label}
                </Link>
            ))}
        </nav>
    );
}

/** A dashed-code empty state used across lists and panels. */
export function EmptyState({ code, heading, body, children }: { code: string; heading: string; body?: string; children?: ReactNode }) {
    return (
        <div className="flex items-start gap-4 px-5 py-7">
            <span
                aria-hidden="true"
                className="inline-flex h-[22px] w-7 shrink-0 items-center justify-center rounded-sm border border-dashed border-rule-2 font-mono text-[11px] text-ink-2"
            >
                {code}
            </span>
            <div className="flex flex-col gap-1">
                <p className="font-semibold">{heading}</p>
                {body && <p className="max-w-prose text-sm text-ink-2">{body}</p>}
                {children}
            </div>
        </div>
    );
}

const dateTimeFormat = new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
const dateFormat = new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' });
const timeFormat = new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: '2-digit' });

export function formatDateTime(iso: string | null): string {
    return iso ? dateTimeFormat.format(new Date(iso)) : 'Never';
}

/** Accepts "2026-11-04" (local date) or a full ISO timestamp. */
export function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return dateFormat.format(new Date(value.length === 10 ? `${value}T00:00:00` : value));
}

export function formatTime(iso: string): string {
    return timeFormat.format(new Date(iso));
}

/** Stamp tones for every casework status, following the design system. */
export function statusTone(status: string): StampTone {
    switch (status) {
        case 'scheduled':
        case 'open':
        case 'in_progress':
        case 'under_review':
        case 'for_follow_up':
            return 'green';
        case 'received':
        case 'pending':
        case 'rescheduled':
        case 'on_hold':
            return 'ochre';
        case 'completed':
        case 'enrolled':
            return 'filed';
        case 'overdue':
            return 'brick';
        default:
            return 'neutral';
    }
}
