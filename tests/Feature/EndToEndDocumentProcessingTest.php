<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndDocumentProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test end-to-end document processing flow
     */
    public function test_end_to_end_document_processing_flow()
    {
        Storage::fake('local');
        Queue::fake(); // Prevent actual queue processing

        // Create a fake text file
        $file = UploadedFile::fake()->createWithContent('test_document.txt',
            "This is a test document for end-to-end processing.\n" .
            "It contains multiple lines of text.\n" .
            "The system should process this document and create chunks.\n" .
            "Each chunk should contain a portion of this text.\n" .
            "This is the end of the test document."
        );

        // Upload the document
        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        // Assert successful upload
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('success');

        // Get the document
        $document = $this->user->documents()->first();
        $this->assertNotNull($document);
        $this->assertEquals('test_document', $document->title);

        // Assert that the job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessDocument::class, 1);
    }

    /**
     * Test document processing handles PDF files
     */
    public function test_document_processing_handles_pdf_files()
    {
        Storage::fake('local');
        Queue::fake(); // Prevent actual queue processing

        // Create a fake PDF file (just for testing the flow, not actual PDF content)
        $file = UploadedFile::fake()->create('test_document.pdf', 100, 'application/pdf');

        // Upload the document
        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        // Assert successful upload
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Assert that the job was dispatched
        Queue::assertPushed(\App\Jobs\ProcessDocument::class, 1);
    }
}