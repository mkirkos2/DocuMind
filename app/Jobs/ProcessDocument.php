<?php

namespace App\Jobs;

use App\Models\Document;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;

class ProcessDocument implements ShouldQueue
{
    use Queueable;

    public $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

    public function handle(): void
    {
        try {
            $this->document->update([
                'processing_status' => 'processing',
            ]);

            $filePath = Storage::disk('local')->path($this->document->file_path);

            if (!file_exists($filePath)) {
                throw new Exception("File not found: {$filePath}");
            }

            $chunks = [];

            if ($this->document->mime_type === 'application/pdf') {
                $pages = $this->extractPdfPages($filePath);

                foreach ($pages as $index => $pageText) {
                    $pageChunks = $this->splitTextIntoChunks(
                        $pageText,
                        $index + 1
                    );

                    $chunks = array_merge($chunks, $pageChunks);
                }
            } elseif ($this->document->mime_type === 'text/plain') {
                $textContent = $this->extractTxtText($filePath);
                $chunks = $this->splitTextIntoChunks($textContent, null);
            } else {
                throw new Exception(
                    "Unsupported file type: {$this->document->mime_type}"
                );
            }

            DB::transaction(function () use ($chunks) {
                $this->document->chunks()->delete();

                foreach ($chunks as $index => $chunkData) {
                    $this->document->chunks()->create([
                        'chunk_text' => $chunkData['content'],
                        'character_count' => strlen($chunkData['content']),
                        'chunk_order' => $index,
                        'page_number' => $chunkData['page_number'],
                    ]);
                }
            });

            $this->document->update([
                'processing_status' => 'completed',
                'chunk_count' => count($chunks),
            ]);
        } catch (Exception $e) {
            $this->document->update([
                'processing_status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract text from each PDF page separately.
     */
    protected function extractPdfPages(string $filePath): array
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $pages = [];

            foreach ($pdf->getPages() as $page) {
                $pages[] = $page->getText();
            }

            return $pages;
        } catch (Exception $e) {
            throw new Exception(
                'Failed to extract text from PDF: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Extract text from a TXT file.
     */
    protected function extractTxtText(string $filePath): string
    {
        try {
            $content = file_get_contents($filePath);

            if ($content === false) {
                throw new Exception('Failed to read TXT file');
            }

            return $content;
        } catch (Exception $e) {
            throw new Exception(
                'Failed to extract text from TXT: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Split text into chunks of approximately 1000 characters with overlap.
     */
    protected function splitTextIntoChunks(
        string $text,
        ?int $pageNumber
    ): array {
        $chunks = [];
        $chunkSize = 1000;
        $overlap = 100;
        $length = strlen($text);
        $position = 0;

        while ($position < $length) {
            $chunk = substr($text, $position, $chunkSize);

            $chunks[] = [
                'content' => $chunk,
                'page_number' => $pageNumber,
            ];

            $position += $chunkSize - $overlap;
        }

        return $chunks;
    }
}