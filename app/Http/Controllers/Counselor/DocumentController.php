<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AuditAction;
use App\Enums\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Counselor\Concerns\PresentsCasework;
use App\Http\Requests\Counselor\StoreDocumentRequest;
use App\Models\Document;
use App\Models\GuidanceCase;
use App\Models\Student;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    use PresentsCasework;

    /**
     * Recent documents across all students, optionally by category.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Document::class);

        $category = DocumentCategory::tryFrom((string) $request->query('category'));

        $documents = Document::with(['student.currentEnrollment.section', 'guidanceCase:id,reference', 'uploader:id,name'])
            ->when($category !== null, fn ($query) => $query->where('category', $category))
            ->latest()
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Document $document): array => self::present($document));

        return Inertia::render('counselor/documents/index', [
            'documents' => $documents,
            'category' => $category?->value ?? 'all',
            'categories' => DocumentCategory::options(),
        ]);
    }

    /**
     * The upload form. `?student=` and `?case=` prefill it.
     */
    public function create(Request $request): Response
    {
        Gate::authorize('create', Document::class);

        $case = $request->integer('case') ? GuidanceCase::with('student.currentEnrollment.section')->find($request->integer('case')) : null;
        $student = $case?->student
            ?? ($request->integer('student') ? Student::with('currentEnrollment.section')->find($request->integer('student')) : null);

        return Inertia::render('counselor/documents/create', [
            'student' => $student ? self::studentSummary($student) : null,
            'caseId' => $case?->id,
            'cases' => $student === null ? [] : GuidanceCase::where('student_id', $student->id)
                ->orderByDesc('opened_on')
                ->get(['id', 'reference', 'category'])
                ->map(fn (GuidanceCase $item): array => ['id' => $item->id, 'label' => "{$item->reference} · {$item->category->label()}"]),
            'categories' => DocumentCategory::options(),
            'allowedExtensions' => Document::ALLOWED_EXTENSIONS,
            'maxMegabytes' => Document::MAX_KILOBYTES / 1024,
        ]);
    }

    /**
     * Store the file privately under a generated name and record the upload.
     */
    public function store(StoreDocumentRequest $request, AuditLogger $audit): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store('documents/'.now()->format('Y/m'), Document::DISK);

        $document = Document::create([
            ...$request->safe()->only(['student_id', 'guidance_case_id', 'title', 'category']),
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'sha256' => hash_file('sha256', Storage::disk(Document::DISK)->path($path)),
            'uploaded_by' => $request->user()->id,
        ]);

        $audit->record(AuditAction::DocumentUploaded, 'documents', $document, "{$document->category->label()} · {$document->humanSize()}");

        if ($document->guidance_case_id !== null) {
            return redirect()->route('counselor.cases.show', $document->guidance_case_id)->with('status', "“{$document->title}” uploaded to the case.");
        }

        return redirect()->route('counselor.students.show', $document->student_id)->with('status', "“{$document->title}” uploaded.");
    }

    /**
     * Release a file to an authorised counselor. Every download is logged,
     * always sent as an attachment, and never cached.
     */
    public function download(Document $document, AuditLogger $audit): StreamedResponse
    {
        Gate::authorize('view', $document);

        abort_unless(Storage::disk(Document::DISK)->exists($document->storage_path), 404, 'The stored file is missing. Ask the System Administrator to check the backups.');

        $audit->record(AuditAction::DocumentDownloaded, 'documents', $document);

        return Storage::disk(Document::DISK)->download($document->storage_path, self::downloadName($document), [
            'Content-Type' => $document->mime_type,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'category' => $document->category->label(),
            'originalName' => $document->original_name,
            'size' => $document->humanSize(),
            'extension' => Str::upper(pathinfo($document->original_name, PATHINFO_EXTENSION)),
            'uploadedAt' => $document->created_at?->toIso8601String(),
            'uploadedBy' => $document->relationLoaded('uploader') ? $document->uploader?->name : null,
            'student' => $document->relationLoaded('student') ? self::studentSummary($document->student) : null,
            'caseId' => $document->guidance_case_id,
            'caseReference' => $document->relationLoaded('guidanceCase') ? $document->guidanceCase?->reference : null,
        ];
    }

    /**
     * A safe download name: the original name with anything unusual removed.
     */
    private static function downloadName(Document $document): string
    {
        $extension = Str::lower(pathinfo($document->storage_path, PATHINFO_EXTENSION));
        $base = Str::slug(pathinfo($document->original_name, PATHINFO_FILENAME)) ?: 'document';

        return "{$base}.{$extension}";
    }
}
