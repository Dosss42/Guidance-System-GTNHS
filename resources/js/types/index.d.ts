import '@inertiajs/core';

export type RoleSlug = 'administrator' | 'guidance_counselor';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    initials: string;
    role: {
        slug: RoleSlug | null;
        label: string | null;
    };
}

export interface SharedData {
    auth: { user: AuthUser | null };
    flash: { status: string | null };
    environment: string;
}

export interface AuditEntry {
    id: number;
    at: string;
    user: string | null;
    action: string;
    actionLabel: string;
    module: string;
    record: string | null;
    description: string | null;
    ip: string | null;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedData;
    }
}
