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

export interface SchoolYearOption {
    id: number;
    name: string;
    isCurrent: boolean;
}

export interface SectionOption {
    id: number;
    gradeLevel: number;
    name: string;
}

export interface StatusOption {
    value: string;
    label: string;
}

export interface Enrollment {
    id: number;
    schoolYearId: number;
    schoolYear: string;
    isCurrentYear: boolean;
    sectionId: number;
    gradeLevel: number;
    section: string;
    status: string;
    statusLabel: string;
    updatedBy?: string | null;
}

export interface StudentIdentity {
    id: number;
    lrn: string;
    firstName: string;
    middleName: string | null;
    lastName: string;
    suffix: string | null;
    birthDate: string | null;
    filingName: string;
}

export interface StudentSummary {
    id: number;
    name: string;
    detail: string;
}

export interface Option {
    value: string;
    label: string;
}

export interface Signature {
    createdBy: string | null;
    updatedBy: string | null;
    createdAt: string | null;
    updatedAt: string | null;
}

export interface Appointment extends Signature {
    id: number;
    scheduledAt: string;
    scheduledAtLocal: string;
    durationMinutes: number;
    purpose: string;
    purposeLabel: string;
    status: string;
    statusLabel: string;
    remarks: string | null;
    student: StudentSummary | null;
    caseId: number | null;
    caseReference: string | null;
}

export interface DocumentFile {
    id: number;
    title: string;
    category: string;
    originalName: string;
    size: string;
    extension: string;
    uploadedAt: string | null;
    uploadedBy: string | null;
    student: StudentSummary | null;
    caseId: number | null;
    caseReference: string | null;
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
