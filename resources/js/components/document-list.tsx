import { formatDateTime } from '@/components/ui';
import type { DocumentFile } from '@/types';

/**
 * Documents with an audited download link. A plain <a> is used on purpose:
 * a download is a file response, not an Inertia page visit.
 */
export function DocumentList({ documents, showStudent = false, empty = 'No documents.' }: { documents: DocumentFile[]; showStudent?: boolean; empty?: string }) {
    if (documents.length === 0) {
        return <p className="px-5 py-4 text-sm text-ink-2">{empty}</p>;
    }

    return (
        <ul>
            {documents.map((document) => (
                <li key={document.id} className="flex items-center gap-3 border-b border-rule px-5 py-3 last:border-b-0">
                    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className="shrink-0 text-ink-2">
                        <rect x="5" y="11" width="14" height="9" rx="1" />
                        <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                    </svg>
                    <div className="min-w-0 flex-1">
                        <a
                            href={`/counselor/documents/${document.id}/download`}
                            className="font-semibold text-green hover:text-ink"
                            aria-label={`Download ${document.title}, ${document.extension}, ${document.size}`}
                        >
                            {document.title}
                        </a>
                        <span className="block text-[13px] text-ink-2">
                            {document.category} · {document.extension} · {document.size}
                            {showStudent && document.student && ` · ${document.student.name}`}
                            {document.caseReference && ` · ${document.caseReference}`}
                        </span>
                        <span className="block text-xs text-ink-2">
                            Private · {formatDateTime(document.uploadedAt)}
                            {document.uploadedBy && ` · ${document.uploadedBy}`}
                        </span>
                    </div>
                </li>
            ))}
        </ul>
    );
}
