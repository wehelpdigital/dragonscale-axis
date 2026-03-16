<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CheckProcessController;
use App\Http\Controllers\Api\AniSensoApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Check Process API endpoint
Route::get('/check-process', [CheckProcessController::class, 'checkProcess']);

// Ani-Senso Course Management API (with web middleware for session auth)
Route::middleware('web')->prefix('anisenso')->group(function () {
    // Chapters
    Route::get('/chapters/{id}', [AniSensoApiController::class, 'getChapter']);
    Route::post('/chapters', [AniSensoApiController::class, 'storeChapter']);
    Route::put('/chapters/{id}', [AniSensoApiController::class, 'updateChapter']);
    Route::delete('/chapters/{id}', [AniSensoApiController::class, 'deleteChapter']);
    Route::put('/chapters/order', [AniSensoApiController::class, 'updateChapterOrder']);

    // Topics
    Route::get('/topics/{id}', [AniSensoApiController::class, 'getTopic']);
    Route::post('/topics', [AniSensoApiController::class, 'storeTopic']);
    Route::put('/topics/{id}', [AniSensoApiController::class, 'updateTopic']);
    Route::delete('/topics/{id}', [AniSensoApiController::class, 'deleteTopic']);
    Route::put('/topics/order', [AniSensoApiController::class, 'updateTopicOrder']);

    // Contents
    Route::get('/contents/{id}', [AniSensoApiController::class, 'getContent']);
    Route::post('/contents', [AniSensoApiController::class, 'storeContent']);
    Route::post('/contents/{id}', [AniSensoApiController::class, 'updateContent']); // POST for file uploads
    Route::delete('/contents/{id}', [AniSensoApiController::class, 'deleteContent']);
    Route::put('/contents/order', [AniSensoApiController::class, 'updateContentOrder']);

    // Questionnaires
    Route::get('/questionnaires/{id}', [AniSensoApiController::class, 'getQuestionnaire']);
    Route::post('/questionnaires', [AniSensoApiController::class, 'storeQuestionnaire']);
    Route::put('/questionnaires/{id}', [AniSensoApiController::class, 'updateQuestionnaire']);
    Route::delete('/questionnaires/{id}', [AniSensoApiController::class, 'deleteQuestionnaire']);
    Route::put('/course-items/order', [AniSensoApiController::class, 'updateCourseItemsOrder']);

    // Questions
    Route::get('/questions/{id}', [AniSensoApiController::class, 'getQuestion']);
    Route::post('/questions', [AniSensoApiController::class, 'storeQuestion']);
    Route::post('/questions/{id}', [AniSensoApiController::class, 'updateQuestion']); // POST for file uploads
    Route::delete('/questions/{id}', [AniSensoApiController::class, 'deleteQuestion']);
    Route::put('/questions/order', [AniSensoApiController::class, 'updateQuestionOrder']);

    // Comments
    Route::get('/courses/{courseId}/comments', [AniSensoApiController::class, 'getComments']);
    Route::get('/courses/{courseId}/comments/unanswered-count', [AniSensoApiController::class, 'getUnansweredCount']);
    Route::get('/courses/{courseId}/contents-list', [AniSensoApiController::class, 'getCourseContents']);
    Route::get('/contents/{contentId}/comments', [AniSensoApiController::class, 'getContentComments']);
    Route::post('/comments', [AniSensoApiController::class, 'storeComment']);
    Route::post('/comments/{id}/reply', [AniSensoApiController::class, 'replyToComment']);
    Route::put('/comments/{id}', [AniSensoApiController::class, 'updateComment']);
    Route::delete('/comments/{id}', [AniSensoApiController::class, 'deleteComment']);
    Route::post('/comments/{id}/reaction', [AniSensoApiController::class, 'addReaction']);

    // GIFs
    Route::get('/gifs/search', [AniSensoApiController::class, 'searchGifs']);
    Route::get('/gifs/trending', [AniSensoApiController::class, 'getTrendingGifs']);
});

// Leads API (Public - authenticated via API key in query parameter)
Route::prefix('leads')->group(function () {
    Route::get('/add', [App\Http\Controllers\Api\LeadsApiController::class, 'addLead']);
    Route::get('/stores', [App\Http\Controllers\Api\LeadsApiController::class, 'getStores']);
});

// Forms API (Public - authenticated via form-specific API key)
Route::prefix('forms')->group(function () {
    Route::get('/{slug}/submit', [App\Http\Controllers\Api\FormApiController::class, 'submit']);
    Route::get('/{slug}/docs', [App\Http\Controllers\Api\FormApiController::class, 'documentation']);
});

// Trigger Flow Cron API (Public - authenticated via secret key)
Route::get('/trigger-cron', [App\Http\Controllers\Ecommerce\TriggerTasksController::class, 'cronEndpoint']);
