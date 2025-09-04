<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['verify' => true]);

Route::get('/', [App\Http\Controllers\HomeController::class, 'root'])->name('root')->middleware('auth');

// welcome route
Route::get('/welcome', [App\Http\Controllers\WelcomeController::class, 'index'])->name('welcome')->middleware('auth');

// test routes (temporary for debugging)
Route::get('/test/users', [App\Http\Controllers\TestController::class, 'testUsers']);
Route::get('/test/password/{email}', [App\Http\Controllers\TestController::class, 'testPassword']);
Route::get('/test/auth/{email}/{password}', [App\Http\Controllers\TestController::class, 'testAuth']);
Route::post('/test/login-process', [App\Http\Controllers\TestController::class, 'testLoginProcess']);

// customers route
Route::get('/customers', [App\Http\Controllers\CustomerController::class, 'index'])->name('customers.list');

// users route
Route::get('/users', [App\Http\Controllers\UsersController::class, 'index'])->name('users.index')->middleware('auth');
Route::get('/users-add', [App\Http\Controllers\UsersController::class, 'create'])->name('users.create')->middleware('auth');
Route::get('/users-edit', [App\Http\Controllers\UsersController::class, 'edit'])->name('users.edit')->middleware('auth');
Route::post('/users/check-email', [App\Http\Controllers\UsersController::class, 'checkEmail'])->name('users.checkEmail')->middleware('auth');
Route::get('/users/check-delete/{id}', [App\Http\Controllers\UsersController::class, 'checkDeleteValidation'])->name('users.checkDelete')->middleware('auth');
Route::post('/users', [App\Http\Controllers\UsersController::class, 'store'])->name('users.store')->middleware('auth');
Route::post('/users/{id}', [App\Http\Controllers\UsersController::class, 'update'])->name('users.update')->middleware('auth');
Route::delete('/users/{id}', [App\Http\Controllers\UsersController::class, 'destroy'])->name('users.destroy')->middleware('auth');

//Update User Details
Route::post('/update-profile/{id}', [App\Http\Controllers\HomeController::class, 'updateProfile'])->name('updateProfile');
Route::post('/update-password/{id}', [App\Http\Controllers\HomeController::class, 'updatePassword'])->name('updatePassword');

// crypto checker route
Route::get('/crypto-checker', [App\Http\Controllers\CryptoCheckerController::class, 'index'])->name('crypto-checker')->middleware('auth');

// crypto set route
Route::get('/crypto-set', [App\Http\Controllers\CryptoSetController::class, 'index'])->name('crypto-set')->middleware('auth');

// crypto notification history route
Route::get('/crypto-notification-history', [App\Http\Controllers\CryptoNotificationHistoryController::class, 'index'])->name('crypto-notification-history')->middleware('auth');

// crypto history route
Route::get('/crypto-history', [App\Http\Controllers\CryptoHistoryController::class, 'index'])->name('crypto-history')->middleware('auth');

// crypto pricing history route
Route::get('/crypto-pricing-history', [App\Http\Controllers\CryptoPricingHistoryController::class, 'index'])->name('crypto-pricing-history')->middleware('auth');

// crypto income logger routes
Route::get('/crypto-income-logger', [App\Http\Controllers\CryptoIncomeLoggerController::class, 'index'])->name('crypto-income-logger')->middleware('auth');
Route::get('/crypto-income-logger-add', [App\Http\Controllers\CryptoIncomeLoggerController::class, 'create'])->name('crypto-income-logger-add')->middleware('auth');
Route::post('/crypto-income-logger-add', [App\Http\Controllers\CryptoIncomeLoggerController::class, 'store'])->name('crypto-income-logger-store')->middleware('auth');
Route::post('/crypto-income-logger-delete/{id}', [App\Http\Controllers\CryptoIncomeLoggerController::class, 'destroy'])->name('crypto-income-logger-delete')->middleware('auth');

