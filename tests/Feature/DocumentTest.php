<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\GuidanceCase;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->counselor = User::factory()->counselor()->create();
        $this->student = Student::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function upload(UploadedFile $file, array $overrides = []): array
    {
        return [
            'student_id' => $this->student->id,
            'title' => 'Referral slip (scan)',
            'category' => 'referral_slip',
            'file' => $file,
            ...$overrides,
        ];
    }

    public function test_a_counselor_uploads_a_document_to_private_storage_under_a_generated_name(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.documents.store'), $this->upload(UploadedFile::fake()->create('Slip of Juan.pdf', 120, 'application/pdf')))
            ->assertRedirect(route('counselor.students.show', $this->student));

        $document = Document::sole();
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertStringStartsWith('documents/', $document->storage_path);
        $this->assertStringNotContainsString('Juan', $document->storage_path);
        $this->assertSame('Slip of Juan.pdf', $document->original_name);
        $this->assertSame($this->counselor->id, $document->uploaded_by);
        $this->assertSame(64, strlen($document->sha256));
        $this->assertDatabaseHas('audit_logs', ['action' => AuditAction::DocumentUploaded->value, 'record_id' => $document->id]);
    }

    public function test_unsafe_or_oversized_files_are_rejected(): void
    {
        $this->actingAs($this->counselor)
            ->post(route('counselor.documents.store'), $this->upload(UploadedFile::fake()->create('page.html', 5, 'text/html')))
            ->assertSessionHasErrors('file');

        $this->actingAs($this->counselor)
            ->post(route('counselor.documents.store'), $this->upload(UploadedFile::fake()->create('tool.exe', 5, 'application/x-msdownload')))
            ->assertSessionHasErrors('file');

        $this->actingAs($this->counselor)
            ->post(route('counselor.documents.store'), $this->upload(UploadedFile::fake()->create('huge.pdf', Document::MAX_KILOBYTES + 1, 'application/pdf')))
            ->assertSessionHasErrors(['file' => 'The file is larger than 5 MB.']);

        $this->assertSame(0, Document::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_document_cannot_be_linked_to_another_students_case(): void
    {
        $otherCase = GuidanceCase::factory()->create();

        $this->actingAs($this->counselor)
            ->post(route('counselor.documents.store'), $this->upload(UploadedFile::fake()->create('slip.pdf', 10, 'application/pdf'), ['guidance_case_id' => $otherCase->id]))
            ->assertSessionHasErrors('guidance_case_id');
    }

    public function test_downloading_is_audited_and_never_cached(): void
    {
        $document = Document::factory()->for($this->student)->create(['original_name' => 'Referral Slip <1>.pdf']);
        Storage::disk('local')->put($document->storage_path, '%PDF-1.4 sample');
        $colleague = User::factory()->counselor()->create();

        $response = $this->actingAs($colleague)->get(route('counselor.documents.download', $document));

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('referral-slip-1.pdf');

        $entry = AuditLog::where('action', AuditAction::DocumentDownloaded)->sole();
        $this->assertSame($colleague->id, $entry->user_id);
        $this->assertSame($document->id, $entry->record_id);
    }

    public function test_the_administrator_cannot_list_upload_or_download_documents(): void
    {
        $document = Document::factory()->create();
        Storage::disk('local')->put($document->storage_path, '%PDF-1.4 sample');

        $this->actingAs(User::factory()->administrator()->create());

        $this->get(route('counselor.documents.index'))->assertForbidden();
        $this->get(route('counselor.documents.download', $document))->assertForbidden();
        $this->post(route('counselor.documents.store'), $this->upload(UploadedFile::fake()->create('a.pdf', 1, 'application/pdf')))->assertForbidden();

        $this->assertSame(0, AuditLog::where('action', AuditAction::DocumentDownloaded)->count());
    }

    public function test_private_files_have_no_public_route(): void
    {
        $document = Document::factory()->create();
        Storage::disk('local')->put($document->storage_path, '%PDF-1.4 sample');

        $this->actingAs($this->counselor)->get('/storage/'.$document->storage_path)->assertNotFound();
    }

    public function test_the_storage_path_is_never_sent_to_the_browser(): void
    {
        $document = Document::factory()->for($this->student)->create();

        $this->actingAs($this->counselor)
            ->get(route('counselor.students.show', $this->student))
            ->assertInertia(fn (Assert $page) => $page
                ->has('casework.documents', 1)
                ->where('casework.documents.0.id', $document->id)
                ->missing('casework.documents.0.storagePath')
                ->missing('casework.documents.0.storage_path'));
    }
}
