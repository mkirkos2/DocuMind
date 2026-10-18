<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function user_can_view_documents_index()
    {
        $response = $this->actingAs($this->user)->get(route('documents.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Documents/Index'));
    }

    /** @test */
    public function user_can_upload_pdf_document()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'user_id' => $this->user->id,
            'original_filename' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'processing_status' => 'pending',
        ]);

        Storage::disk('local')->assertExists('documents/' . $file->hashName());
    }

    /** @test */
    public function user_can_upload_txt_document()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.txt', 500, 'text/plain');

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('documents', [
            'user_id' => $this->user->id,
            'original_filename' => 'document.txt',
            'mime_type' => 'text/plain',
            'processing_status' => 'pending',
        ]);

        Storage::disk('local')->assertExists('documents/' . $file->hashName());
    }

    /** @test */
    public function user_cannot_upload_invalid_file_type()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.exe', 1000, 'application/octet-stream');

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
        Storage::disk('local')->assertMissing('documents/' . $file->hashName());
    }

    /** @test */
    public function user_cannot_upload_file_larger_than_10mb()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 11000, 'application/pdf'); // 11MB

        $response = $this->actingAs($this->user)
            ->post(route('documents.store'), [
                'file' => $file,
            ]);

        $response->assertSessionHasErrors('file');
        Storage::disk('local')->assertMissing('documents/' . $file->hashName());
    }

    /** @test */
    public function user_can_view_their_own_document()
    {
        $document = $this->user->documents()->create([
            'title' => 'Test Document',
            'original_filename' => 'test.pdf',
            'file_path' => 'documents/test.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'processing_status' => 'pending',
            'chunk_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('documents.show', $document));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Documents/Show')
            ->where('document.id', $document->id)
        );
    }

    /** @test */
    public function user_cannot_view_other_users_document()
    {
        $otherUser = User::factory()->create();
        $document = $otherUser->documents()->create([
            'title' => 'Other Document',
            'original_filename' => 'other.pdf',
            'file_path' => 'documents/other.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'processing_status' => 'pending',
            'chunk_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->get(route('documents.show', $document));

        $response->assertNotFound();
    }

    /** @test */
    public function user_can_delete_their_own_document()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        $storedFilePath = $file->store('documents', 'local');

        $document = $this->user->documents()->create([
            'title' => 'Test Document',
            'original_filename' => 'document.pdf',
            'file_path' => $storedFilePath,
            'file_size' => $file->getSize(),
            'mime_type' => 'application/pdf',
            'processing_status' => 'pending',
            'chunk_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);

        Storage::disk('local')->assertMissing($storedFilePath);
    }

    /** @test */
    public function user_cannot_delete_other_users_document()
    {
        $otherUser = User::factory()->create();
        $document = $otherUser->documents()->create([
            'title' => 'Other Document',
            'original_filename' => 'other.pdf',
            'file_path' => 'documents/other.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'processing_status' => 'pending',
            'chunk_count' => 0,
        ]);

        $response = $this->actingAs($this->user)->delete(route('documents.destroy', $document));

        $response->assertNotFound();

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);
    }

    /** @test */
    public function guest_cannot_access_documents()
    {
        $response = $this->get(route('documents.index'));

        $response->assertRedirect(route('login'));
    }
}