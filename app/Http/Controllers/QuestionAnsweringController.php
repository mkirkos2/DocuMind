<?php

namespace App\Http\Controllers;

use App\Services\QuestionAnsweringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class QuestionAnsweringController extends Controller
{
    protected QuestionAnsweringService $questionAnsweringService;

    public function __construct(QuestionAnsweringService $questionAnsweringService)
    {
        $this->questionAnsweringService = $questionAnsweringService;
    }

    /**
     * Answer a question using the user's documents
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function answer(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid request',
                'message' => $validator->errors()->first(),
            ], 400);
        }

        $question = $request->input('question');

        try {
            // Get the answer from the service
            $result = $this->questionAnsweringService->answerQuestion($question);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => 'Failed to generate answer',
                'message' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An unexpected error occurred',
                'message' => 'Please try again later.',
            ], 500);
        }
    }
}