<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageChunkReference;
use App\Models\MessageDocumentReference;
use App\Services\OllamaService;
use App\Services\DocumentSearchService;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class QuestionAnsweringService
{
    protected OllamaService $ollamaService;
    protected DocumentSearchService $documentSearchService;

    public function __construct(OllamaService $ollamaService, DocumentSearchService $documentSearchService)
    {
        $this->ollamaService = $ollamaService;
        $this->documentSearchService = $documentSearchService;
    }

    /**
     * Answer a question using the user's documents
     *
     * @param string $question
     * @return array
     * @throws RuntimeException
     */
    public function answerQuestion(string $question): array
    {
        // Get relevant document chunks
        $chunks = $this->documentSearchService->searchUserDocuments($question, 5);

        // If no relevant chunks found, return a message indicating so
        if (empty($chunks)) {
            return [
                'answer' => 'I couldn\'t find any relevant information in your documents to answer this question.',
                'sources' => [],
            ];
        }

        // Build the prompt with context from the document chunks
        $prompt = $this->buildPrompt($question, $chunks);

        // Build system instruction
        $systemInstruction = $this->buildSystemInstruction();

        // Generate answer using Ollama
        try {
            $answer = $this->ollamaService->generateText($systemInstruction, $prompt);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Failed to generate answer: ' . $e->getMessage());
        }

        // Create or get conversation for the user
        $conversation = $this->getOrCreateConversation($question);

        // Save the user question
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);

        // Save the AI answer
        $assistantMessage = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $answer,
        ]);

        // Save references to documents and chunks used
        $this->saveMessageReferences($assistantMessage, $chunks);

        // Format sources for return
        $sources = array_map(function ($chunk) {
            return [
                'document_id' => $chunk['document_id'],
                'document_title' => $chunk['document_title'],
                'chunk_id' => $chunk['chunk_id'],
                'chunk_order' => $chunk['chunk_order'],
                'page_number' => $chunk['page_number'],
            ];
        }, $chunks);

        return [
            'answer' => $answer,
            'sources' => $sources,
        ];
    }

    /**
     * Build the system instruction for the AI model
     *
     * @return string
     */
    protected function buildSystemInstruction(): string
    {
        return "You answer questions about the user's documents. "
            . "Use the document excerpts provided in the user message. "
            . "When asked what a page says, summarize the text from that page. "
            . "When asked about the content of an excerpt, describe what is actually written. "
            . "Do not claim that information is missing when the excerpt contains text relevant to the question. "
            . "Do not invent facts that are not supported by the excerpts. "
            . "Mention the document title and page number when available.";
    }

    /**
     * Build the prompt with the question and document context
     *
     * @param string $question
     * @param array $chunks
     * @return string
     */
    protected function buildPrompt(string $question, array $chunks): string
    {
        $prompt = "Question: {$question}\n\n";
        $prompt .= "Use the following document excerpts to answer the question.\n";
        $prompt .= "If the question asks what a page says, summarize the text provided for that page.\n\n";

        foreach ($chunks as $index => $chunk) {
            $sourceNumber = $index + 1;
            $sourceInfo = "Document: {$chunk['document_title']}";

            if ($chunk['page_number']) {
                $sourceInfo .= " (Page {$chunk['page_number']})";
            }

            $sourceInfo .= " [Chunk #{$chunk['chunk_order']}]";

            $prompt .= "[Source {$sourceNumber} - {$sourceInfo}]\n";
            $prompt .= $chunk['chunk_text'] . "\n\n";
        }

        $prompt .= "Answer based on the text above. If the excerpt contains text, describe or summarize it rather than refusing to answer. ";
        $prompt .= "Do not invent details that are not present in the excerpts.";

        return $prompt;
    }

    /**
     * Get or create a conversation for the user
     *
     * @param string $question
     * @return Conversation
     */
    protected function getOrCreateConversation(string $question): Conversation
    {
        // Try to get the most recent conversation
        $conversation = Auth::user()->conversations()->orderBy('updated_at', 'desc')->first();

        // If no conversation exists, create a new one
        if (!$conversation) {
            $conversation = Auth::user()->conversations()->create([
                'title' => substr($question, 0, 50) . (strlen($question) > 50 ? '...' : ''),
            ]);
        }

        return $conversation;
    }

    /**
     * Save references between the message and the documents/chunks used
     *
     * @param Message $message
     * @param array $chunks
     * @return void
     */

    protected function saveMessageReferences(Message $message, array $chunks): void
    {
        $savedDocumentIds = [];

        foreach ($chunks as $chunk) {
            $documentId = (int) $chunk['document_id'];

            // Save each document reference only once per message.
            if (!in_array($documentId, $savedDocumentIds, true)) {
                $message->documentReferences()->create([
                    'document_id' => $documentId,
                    'relevance_score' => $chunk['relevance_score'],
                ]);

                $savedDocumentIds[] = $documentId;
            }

            // Save every chunk reference.
            $message->chunkReferences()->create([
                'document_chunk_id' => $chunk['chunk_id'],
                'relevance_score' => $chunk['relevance_score'],
            ]);
        }
    }
}