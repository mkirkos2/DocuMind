<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Auth;

class DocumentSearchService
{
    public function searchUserDocuments(string $query, int $limit = 5): array
    {
        if (trim($query) === '' || strlen($query) > 255 || $limit < 1) {
            return [];
        }

        $pageNumber = $this->extractPageNumber($query);

        $documentsQuery = Document::where('user_id', Auth::id())
            ->where('processing_status', 'completed')
            ->join('document_chunks', 'documents.id', '=', 'document_chunks.document_id');

        if ($pageNumber !== null) {
            $results = $documentsQuery
                ->where('document_chunks.page_number', $pageNumber)
                ->select(
                    'documents.id as document_id',
                    'documents.title as document_title',
                    'document_chunks.id as chunk_id',
                    'document_chunks.chunk_text',
                    'document_chunks.chunk_order',
                    'document_chunks.page_number',
                    'document_chunks.character_count'
                )
                ->orderBy('documents.id')
                ->orderBy('document_chunks.chunk_order')
                ->limit($limit)
                ->get();

            return $results->map(function ($result) {
                return [
                    'document_id' => $result->document_id,
                    'document_title' => $result->document_title,
                    'chunk_id' => $result->chunk_id,
                    'chunk_text' => $result->chunk_text,
                    'chunk_order' => $result->chunk_order,
                    'page_number' => $result->page_number,
                    'character_count' => $result->character_count,
                    'relevance_score' => 100,
                ];
            })->all();
        }

        $terms = preg_split('/\s+/u', trim($query));
        $terms = array_values(array_filter(
            array_map(
                fn ($term) => trim($term, " \t\n\r\0\x0B.,!?;:\"'()[]{}"),
                $terms
            ),
            fn ($term) => $term !== ''
        ));

        if (empty($terms)) {
            return [];
        }

        foreach ($terms as $term) {
            $documentsQuery->whereRaw(
                'LOWER(document_chunks.chunk_text) LIKE LOWER(?)',
                ['%' . $term . '%']
            );
        }

        return $documentsQuery
            ->select(
                'documents.id as document_id',
                'documents.title as document_title',
                'document_chunks.id as chunk_id',
                'document_chunks.chunk_text',
                'document_chunks.chunk_order',
                'document_chunks.page_number',
                'document_chunks.character_count'
            )
            ->limit($limit)
            ->get()
            ->map(function ($result) use ($terms) {
                $lowerText = strtolower($result->chunk_text);
                $totalFrequency = 0;

                foreach ($terms as $term) {
                    $totalFrequency += substr_count(
                        $lowerText,
                        strtolower($term)
                    );
                }

                return [
                    'document_id' => $result->document_id,
                    'document_title' => $result->document_title,
                    'chunk_id' => $result->chunk_id,
                    'chunk_text' => $result->chunk_text,
                    'chunk_order' => $result->chunk_order,
                    'page_number' => $result->page_number,
                    'character_count' => $result->character_count,
                    'relevance_score' => $totalFrequency * 100
                        + (100 - $result->chunk_order),
                ];
            })
            ->sortByDesc('relevance_score')
            ->values()
            ->all();
    }

    protected function extractPageNumber(string $query): ?int
    {
        if (preg_match('/\bpage\s*(?:number\s*)?(\d+)\b/i', $query, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/σελίδα\s*(?:νούμερο\s*)?(\d+)/iu', $query, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}