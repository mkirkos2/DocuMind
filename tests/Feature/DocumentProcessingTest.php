<?php

namespace Tests\Feature;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test that document processing job is dispatched after upload
     */
    public function test_document_processing_job_is_dispatched_after_upload()
    {
        Queue::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        // Assert that the job was dispatched
        Queue::assertPushed(ProcessDocument::class, 1);

        // Assert that the job was dispatched with the correct document
        Queue::assertPushed(ProcessDocument::class, function ($job) {
            return $job->document->original_filename === 'document.txt';
        });
    }

    /**
     * Test that TXT document text is extracted and chunked
     */
    public function test_txt_document_text_is_extracted_and_chunked()
    {
        Storage::fake('local');
        Queue::fake();

        // Create a fake text file with known content
        $content = str_repeat("This is a test sentence. ", 100); // ~2500 characters
        $file = UploadedFile::fake()->createWithContent('document.txt', $content);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $document = $this->user->documents()->first();

        // Process the document directly (without queue)
        $job = new ProcessDocument($document);
        $job->handle();

        // Reload the document
        $document->refresh();

        // Assert document status is completed
        $this->assertEquals('completed', $document->processing_status);
        $this->assertGreaterThan(0, $document->chunk_count);

        // Assert chunks were created
        $this->assertTrue($document->chunks()->count() > 0);

        // Check that chunks contain the expected content
        $chunks = $document->chunks()->orderBy('chunk_order')->get();
        $reconstructedText = '';
        foreach ($chunks as $chunk) {
            $reconstructedText .= $chunk->chunk_text;
        }

        // The reconstructed text should contain most of the original content
        // (allowing for some overlap/trimming)
        $this->assertStringContainsString(substr($content, 0, 100), $reconstructedText);
    }

    /**
     * Test that processing status transitions from pending to completed
     */
    public function test_processing_status_transitions_from_pending_to_completed()
    {
        Storage::fake('local');
        Queue::fake();

        // Create a larger file that will result in multiple chunks
        $content = str_repeat("This is a test sentence for a larger document. ", 100); // About 5000 characters
        $file = UploadedFile::fake()->createWithContent('document.txt', $content);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $document = $this->user->documents()->first();

        // Initially should be pending
        $this->assertEquals('pending', $document->processing_status);

        // Process the document
        $job = new ProcessDocument($document);
        $job->handle();

        // Reload and check status
        $document->refresh();
        $this->assertEquals('completed', $document->processing_status);
        $this->assertGreaterThan(0, $document->chunk_count);
    }

    /**
     * Test that failed processing sets status to failed with error message
     */
    public function test_failed_processing_sets_status_to_failed_with_error_message()
    {
        Storage::fake('local');
        Queue::fake();

        // Create a document with an invalid file path to simulate failure
        $document = $this->user->documents()->create([
            'title' => 'Invalid Document',
            'original_filename' => 'invalid.txt',
            'file_path' => 'documents/nonexistent.txt',
            'file_size' => 100,
            'mime_type' => 'text/plain',
            'processing_status' => 'pending',
            'chunk_count' => 0,
        ]);

        // Process the document (should fail)
        $job = new ProcessDocument($document);

        try {
            $job->handle();
        } catch (\Exception $e) {
            // Expected to throw exception
        }

        // Reload and check status
        $document->refresh();
        $this->assertEquals('failed', $document->processing_status);
        $this->assertNotNull($document->processing_error);
        $this->assertStringContainsString('File not found', $document->processing_error);
    }

    /**
     * Test that retrying processing clears existing chunks
     */
    public function test_retrying_processing_clears_existing_chunks()
    {
        Storage::fake('local');
        Queue::fake();

        // Create a larger file that will result in multiple chunks
        $content = str_repeat("This is a test sentence for checking chunk clearing. ", 50);
        $file = UploadedFile::fake()->createWithContent('document.txt', $content);

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $document = $this->user->documents()->first();

        // Process the document the first time
        $job1 = new ProcessDocument($document);
        $job1->handle();

        // Check initial chunk count
        $document->refresh();
        $initialChunkCount = $document->chunk_count;
        $this->assertTrue($document->chunks()->count() > 0);

        // Process the document again (simulate retry)
        $job2 = new ProcessDocument($document);
        $job2->handle();

        // Reload and check that chunks were cleared and recreated
        $document->refresh();
        $this->assertEquals('completed', $document->processing_status);
        $this->assertEquals($initialChunkCount, $document->chunk_count);

        // Verify no duplicate chunks exist
        $chunks = $document->chunks()->get();
        $this->assertCount($initialChunkCount, $chunks);
    }
}