// crypto difference analysis route
Route::get('/crypto-difference-analysis', [App\Http\Controllers\CryptoDifferenceAnalysisController::class, 'index'])->name('crypto-difference-analysis')->middleware('auth');
Route::post('/crypto-difference-analysis/generate', [App\Http\Controllers\CryptoDifferenceAnalysisController::class, 'generateAnalysis'])->name('crypto-difference-analysis.generate')->middleware('auth');

// crypto tutorials route
Route::get('/crypto-tutorials', [App\Http\Controllers\CryptoTutorialsController::class, 'index'])->name('crypto-tutorials')->middleware('auth');
Route::get('/crypto-difference-analysis/current-task', [App\Http\Controllers\CryptoDifferenceAnalysisController::class, 'getCurrentTask'])->name('crypto-difference-analysis.current-task')->middleware('auth');

// crypto difference history routes
Route::get('/crypto-difference-history-to-buy', [App\Http\Controllers\CryptoDifferenceHistoryToBuyController::class, 'index'])->name('crypto-difference-history-to-buy')->middleware('auth');
Route::get('/crypto-difference-history-to-sell', [App\Http\Controllers\CryptoDifferenceHistoryToSellController::class, 'index'])->name('crypto-difference-history-to-sell')->middleware('auth');

// crypto difference calculation route
Route::post('/crypto-difference-calculation', [App\Http\Controllers\CryptoPricingHistoryController::class, 'calculateDifference'])->name('crypto-difference-calculation')->middleware('auth');

// crypto AI analysis route
Route::get('/crypto-ai-analysis', [App\Http\Controllers\CryptoAiAnalysisController::class, 'index'])->name('crypto-ai-analysis')->middleware('auth');

// crypto settings route
Route::get('/crypto-settings', [App\Http\Controllers\CryptoSettingsController::class, 'index'])->name('crypto-settings')->middleware('auth');

// crypto set change routes
Route::get('/crypto-set-change', [App\Http\Controllers\CryptoSetChangeController::class, 'index'])->name('crypto-set-change.index')->middleware('auth');
Route::post('/crypto-set-change', [App\Http\Controllers\CryptoSetChangeController::class, 'update'])->name('crypto-set-change.update')->middleware('auth');

// crypto set update routes
Route::get('/crypto-set-update', [App\Http\Controllers\CryptoSetUpdateController::class, 'index'])->name('crypto-set-update.index')->middleware('auth');
Route::post('/crypto-set-update', [App\Http\Controllers\CryptoSetUpdateController::class, 'update'])->name('crypto-set-update.update')->middleware('auth');

