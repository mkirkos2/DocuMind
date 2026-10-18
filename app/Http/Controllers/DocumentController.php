<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocument;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = auth()->user()->documents()->orderBy('created_at', 'desc')->paginate(10);

        return inertia('Documents/Index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB in KB
                function ($attribute, $value, $fail) {
                    $mimeType = $value->getMimeType();
                    $allowedTypes = ['application/pdf', 'text/plain'];

                    if (!in_array($mimeType, $allowedTypes)) {
                        $fail('The file must be a PDF or TXT file.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Generate a unique filename
        $filename = uniqid() . '_' . str_replace(' ', '_', $originalFilename);

        // Store the file in the private storage disk
        $filePath = $file->storeAs('documents', $filename, 'local');

        // Create the document record
        $document = auth()->user()->documents()->create([
            'title' => pathinfo($originalFilename, PATHINFO_FILENAME),
            'original_filename' => $originalFilename,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'processing_status' => 'pending',
            'chunk_count' => 0,
        ]);

        // Dispatch the document processing job
        ProcessDocument::dispatch($document);

        return redirect()->back()->with('success', 'Document uploaded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        // Ensure the document belongs to the authenticated user
        if ($document->user_id !== auth()->id()) {
            abort(404);
        }

        return inertia('Documents/Show', [
            'document' => $document,
        ]);
    }

    /**
     * Search documents and chunks for the authenticated user.
     */
    public function search(Request $request)
    {
        $query = trim($request->input('q', ''));
        $results = [];

        if (strlen($query) > 0 && strlen($query) <= 255) {
            $documentSearchService = new \App\Services\DocumentSearchService();
            $results = $documentSearchService->searchUserDocuments($query, 10);
        }

        // Debug logging
        \Log::info('Search request', [
            'user_id' => auth()->id(),
            'query' => $query,
            'results_count' => count($results)
        ]);

        return inertia('Documents/Search', [
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        // Ensure the document belongs to the authenticated user
        if ($document->user_id !== auth()->id()) {
            abort(404);
        }

        try {
            // Delete the file from storage
            Storage::disk('local')->delete($document->file_path);
        } catch (\Exception $e) {
            // Log the error but continue with document deletion
            \Log::error('Failed to delete file: ' . $e->getMessage());
        }

        // Delete the document record
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
