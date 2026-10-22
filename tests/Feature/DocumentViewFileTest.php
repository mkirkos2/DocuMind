<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentViewFileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        
        // Create a fake storage disk
        Storage::fake('local');
        
        // Create a fake PDF file
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $storedFilePath = $file->store('documents', 'local');
        
        // Create a document
        $this->document = $this->user->documents()->create([
            'title' => 'Test Document',
            'original_filename' => 'document.pdf',
            'file_path' => $storedFilePath,
            'file_size' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'processing_status' => 'completed',
            'chunk_count' => 5,
        ]);
    }

    /** @test */
    public function authenticated_user_can_view_their_own_pdf_document()
    {
        // Create the actual file content
        Storage::disk('local')->put($this->document->file_path, 'fake pdf content');

        $response = $this->actingAs($this->user)
            ->get(route('documents.view-file', $this->document));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'inline; filename="document.pdf"');
    }

    /** @test */
    public function user_cannot_view_other_users_document()
    {
        $otherUser = User::factory()->create();
        
        $response = $this->actingAs($otherUser)
            ->get(route('documents.view-file', $this->document));

        $response->assertNotFound();
    }

    /** @test */
    public function guest_cannot_view_any_document()
    {
        $response = $this->get(route('documents.view-file', $this->document));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function user_cannot_view_non_existent_file()
    {
        // Delete the file
        Storage::disk('local')->delete($this->document->file_path);

        $response = $this->actingAs($this->user)
            ->get(route('documents.view-file', $this->document));

        $response->assertNotFound();
    }

    /** @test */
    public function user_cannot_view_non_pdf_document()
    {
        // Create a TXT document
        $txtFile = UploadedFile::fake()->create('document.txt', 500, 'text/plain');
        $txtStoredFilePath = $txtFile->store('documents', 'local');
        
        $txtDocument = $this->user->documents()->create([
            'title' => 'Text Document',
            'original_filename' => 'document.txt',
            'file_path' => $txtStoredFilePath,
            'file_size' => $txtFile->getSize(),
            'mime_type' => 'text/plain',
            'processing_status' => 'completed',
            'chunk_count' => 3,
        ]);

        Storage::disk('local')->put($txtDocument->file_path, 'fake text content');

        $response = $this->actingAs($this->user)
            ->get(route('documents.view-file', $txtDocument));

        $response->assertNotFound();
    }
}