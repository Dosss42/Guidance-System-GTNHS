import { DocumentList } from '@/components/document-list';
import { buttonClasses, Card, FilterTabs, FlashStatus, Pagination } from '@/components/ui';
import CounselorLayout from '@/layouts/counselor-layout';
import type { DocumentFile, Option, Paginated } from '@/types';
import { Link } from '@inertiajs/react';

export default function DocumentsIndex({ documents, category, categories }: { documents: Paginated<DocumentFile>; category: string; categories: Option[] }) {
    return (
        <CounselorLayout title="Documents">
            <div className="flex flex-wrap items-end justify-between gap-4">
                <div className="flex flex-col gap-0.5">
                    <span className="mono-label">Records · Documents</span>
                    <h1 className="font-display text-[40px] leading-[1.1] font-medium tracking-[-0.01em]">Documents</h1>
                    <p className="max-w-3xl text-ink-2">
                        Files are kept in private storage, never at a public link. Every download is recorded in the activity log under your name.
                    </p>
                </div>
                <Link href="/counselor/documents/create" className={buttonClasses.primary}>
                    Upload document
                </Link>
            </div>

            <FlashStatus />

            <Card title={`${documents.total} ${documents.total === 1 ? 'document' : 'documents'}`}>
                <div className="border-b border-rule px-4 py-2.5">
                    <FilterTabs
                        label="Filter by type"
                        options={[{ value: 'all', label: 'All types' }, ...categories]}
                        current={category}
                        href={(value) => (value === 'all' ? '/counselor/documents' : `/counselor/documents?category=${value}`)}
                    />
                </div>
                <DocumentList documents={documents.data} showStudent empty="No documents uploaded yet." />
                <Pagination page={documents} noun={['Newer', 'Older']} />
            </Card>
        </CounselorLayout>
    );
}