// Ani-Senso Course routes
Route::get('/anisenso-courses', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'index'])->name('anisenso-courses')->middleware('auth');
Route::get('/anisenso-courses/create', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'create'])->name('anisenso-courses.create')->middleware('auth');
Route::get('/anisenso-courses-add', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'create'])->name('anisenso-courses-add')->middleware('auth');
Route::get('/anisenso-courses-edit', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'editPage'])->name('anisenso-courses-edit')->middleware('auth');
Route::post('/anisenso-courses', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'store'])->name('anisenso-courses.store')->middleware('auth');
Route::get('/anisenso-courses/{id}/edit', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'edit'])->name('anisenso-courses.edit')->middleware('auth');
Route::put('/anisenso-courses/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'update'])->name('anisenso-courses.update')->middleware('auth');
Route::delete('/anisenso-courses/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'destroy'])->name('anisenso-courses.destroy')->middleware('auth');

// Ani-Senso Course Contents routes
Route::get('/anisenso-courses-contents', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'contents'])->name('anisenso-courses.contents')->middleware('auth');
Route::get('/anisenso-courses-contents-add-chapter', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'addChapter'])->name('anisenso-courses.chapters.add')->middleware('auth');
Route::get('/anisenso-courses-contents-edit', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'editChapter'])->name('anisenso-courses.chapters.edit')->middleware('auth');
Route::post('/anisenso-courses-chapters', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'storeChapter'])->name('anisenso-courses.chapters.store')->middleware('auth');
Route::put('/anisenso-courses-chapters/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'updateChapter'])->name('anisenso-courses.chapters.update')->middleware('auth');
Route::delete('/anisenso-courses-chapters/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'destroyChapter'])->name('anisenso-courses.chapters.destroy')->middleware('auth');
Route::put('/anisenso-courses-chapters-order', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'updateChapterOrder'])->name('anisenso-courses.chapters.order')->middleware('auth');

// Ani-Senso Course Topics routes
Route::get('/anisenso-courses-topics', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'courseTopics'])->name('anisenso-courses-topics')->middleware('auth');
Route::get('/anisenso-courses-all-topics', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'courseAllTopics'])->name('anisenso-courses.all-topics')->middleware('auth');
Route::get('/anisenso-courses-topics-add', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'addTopic'])->name('anisenso-courses-topics-add')->middleware('auth');
Route::get('/anisenso-courses-topics-edit', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'editTopic'])->name('anisenso-courses-topics-edit')->middleware('auth');
Route::get('/anisenso-courses-topics-resources', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'topicResources'])->name('anisenso-courses-topics-resources')->middleware('auth');
Route::post('/anisenso-courses-topics-resources-upload', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'uploadResource'])->name('anisenso-courses-topics-resources.upload')->middleware('auth');

// Ani-Senso Course Access Tags routes
Route::get('/anisenso-courses-tags', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseTagsController::class, 'index'])->name('anisenso-courses-tags')->middleware('auth');
Route::get('/anisenso-courses-tags-add', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseTagsController::class, 'create'])->name('anisenso-courses-tags.create')->middleware('auth');
Route::post('/anisenso-courses-tags', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseTagsController::class, 'store'])->name('anisenso-courses-tags.store')->middleware('auth');
Route::get('/anisenso-courses-tags-edit', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseTagsController::class, 'edit'])->name('anisenso-courses-tags.edit')->middleware('auth');
Route::put('/anisenso-courses-tags/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseTagsController::class, 'update'])->name('anisenso-courses-tags.update')->middleware('auth');
Route::delete('/anisenso-courses-tags/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseTagsController::class, 'destroy'])->name('anisenso-courses-tags.destroy')->middleware('auth');
Route::put('/anisenso-courses-topics-resources-order', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'updateResourceOrder'])->name('anisenso-courses-topics-resources.order')->middleware('auth');
Route::post('/anisenso-courses-topics', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'storeTopic'])->name('anisenso-courses-topics.store')->middleware('auth');
Route::put('/anisenso-courses-topics/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'updateTopic'])->name('anisenso-courses-topics.update')->middleware('auth');
Route::delete('/anisenso-courses-topics/{id}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'destroyTopic'])->name('anisenso-courses-topics.destroy')->middleware('auth');
Route::put('/anisenso-topics-order', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'updateTopicOrder'])->name('anisenso-topics.order')->middleware('auth');

// Image upload route for TinyMCE
Route::post('/upload-image', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'uploadImage'])->name('upload-image')->middleware('auth');

//Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

