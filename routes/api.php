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

/*
 * AniSystem's media store. That app's container loses its filesystem on every
 * deploy, so its uploads live here instead. Authenticated by a shared secret —
 * the caller is a server, and has no session here.
 */
Route::post('/anisystem/media', [App\Http\Controllers\Api\AnisystemMediaController::class, 'store']);
Route::delete('/anisystem/media', [App\Http\Controllers\Api\AnisystemMediaController::class, 'destroy']);

/*
 * The studio website (wehelpdigital/game-website): the games it features and the
 * web checkout's payments (Games > Website). Authenticated by its bearer token
 * (GAMES_SITE_TOKEN) -- the caller is the website's server, never a browser.
 */
Route::middleware([App\Http\Middleware\GamesSiteToken::class, 'throttle:240,1'])->prefix('games-site/v1')->group(function () {
    Route::get('/site', [App\Http\Controllers\Api\GamesSiteApiController::class, 'site']);
    Route::post('/checkouts/{token}', [App\Http\Controllers\Api\GamesSiteApiController::class, 'open']);
    Route::get('/checkouts/{token}', [App\Http\Controllers\Api\GamesSiteApiController::class, 'show']);
    Route::post('/checkouts/{token}/gcash', [App\Http\Controllers\Api\GamesSiteApiController::class, 'gcash']);
});

/*
 * Streamr (wehelpdigital/streamr) — the livestream-selling SaaS. Called by
 * Streamr only: Authorization: Bearer STREAMR_API_TOKEN + X-Streamr-Timestamp
 * + X-Streamr-Signature (StreamrApiToken). 503 until the token is set.
 * Its own 120/min counter ("streamr" prefix), never shared with games-site's.
 */
Route::middleware([App\Http\Middleware\StreamrApiToken::class, 'throttle:120,1,streamr'])
    ->withoutMiddleware('throttle:api')
    ->prefix('streamr/v1')->name('streamr-api.')->group(function () {
        Route::get('/ping', [App\Http\Controllers\Api\StreamrApiController::class, 'ping'])->name('ping');
        Route::get('/config', [App\Http\Controllers\Api\StreamrApiController::class, 'config'])->name('config');
        Route::post('/orders', [App\Http\Controllers\Api\StreamrApiController::class, 'createOrder'])->name('orders.store');
        Route::get('/orders', [App\Http\Controllers\Api\StreamrApiController::class, 'orders'])->name('orders.index');
        Route::get('/orders/{number}', [App\Http\Controllers\Api\StreamrApiController::class, 'order'])->name('orders.show')
            ->where('number', 'STR-[0-9]{8}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4,6}');
        Route::post('/orders/{number}/proofs', [App\Http\Controllers\Api\StreamrApiController::class, 'addProof'])->name('orders.proofs')
            ->where('number', 'STR-[0-9]{8}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4,6}');
        Route::post('/orders/{number}/ai-check', [App\Http\Controllers\Api\StreamrApiController::class, 'aiCheck'])->name('orders.ai-check')
            ->where('number', 'STR-[0-9]{8}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4,6}');
        Route::get('/references/check', [App\Http\Controllers\Api\StreamrApiController::class, 'references'])->name('references');
        Route::put('/clients/{streamrUserId}', [App\Http\Controllers\Api\StreamrApiController::class, 'upsertClient'])->name('clients.upsert')
            ->where('streamrUserId', '[0-9]+');
        Route::post('/support/tickets', [App\Http\Controllers\Api\StreamrApiController::class, 'supportTicket'])->name('support.tickets');
        Route::post('/support/tickets/{uuid}/messages', [App\Http\Controllers\Api\StreamrApiController::class, 'supportMessage'])->name('support.messages')
            ->where('uuid', '[0-9a-fA-F-]{36}');
        Route::put('/support/tickets/{uuid}/status', [App\Http\Controllers\Api\StreamrApiController::class, 'supportStatus'])->name('support.status')
            ->where('uuid', '[0-9a-fA-F-]{36}');
        Route::get('/support/updates', [App\Http\Controllers\Api\StreamrApiController::class, 'supportUpdates'])->name('support.updates');
    });
