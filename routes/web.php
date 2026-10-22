<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionAnsweringController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\Document;
use App\Models\Message;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    $user = auth()->user();

    return Inertia::render('Dashboard', [
        'stats' => [
            'totalDocuments' => Document::where('user_id', $user->id)->count(),

            'processedDocuments' => Document::where('user_id', $user->id)
                ->where('processing_status', 'completed')
                ->count(),

            'questionsAsked' => Message::where('role', 'user')
                ->whereHas('conversation', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->count(),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/documents/search', [DocumentController::class, 'search'])
        ->name('documents.search');

    Route::get('/question-answer', function () {
        return Inertia::render('QuestionAnswer');
    })->name('question.answer.page');

    Route::post('/question-answer', [QuestionAnsweringController::class, 'answer'])
        ->name('question.answer');

    Route::resource('documents', DocumentController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});