// E-commerce routes
Route::get('/ecom-products', [App\Http\Controllers\Ecommerce\ProductsController::class, 'index'])->name('ecom-products')->middleware('auth');
Route::get('/ecom-products-add', [App\Http\Controllers\Ecommerce\ProductsController::class, 'create'])->name('ecom-products.create')->middleware('auth');
Route::post('/ecom-products-add', [App\Http\Controllers\Ecommerce\ProductsController::class, 'store'])->name('ecom-products.store')->middleware('auth');
Route::delete('/ecom-products/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'destroy'])->name('ecom-products.destroy')->middleware('auth');
Route::patch('/ecom-products/{id}/status', [App\Http\Controllers\Ecommerce\ProductsController::class, 'updateStatus'])->name('ecom-products.update-status')->middleware('auth');
Route::get('/ecom-products-variants', [App\Http\Controllers\Ecommerce\ProductsController::class, 'variants'])->name('ecom-products.variants')->middleware('auth');
Route::get('/ecom-products-variants-add', [App\Http\Controllers\Ecommerce\ProductsController::class, 'createVariant'])->name('ecom-products.variants.create')->middleware('auth');
Route::post('/ecom-products-variants-add', [App\Http\Controllers\Ecommerce\ProductsController::class, 'storeVariant'])->name('ecom-products.variants.store')->middleware('auth');
Route::get('/ecom-products-variants-edit', [App\Http\Controllers\Ecommerce\ProductsController::class, 'editVariant'])->name('ecom-products.variants.edit')->middleware('auth');
Route::put('/ecom-products-variants-edit', [App\Http\Controllers\Ecommerce\ProductsController::class, 'updateVariant'])->name('ecom-products.variants.update')->middleware('auth');
Route::get('/ecom-products-variants-photos', [App\Http\Controllers\Ecommerce\ProductsController::class, 'variantPhotos'])->name('ecom-products.variants.photos')->middleware('auth');
Route::get('/ecom-products-variants-videos', [App\Http\Controllers\Ecommerce\ProductsController::class, 'variantVideos'])->name('ecom-products.variants.videos')->middleware('auth');
Route::post('/ecom-products-variants-videos/upload', [App\Http\Controllers\Ecommerce\ProductsController::class, 'uploadVariantVideo'])->name('ecom-products.variants.videos.upload')->middleware('auth');
Route::delete('/ecom-products-variants-videos/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'deleteVariantVideo'])->name('ecom-products.variants.videos.delete')->middleware('auth');
Route::post('/ecom-products-variants-photos/upload', [App\Http\Controllers\Ecommerce\ProductsController::class, 'uploadVariantImage'])->name('ecom-products.variants.photos.upload')->middleware('auth');
Route::patch('/ecom-products-variants-photos/reorder', [App\Http\Controllers\Ecommerce\ProductsController::class, 'reorderVariantImages'])->name('ecom-products.variants.photos.reorder')->middleware('auth');
Route::delete('/ecom-products-variants-photos/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'deleteVariantImage'])->name('ecom-products.variants.photos.delete')->middleware('auth');
Route::patch('/ecom-products-variants/{id}/status', [App\Http\Controllers\Ecommerce\ProductsController::class, 'updateVariantStatus'])->name('ecom-products.variants.update-status')->middleware('auth');
Route::patch('/ecom-products-variants/{id}/stocks', [App\Http\Controllers\Ecommerce\ProductsController::class, 'updateVariantStocks'])->name('ecom-products.variants.update-stocks')->middleware('auth');
Route::delete('/ecom-products-variants/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'deleteVariant'])->name('ecom-products.variants.delete')->middleware('auth');

// E-commerce product triggers route
Route::get('/ecom-products-triggers', [App\Http\Controllers\Ecommerce\ProductsController::class, 'triggers'])->name('ecom-products.triggers')->middleware('auth');
Route::get('/ecom-products-triggers/available-tags', [App\Http\Controllers\Ecommerce\ProductsController::class, 'getAvailableTags'])->name('ecom-products.triggers.available-tags')->middleware('auth');
Route::post('/ecom-products-triggers/save-tags', [App\Http\Controllers\Ecommerce\ProductsController::class, 'saveTags'])->name('ecom-products.triggers.save-tags')->middleware('auth');
Route::delete('/ecom-products-triggers/delete-tag/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'deleteTag'])->name('ecom-products.triggers.delete-tag')->middleware('auth');

// E-commerce product discounts route
Route::get('/ecom-products-discounts', [App\Http\Controllers\Ecommerce\ProductsController::class, 'discounts'])->name('ecom-products.discounts')->middleware('auth');

// Catch-all route - must be last
Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
