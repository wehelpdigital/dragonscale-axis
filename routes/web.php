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

Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('root')->middleware('auth');
Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Route::get('/dashboard/data', [App\Http\Controllers\DashboardController::class, 'getData'])->name('dashboard.data')->middleware('auth');

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
Route::get('/crypto-history/data', [App\Http\Controllers\CryptoHistoryController::class, 'getData'])->name('crypto-history.data')->middleware('auth');

// crypto pricing history route
Route::get('/crypto-pricing-history', [App\Http\Controllers\CryptoPricingHistoryController::class, 'index'])->name('crypto-pricing-history')->middleware('auth');
Route::get('/crypto-pricing-history/data', [App\Http\Controllers\CryptoPricingHistoryController::class, 'getData'])->name('crypto-pricing-history.data')->middleware('auth');

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
Route::get('/crypto-difference-history-to-buy/data', [App\Http\Controllers\CryptoDifferenceHistoryToBuyController::class, 'getData'])->name('crypto-difference-history-to-buy.data')->middleware('auth');
Route::get('/crypto-difference-history-to-sell', [App\Http\Controllers\CryptoDifferenceHistoryToSellController::class, 'index'])->name('crypto-difference-history-to-sell')->middleware('auth');
Route::get('/crypto-difference-history-to-sell/data', [App\Http\Controllers\CryptoDifferenceHistoryToSellController::class, 'getData'])->name('crypto-difference-history-to-sell.data')->middleware('auth');

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
Route::put('/anisenso-courses/{id}/status', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'toggleStatus'])->name('anisenso-courses.status')->middleware('auth');

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

// Ani-Senso Course Students routes
Route::get('/anisenso-courses/{courseId}/students', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'getStudents'])->name('anisenso-courses.students')->middleware('auth');
Route::get('/anisenso-courses/{courseId}/students/search', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'searchAvailableStudents'])->name('anisenso-courses.students.search')->middleware('auth');
Route::post('/anisenso-courses/students/enroll', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'enrollStudent'])->name('anisenso-courses.students.enroll')->middleware('auth');
Route::get('/anisenso-courses/enrollments/{enrollmentId}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'getEnrollment'])->name('anisenso-courses.enrollments.get')->middleware('auth');
Route::put('/anisenso-courses/enrollments/{enrollmentId}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'updateEnrollment'])->name('anisenso-courses.enrollments.update')->middleware('auth');
Route::delete('/anisenso-courses/enrollments/{enrollmentId}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'removeStudent'])->name('anisenso-courses.enrollments.delete')->middleware('auth');
Route::post('/anisenso-courses/enrollments/{enrollmentId}/reset-progress', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'resetProgress'])->name('anisenso-courses.enrollments.reset-progress')->middleware('auth');
Route::post('/anisenso-courses/students/{accessClientId}/send-password-reset', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseStudentsController::class, 'sendPasswordResetEmail'])->name('anisenso-courses.students.send-password-reset')->middleware('auth');

// Ani-Senso Course Audit routes
Route::get('/anisenso-courses/{courseId}/audit', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseAuditController::class, 'getAuditLogs'])->name('anisenso-courses.audit')->middleware('auth');
Route::get('/anisenso-courses/{courseId}/audit/users', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseAuditController::class, 'getUsers'])->name('anisenso-courses.audit.users')->middleware('auth');

// Ani-Senso Course Reviews routes
Route::get('/anisenso-courses/{courseId}/reviews', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseReviewsController::class, 'getReviews'])->name('anisenso-courses.reviews')->middleware('auth');
Route::delete('/anisenso-courses/reviews/{reviewId}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseReviewsController::class, 'deleteReview'])->name('anisenso-courses.reviews.delete')->middleware('auth');
Route::put('/anisenso-courses/reviews/{reviewId}/approval', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseReviewsController::class, 'toggleApproval'])->name('anisenso-courses.reviews.approval')->middleware('auth');
Route::put('/anisenso-courses/reviews/{reviewId}/featured', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseReviewsController::class, 'toggleFeatured'])->name('anisenso-courses.reviews.featured')->middleware('auth');
Route::post('/anisenso-courses/reviews/{reviewId}/reply', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseReviewsController::class, 'addReply'])->name('anisenso-courses.reviews.reply')->middleware('auth');
Route::delete('/anisenso-courses/review-replies/{replyId}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseReviewsController::class, 'deleteReply'])->name('anisenso-courses.reviews.reply.delete')->middleware('auth');

// Ani-Senso Course Settings routes
Route::get('/anisenso-courses/{courseId}/settings', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseSettingsController::class, 'getSettings'])->name('anisenso-courses.settings')->middleware('auth');
Route::put('/anisenso-courses/{courseId}/settings/course-flow', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseSettingsController::class, 'updateCourseFlow'])->name('anisenso-courses.settings.course-flow')->middleware('auth');

// Ani-Senso Course Certificate routes
Route::get('/anisenso-courses-certificate-designer', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'designer'])->name('anisenso-courses.certificate.designer')->middleware('auth');
Route::get('/anisenso-courses/{courseId}/certificate', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'getTemplate'])->name('anisenso-courses.certificate.get')->middleware('auth');
Route::put('/anisenso-courses/{courseId}/certificate', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'saveTemplate'])->name('anisenso-courses.certificate.save')->middleware('auth');
Route::post('/anisenso-courses/{courseId}/certificate/background', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'uploadBackground'])->name('anisenso-courses.certificate.background')->middleware('auth');
Route::delete('/anisenso-courses/{courseId}/certificate/background', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'removeBackground'])->name('anisenso-courses.certificate.background.remove')->middleware('auth');
Route::post('/anisenso-courses/{courseId}/certificate/assets', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'uploadAsset'])->name('anisenso-courses.certificate.assets.upload')->middleware('auth');
Route::get('/anisenso-courses/{courseId}/certificate/assets', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'getAssets'])->name('anisenso-courses.certificate.assets')->middleware('auth');
Route::delete('/anisenso-courses/certificate/assets/{assetId}', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'deleteAsset'])->name('anisenso-courses.certificate.assets.delete')->middleware('auth');
Route::put('/anisenso-courses/{courseId}/certificate/toggle-status', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseCertificatesController::class, 'toggleStatus'])->name('anisenso-courses.certificate.toggle-status')->middleware('auth');

// Image upload route for TinyMCE
Route::post('/upload-image', [App\Http\Controllers\aniSensoAdmin\AniSensoCourseController::class, 'uploadImage'])->name('upload-image')->middleware('auth');

//Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

// E-commerce routes
// Stores
Route::get('/ecom-stores', [App\Http\Controllers\Ecommerce\StoresController::class, 'index'])->name('ecom-stores')->middleware('auth');
Route::get('/ecom-stores-add', [App\Http\Controllers\Ecommerce\StoresController::class, 'create'])->name('ecom-stores.create')->middleware('auth');
Route::post('/ecom-stores-add', [App\Http\Controllers\Ecommerce\StoresController::class, 'store'])->name('ecom-stores.store')->middleware('auth');
Route::get('/ecom-stores-edit', [App\Http\Controllers\Ecommerce\StoresController::class, 'edit'])->name('ecom-stores.edit')->middleware('auth');
Route::put('/ecom-stores/{id}', [App\Http\Controllers\Ecommerce\StoresController::class, 'update'])->name('ecom-stores.update')->middleware('auth');
Route::delete('/ecom-stores/{id}', [App\Http\Controllers\Ecommerce\StoresController::class, 'destroy'])->name('ecom-stores.destroy')->middleware('auth');
Route::patch('/ecom-stores/{id}/status', [App\Http\Controllers\Ecommerce\StoresController::class, 'updateStatus'])->name('ecom-stores.update-status')->middleware('auth');
Route::post('/ecom-stores/{id}/remove-logo', [App\Http\Controllers\Ecommerce\StoresController::class, 'removeLogo'])->name('ecom-stores.remove-logo')->middleware('auth');

// Store Settings
Route::get('/ecom-store-settings', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'index'])->name('ecom-store-settings')->middleware('auth');
Route::post('/ecom-store-settings/smtp', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'saveSmtp'])->name('ecom-store-settings.smtp.save')->middleware('auth');
Route::post('/ecom-store-settings/smtp/test', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'testSmtp'])->name('ecom-store-settings.smtp.test')->middleware('auth');
Route::post('/ecom-store-settings/smtp/toggle', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'toggleSmtpStatus'])->name('ecom-store-settings.smtp.toggle')->middleware('auth');

// Payment Settings
Route::post('/ecom-store-settings/payment', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'savePayment'])->name('ecom-store-settings.payment.save')->middleware('auth');
Route::post('/ecom-store-settings/payment/upload', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'uploadPaymentImage'])->name('ecom-store-settings.payment.upload')->middleware('auth');
Route::post('/ecom-store-settings/payment/remove-image', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'removePaymentImage'])->name('ecom-store-settings.payment.remove-image')->middleware('auth');
Route::post('/ecom-store-settings/payment/toggle', [App\Http\Controllers\Ecommerce\StoreSettingsController::class, 'togglePaymentMethod'])->name('ecom-store-settings.payment.toggle')->middleware('auth');

// Store Logins
Route::get('/ecom-store-logins', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'index'])->name('ecom-store-logins')->middleware('auth');
Route::post('/ecom-store-logins/store', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'store'])->name('ecom-store-logins.store')->middleware('auth');
Route::get('/ecom-store-logins/show', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'show'])->name('ecom-store-logins.show')->middleware('auth');
Route::post('/ecom-store-logins/update', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'update'])->name('ecom-store-logins.update')->middleware('auth');
Route::delete('/ecom-store-logins/delete', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'destroy'])->name('ecom-store-logins.delete')->middleware('auth');
Route::post('/ecom-store-logins/toggle', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'toggleStatus'])->name('ecom-store-logins.toggle')->middleware('auth');
Route::get('/ecom-store-logins/check-phone', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'checkPhone'])->name('ecom-store-logins.check-phone')->middleware('auth');
Route::get('/ecom-store-logins/check-email', [App\Http\Controllers\Ecommerce\StoreLoginsController::class, 'checkEmail'])->name('ecom-store-logins.check-email')->middleware('auth');

// All Clients
Route::get('/ecom-clients', [App\Http\Controllers\Ecommerce\ClientsController::class, 'index'])->name('ecom-clients')->middleware('auth');
Route::get('/ecom-clients/data', [App\Http\Controllers\Ecommerce\ClientsController::class, 'getData'])->name('ecom-clients.data')->middleware('auth');
Route::post('/ecom-clients/store', [App\Http\Controllers\Ecommerce\ClientsController::class, 'store'])->name('ecom-clients.store')->middleware('auth');
Route::get('/ecom-clients/show', [App\Http\Controllers\Ecommerce\ClientsController::class, 'show'])->name('ecom-clients.show')->middleware('auth');
Route::post('/ecom-clients/update', [App\Http\Controllers\Ecommerce\ClientsController::class, 'update'])->name('ecom-clients.update')->middleware('auth');
Route::delete('/ecom-clients/delete', [App\Http\Controllers\Ecommerce\ClientsController::class, 'destroy'])->name('ecom-clients.delete')->middleware('auth');
Route::get('/ecom-clients/check-phone', [App\Http\Controllers\Ecommerce\ClientsController::class, 'checkPhone'])->name('ecom-clients.check-phone')->middleware('auth');
Route::get('/ecom-clients/check-email', [App\Http\Controllers\Ecommerce\ClientsController::class, 'checkEmail'])->name('ecom-clients.check-email')->middleware('auth');

// Client Shippings
Route::get('/ecom-client-shippings', [App\Http\Controllers\Ecommerce\ClientShippingsController::class, 'index'])->name('ecom-client-shippings')->middleware('auth');
Route::get('/ecom-client-shippings/data', [App\Http\Controllers\Ecommerce\ClientShippingsController::class, 'getData'])->name('ecom-client-shippings.data')->middleware('auth');
Route::post('/ecom-client-shippings/store', [App\Http\Controllers\Ecommerce\ClientShippingsController::class, 'store'])->name('ecom-client-shippings.store')->middleware('auth');

// Products
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

// NOTE: Variant shipping assignment is now managed through /ecom-shipping-restrictions
// These routes are deprecated and redirected to the shipping restrictions page
Route::get('/ecom-products-variants-shipping', function() {
    return redirect()->route('ecom-shipping')->with('info', 'Shipping assignment is now managed through Shipping Restrictions. Click "Restrictions" on a shipping method to assign products/variants.');
})->name('ecom-products.variants.shipping')->middleware('auth');

// E-commerce product variant triggers route
Route::get('/ecom-products-variants-triggers', [App\Http\Controllers\Ecommerce\ProductsController::class, 'variantTriggers'])->name('ecom-products.variants.triggers')->middleware('auth');
Route::get('/ecom-products-variants-triggers/available-tags', [App\Http\Controllers\Ecommerce\ProductsController::class, 'getAvailableVariantTags'])->name('ecom-products.variants.triggers.available-tags')->middleware('auth');
Route::post('/ecom-products-variants-triggers/save-tags', [App\Http\Controllers\Ecommerce\ProductsController::class, 'saveVariantTags'])->name('ecom-products.variants.triggers.save-tags')->middleware('auth');
Route::post('/ecom-products-variants-triggers/create-tag', [App\Http\Controllers\Ecommerce\ProductsController::class, 'createTriggerTag'])->name('ecom-products.variants.triggers.create-tag')->middleware('auth');
Route::delete('/ecom-products-variants-triggers/delete-tag/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'deleteVariantTag'])->name('ecom-products.variants.triggers.delete-tag')->middleware('auth');

// E-commerce product edit route
Route::get('/ecom-products-edit', [App\Http\Controllers\Ecommerce\ProductsController::class, 'edit'])->name('ecom-products.edit')->middleware('auth');
Route::put('/ecom-products/{id}', [App\Http\Controllers\Ecommerce\ProductsController::class, 'update'])->name('ecom-products.update')->middleware('auth');

// E-commerce packages routes
Route::get('/ecom-packages', [App\Http\Controllers\Ecommerce\PackagesController::class, 'index'])->name('ecom-packages')->middleware('auth');
Route::get('/ecom-packages/data', [App\Http\Controllers\Ecommerce\PackagesController::class, 'getData'])->name('ecom-packages.data')->middleware('auth');
Route::get('/ecom-packages/products', [App\Http\Controllers\Ecommerce\PackagesController::class, 'getProducts'])->name('ecom-packages.products')->middleware('auth');
Route::get('/ecom-packages-add', [App\Http\Controllers\Ecommerce\PackagesController::class, 'create'])->name('ecom-packages.create')->middleware('auth');
Route::post('/ecom-packages-add', [App\Http\Controllers\Ecommerce\PackagesController::class, 'store'])->name('ecom-packages.store')->middleware('auth');
Route::get('/ecom-packages-edit', [App\Http\Controllers\Ecommerce\PackagesController::class, 'edit'])->name('ecom-packages.edit')->middleware('auth');
Route::put('/ecom-packages/{id}', [App\Http\Controllers\Ecommerce\PackagesController::class, 'update'])->name('ecom-packages.update')->middleware('auth');
Route::get('/ecom-packages/{id}/details', [App\Http\Controllers\Ecommerce\PackagesController::class, 'getPackageDetails'])->name('ecom-packages.details')->middleware('auth');
Route::patch('/ecom-packages/{id}/toggle-status', [App\Http\Controllers\Ecommerce\PackagesController::class, 'toggleStatus'])->name('ecom-packages.toggle-status')->middleware('auth');
Route::delete('/ecom-packages/{id}', [App\Http\Controllers\Ecommerce\PackagesController::class, 'destroy'])->name('ecom-packages.destroy')->middleware('auth');

// E-commerce orders routes
Route::get('/ecom-orders', [App\Http\Controllers\Ecommerce\OrdersController::class, 'index'])->name('ecom-orders')->middleware('auth');
Route::get('/ecom-orders/data', [App\Http\Controllers\Ecommerce\OrdersController::class, 'getData'])->name('ecom-orders.data')->middleware('auth');
Route::get('/ecom-orders/{id}/details', [App\Http\Controllers\Ecommerce\OrdersController::class, 'getOrderDetails'])->name('ecom-orders.details')->middleware('auth');
Route::put('/ecom-orders/{id}/status', [App\Http\Controllers\Ecommerce\OrdersController::class, 'updateStatus'])->name('ecom-orders.update-status')->middleware('auth');
Route::put('/ecom-orders/{id}/shipping', [App\Http\Controllers\Ecommerce\OrdersController::class, 'updateShipping'])->name('ecom-orders.update-shipping')->middleware('auth');
Route::put('/ecom-orders/{id}/cancel', [App\Http\Controllers\Ecommerce\OrdersController::class, 'cancelOrder'])->name('ecom-orders.cancel')->middleware('auth');
Route::get('/ecom-orders/{id}/audit-logs', [App\Http\Controllers\Ecommerce\OrdersController::class, 'getAuditLogs'])->name('ecom-orders.audit-logs')->middleware('auth');
Route::get('/ecom-orders-custom-add', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'index'])->name('ecom-orders-custom-add')->middleware('auth');
Route::post('/ecom-orders-custom-add', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'store'])->name('ecom-orders-custom-add.store')->middleware('auth');
Route::post('/ecom-orders-custom-add/validate-step', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'validateStep'])->name('ecom-orders-custom-add.validate-step')->middleware('auth');
Route::get('/ecom-orders-custom-add/products', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getProducts'])->name('ecom-orders-custom-add.products')->middleware('auth');
Route::get('/ecom-orders-custom-add/packages', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getAvailablePackages'])->name('ecom-orders-custom-add.packages')->middleware('auth');
Route::get('/ecom-orders-custom-add/variants', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getProductVariants'])->name('ecom-orders-custom-add.variants')->middleware('auth');
Route::get('/ecom-orders-custom-add/variant-details', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getVariantDetails'])->name('ecom-orders-custom-add.variant-details')->middleware('auth');
Route::get('/ecom-orders-custom-add/stores', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getStores'])->name('ecom-orders-custom-add.stores')->middleware('auth');
Route::get('/ecom-orders-custom-add/clients', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getClients'])->name('ecom-orders-custom-add.clients')->middleware('auth');
Route::get('/ecom-orders-custom-add/access-clients', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getAccessClients'])->name('ecom-orders-custom-add.access-clients')->middleware('auth');
Route::get('/ecom-orders-custom-add/check-phone', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'checkAccessPhone'])->name('ecom-orders-custom-add.check-phone')->middleware('auth');
Route::post('/ecom-orders-custom-add/save-access', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'saveAccess'])->name('ecom-orders-custom-add.save-access')->middleware('auth');
Route::get('/ecom-orders-custom-add/check-client-phone', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'checkClientPhone'])->name('ecom-orders-custom-add.check-client-phone')->middleware('auth');
Route::get('/ecom-orders-custom-add/check-client-email', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'checkClientEmail'])->name('ecom-orders-custom-add.check-client-email')->middleware('auth');
Route::get('/ecom-orders-custom-add/check-access-email', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'checkAccessEmail'])->name('ecom-orders-custom-add.check-access-email')->middleware('auth');
Route::post('/ecom-orders-custom-add/save-client', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'saveClient'])->name('ecom-orders-custom-add.save-client')->middleware('auth');
Route::get('/ecom-orders-custom-add/philippine-provinces', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getPhilippineProvinces'])->name('ecom-orders-custom-add.philippine-provinces')->middleware('auth');
Route::get('/ecom-orders-custom-add/philippine-municipalities', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getPhilippineMunicipalities'])->name('ecom-orders-custom-add.philippine-municipalities')->middleware('auth');
Route::post('/ecom-orders-custom-add/get-shipping-options', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getShippingOptions'])->name('ecom-orders-custom-add.get-shipping-options')->middleware('auth');
Route::post('/ecom-orders-custom-add/calculate-shipping', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'calculateShipping'])->name('ecom-orders-custom-add.calculate-shipping')->middleware('auth');
Route::get('/ecom-orders-custom-add/auto-apply-discounts', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getAutoApplyDiscounts'])->name('ecom-orders-custom-add.auto-apply-discounts')->middleware('auth');
Route::get('/ecom-orders-custom-add/validate-discount-code', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'validateDiscountCode'])->name('ecom-orders-custom-add.validate-discount-code')->middleware('auth');
Route::post('/ecom-orders-custom-add/calculate-with-discounts', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'calculateWithDiscounts'])->name('ecom-orders-custom-add.calculate-with-discounts')->middleware('auth');
Route::post('/ecom-orders-custom-add/validate-product-prices', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'validateProductPrices'])->name('ecom-orders-custom-add.validate-product-prices')->middleware('auth');
Route::post('/ecom-orders-custom-add/validate-shipping-rates', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'validateShippingRates'])->name('ecom-orders-custom-add.validate-shipping-rates')->middleware('auth');
Route::post('/ecom-orders-custom-add/validate-applied-discounts', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'validateAppliedDiscounts'])->name('ecom-orders-custom-add.validate-applied-discounts')->middleware('auth');
Route::post('/ecom-orders-custom-add/affiliate-commissions', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'getAffiliateCommissions'])->name('ecom-orders-custom-add.affiliate-commissions')->middleware('auth');
Route::post('/ecom-orders-custom-add/store-order', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'storeOrder'])->name('ecom-orders-custom-add.store-order')->middleware('auth');
Route::get('/ecom-orders-custom-add/search-shipping-address', [App\Http\Controllers\Ecommerce\OrdersCustomAddController::class, 'searchShippingAddress'])->name('ecom-orders-custom-add.search-shipping-address')->middleware('auth');


// E-commerce shipping routes
Route::get('/ecom-shipping', [App\Http\Controllers\Ecommerce\ShippingController::class, 'index'])->name('ecom-shipping')->middleware('auth');
Route::get('/ecom-shipping/data', [App\Http\Controllers\Ecommerce\ShippingController::class, 'getData'])->name('ecom-shipping.data')->middleware('auth');
Route::post('/ecom-shipping', [App\Http\Controllers\Ecommerce\ShippingController::class, 'store'])->name('ecom-shipping.store')->middleware('auth');
Route::delete('/ecom-shipping/{id}', [App\Http\Controllers\Ecommerce\ShippingController::class, 'destroy'])->name('ecom-shipping.destroy')->middleware('auth');
Route::get('/ecom-shipping/{id}/edit', [App\Http\Controllers\Ecommerce\ShippingController::class, 'edit'])->name('ecom-shipping.edit')->middleware('auth');
Route::put('/ecom-shipping/{id}', [App\Http\Controllers\Ecommerce\ShippingController::class, 'update'])->name('ecom-shipping.update')->middleware('auth');
Route::get('/ecom-shipping-settings', [App\Http\Controllers\Ecommerce\ShippingController::class, 'settings'])->name('ecom-shipping.settings')->middleware('auth');
Route::get('/ecom-shipping-options/data', [App\Http\Controllers\Ecommerce\ShippingController::class, 'getShippingOptionsData'])->name('ecom-shipping-options.data')->middleware('auth');

// E-commerce shipping restrictions routes
Route::get('/ecom-shipping-restrictions', [App\Http\Controllers\Ecommerce\ShippingController::class, 'restrictions'])->name('ecom-shipping.restrictions')->middleware('auth');
Route::get('/ecom-shipping/{id}/restrictions', [App\Http\Controllers\Ecommerce\ShippingController::class, 'getRestrictions'])->name('ecom-shipping.get-restrictions')->middleware('auth');
Route::post('/ecom-shipping/{id}/restrictions', [App\Http\Controllers\Ecommerce\ShippingController::class, 'saveRestrictions'])->name('ecom-shipping.save-restrictions')->middleware('auth');
Route::get('/ecom-shipping/search-stores', [App\Http\Controllers\Ecommerce\ShippingController::class, 'searchStores'])->name('ecom-shipping.search-stores')->middleware('auth');
Route::get('/ecom-shipping/search-products', [App\Http\Controllers\Ecommerce\ShippingController::class, 'searchProducts'])->name('ecom-shipping.search-products')->middleware('auth');
Route::get('/ecom-shipping/product-variants/{productId}', [App\Http\Controllers\Ecommerce\ShippingController::class, 'getProductVariants'])->name('ecom-shipping.product-variants')->middleware('auth');

// E-commerce discounts routes
Route::get('/ecom-discounts', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'index'])->name('ecom-discounts')->middleware('auth');
Route::get('/ecom-discounts/data', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'getData'])->name('ecom-discounts.data')->middleware('auth');
Route::get('/ecom-discounts/search-products', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'searchProducts'])->name('ecom-discounts.search-products')->middleware('auth');
Route::get('/ecom-discounts/search-stores', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'searchStores'])->name('ecom-discounts.search-stores')->middleware('auth');
Route::get('/ecom-discounts/product-variants/{productId}', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'getProductVariants'])->name('ecom-discounts.product-variants')->middleware('auth');
Route::get('/ecom-discounts-add', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'create'])->name('ecom-discounts.create')->middleware('auth');
Route::post('/ecom-discounts-add', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'store'])->name('ecom-discounts.store')->middleware('auth');
Route::get('/ecom-discounts-edit', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'edit'])->name('ecom-discounts.edit')->middleware('auth');
Route::put('/ecom-discounts-edit', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'update'])->name('ecom-discounts.update')->middleware('auth');
Route::get('/ecom-discounts/{id}', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'show'])->name('ecom-discounts.show')->middleware('auth');
Route::patch('/ecom-discounts/{id}/status', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'updateStatus'])->name('ecom-discounts.update-status')->middleware('auth');
Route::delete('/ecom-discounts/{id}', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'destroy'])->name('ecom-discounts.destroy')->middleware('auth');

// E-commerce discount restrictions routes
Route::get('/ecom-discounts-restrictions', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'restrictions'])->name('ecom-discounts.restrictions')->middleware('auth');
Route::get('/ecom-discounts/{id}/restrictions', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'getRestrictions'])->name('ecom-discounts.get-restrictions')->middleware('auth');
Route::post('/ecom-discounts/{id}/restrictions', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'saveRestrictions'])->name('ecom-discounts.save-restrictions')->middleware('auth');
Route::delete('/ecom-discounts-restrictions/{id}', [App\Http\Controllers\Ecommerce\DiscountsController::class, 'removeRestriction'])->name('ecom-discounts.remove-restriction')->middleware('auth');

// E-commerce affiliates routes
Route::get('/ecom-affiliates', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'index'])->name('ecom-affiliates')->middleware('auth');
Route::get('/ecom-affiliates-add', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'create'])->name('ecom-affiliates.create')->middleware('auth');
Route::post('/ecom-affiliates-add', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'store'])->name('ecom-affiliates.store')->middleware('auth');
Route::get('/ecom-affiliates-edit', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'edit'])->name('ecom-affiliates.edit')->middleware('auth');
Route::put('/ecom-affiliates/{id}', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'update'])->name('ecom-affiliates.update')->middleware('auth');
Route::delete('/ecom-affiliates/{id}', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'destroy'])->name('ecom-affiliates.destroy')->middleware('auth');
Route::patch('/ecom-affiliates/{id}/status', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'updateStatus'])->name('ecom-affiliates.update-status')->middleware('auth');
Route::delete('/ecom-affiliates/{id}/remove-photo', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'removePhoto'])->name('ecom-affiliates.remove-photo')->middleware('auth');
Route::get('/ecom-affiliates/client-details/{id}', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'getClientDetails'])->name('ecom-affiliates.client-details')->middleware('auth');
Route::post('/ecom-affiliates/{id}/documents', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'uploadDocuments'])->name('ecom-affiliates.upload-documents')->middleware('auth');
Route::get('/ecom-affiliates/{id}/documents', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'getDocuments'])->name('ecom-affiliates.get-documents')->middleware('auth');
Route::delete('/ecom-affiliates-documents/{id}', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'deleteDocument'])->name('ecom-affiliates.delete-document')->middleware('auth');
Route::get('/ecom-affiliates/{id}/details', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'show'])->name('ecom-affiliates.show')->middleware('auth');
Route::get('/ecom-affiliates/{id}/earnings', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'getEarnings'])->name('ecom-affiliates.earnings')->middleware('auth');
// Affiliate referrals routes
Route::get('/ecom-affiliates-referrals', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'referralsPage'])->name('ecom-affiliates.referrals-page')->middleware('auth');
Route::get('/ecom-affiliates/{id}/referrals', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'getReferrals'])->name('ecom-affiliates.referrals')->middleware('auth');
Route::get('/ecom-affiliates/{id}/referrals/available-clients/{storeId}', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'getAvailableClients'])->name('ecom-affiliates.available-clients')->middleware('auth');
Route::get('/ecom-affiliate-referrals/check-availability', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'checkClientAvailability'])->name('ecom-affiliates.check-availability')->middleware('auth');
Route::post('/ecom-affiliates/{id}/referrals', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'storeReferral'])->name('ecom-affiliates.store-referral')->middleware('auth');
Route::post('/ecom-affiliates/{id}/referrals/new-client', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'storeNewClientReferral'])->name('ecom-affiliates.store-new-client-referral')->middleware('auth');
Route::delete('/ecom-affiliate-referrals/{id}', [App\Http\Controllers\Ecommerce\AffiliatesController::class, 'removeReferral'])->name('ecom-affiliates.remove-referral')->middleware('auth');

// E-commerce triggers routes
Route::get('/ecom-triggers', [App\Http\Controllers\Ecommerce\TriggersController::class, 'index'])->name('ecom-triggers')->middleware('auth');
Route::get('/ecom-triggers-create', [App\Http\Controllers\Ecommerce\TriggersController::class, 'create'])->name('ecom-triggers.create')->middleware('auth');
Route::get('/ecom-triggers-edit', [App\Http\Controllers\Ecommerce\TriggersController::class, 'edit'])->name('ecom-triggers.edit')->middleware('auth');
Route::get('/ecom-triggers-data', [App\Http\Controllers\Ecommerce\TriggersController::class, 'getFlowData'])->name('ecom-triggers.data')->middleware('auth');
Route::post('/ecom-triggers-store', [App\Http\Controllers\Ecommerce\TriggersController::class, 'store'])->name('ecom-triggers.store')->middleware('auth');
Route::put('/ecom-triggers-update', [App\Http\Controllers\Ecommerce\TriggersController::class, 'update'])->name('ecom-triggers.update')->middleware('auth');
Route::put('/ecom-triggers-toggle-status', [App\Http\Controllers\Ecommerce\TriggersController::class, 'toggleStatus'])->name('ecom-triggers.toggle-status')->middleware('auth');
Route::post('/ecom-triggers-duplicate', [App\Http\Controllers\Ecommerce\TriggersController::class, 'duplicate'])->name('ecom-triggers.duplicate')->middleware('auth');
Route::delete('/ecom-triggers-delete', [App\Http\Controllers\Ecommerce\TriggersController::class, 'destroy'])->name('ecom-triggers.destroy')->middleware('auth');

// E-commerce Sales Reports routes
Route::get('/ecom-reports-sales', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'index'])->name('ecom-reports.sales')->middleware('auth');
Route::get('/ecom-reports-sales/overview', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getOverview'])->name('ecom-reports.sales.overview')->middleware('auth');
Route::get('/ecom-reports-sales/by-store', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getSalesByStore'])->name('ecom-reports.sales.by-store')->middleware('auth');
Route::get('/ecom-reports-sales/by-product', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getSalesByProduct'])->name('ecom-reports.sales.by-product')->middleware('auth');
Route::get('/ecom-reports-sales/trend', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getSalesTrend'])->name('ecom-reports.sales.trend')->middleware('auth');
Route::get('/ecom-reports-sales/discount', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getDiscountReport'])->name('ecom-reports.sales.discount')->middleware('auth');
Route::get('/ecom-reports-sales/commission', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getCommissionReport'])->name('ecom-reports.sales.commission')->middleware('auth');
Route::post('/ecom-reports-sales/save', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'saveReport'])->name('ecom-reports.sales.save')->middleware('auth');
Route::get('/ecom-reports-sales/saved', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getSavedReports'])->name('ecom-reports.sales.saved')->middleware('auth');
Route::get('/ecom-reports-sales/load/{id}', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'loadReport'])->name('ecom-reports.sales.load')->middleware('auth');
Route::delete('/ecom-reports-sales/{id}', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'deleteReport'])->name('ecom-reports.sales.delete')->middleware('auth');
Route::get('/ecom-reports-sales/export', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'exportReport'])->name('ecom-reports.sales.export')->middleware('auth');
Route::get('/ecom-reports-sales/profitability', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getProfitabilityData'])->name('ecom-reports.sales.profitability')->middleware('auth');
Route::get('/ecom-reports-sales/refunds', [App\Http\Controllers\Ecommerce\SalesReportsController::class, 'getRefundsReport'])->name('ecom-reports.sales.refunds')->middleware('auth');

// E-commerce Heatmap routes
Route::get('/ecom-reports-heatmap', [App\Http\Controllers\Ecommerce\HeatmapController::class, 'index'])->name('ecom-reports.heatmap')->middleware('auth');
Route::get('/ecom-reports-heatmap/data', [App\Http\Controllers\Ecommerce\HeatmapController::class, 'getData'])->name('ecom-reports.heatmap.data')->middleware('auth');
Route::get('/ecom-reports-heatmap/export', [App\Http\Controllers\Ecommerce\HeatmapController::class, 'export'])->name('ecom-reports.heatmap.export')->middleware('auth');

Route::post('/ecom-triggers-upload-image', [App\Http\Controllers\Ecommerce\TriggersController::class, 'uploadImage'])->name('ecom-triggers.upload-image')->middleware('auth');
Route::get('/ecom-shipping-options/available-provinces', [App\Http\Controllers\Ecommerce\ShippingController::class, 'getAvailableProvinces'])->name('ecom-shipping-options.available-provinces')->middleware('auth');
Route::get('/ecom-shipping-options/{id}/edit', [App\Http\Controllers\Ecommerce\ShippingController::class, 'editShippingOption'])->name('ecom-shipping-options.edit')->middleware('auth');
Route::post('/ecom-shipping-options', [App\Http\Controllers\Ecommerce\ShippingController::class, 'storeShippingOption'])->name('ecom-shipping-options.store')->middleware('auth');
Route::put('/ecom-shipping-options/{id}', [App\Http\Controllers\Ecommerce\ShippingController::class, 'updateShippingOption'])->name('ecom-shipping-options.update')->middleware('auth');
Route::put('/ecom-shipping-options/{id}/status', [App\Http\Controllers\Ecommerce\ShippingController::class, 'updateShippingOptionStatus'])->name('ecom-shipping-options.status')->middleware('auth');
Route::delete('/ecom-shipping-options/{id}', [App\Http\Controllers\Ecommerce\ShippingController::class, 'deleteShippingOption'])->name('ecom-shipping-options.delete')->middleware('auth');

// E-commerce refunds routes
Route::get('/ecom-refunds', [App\Http\Controllers\Ecommerce\RefundsController::class, 'index'])->name('ecom-refunds')->middleware('auth');
Route::get('/ecom-refunds/data', [App\Http\Controllers\Ecommerce\RefundsController::class, 'getData'])->name('ecom-refunds.data')->middleware('auth');
Route::get('/ecom-refunds/summary', [App\Http\Controllers\Ecommerce\RefundsController::class, 'getSummary'])->name('ecom-refunds.summary')->middleware('auth');
Route::get('/ecom-refunds/get-order', [App\Http\Controllers\Ecommerce\RefundsController::class, 'getOrderForRefund'])->name('ecom-refunds.get-order')->middleware('auth');
Route::get('/ecom-refunds/products', [App\Http\Controllers\Ecommerce\RefundsController::class, 'getProducts'])->name('ecom-refunds.products')->middleware('auth');
Route::post('/ecom-refunds', [App\Http\Controllers\Ecommerce\RefundsController::class, 'store'])->name('ecom-refunds.store')->middleware('auth');
Route::get('/ecom-refunds/{id}', [App\Http\Controllers\Ecommerce\RefundsController::class, 'show'])->name('ecom-refunds.show')->middleware('auth');
Route::post('/ecom-refunds/{id}/process', [App\Http\Controllers\Ecommerce\RefundsController::class, 'process'])->name('ecom-refunds.process')->middleware('auth');
Route::delete('/ecom-refunds/{id}', [App\Http\Controllers\Ecommerce\RefundsController::class, 'destroy'])->name('ecom-refunds.destroy')->middleware('auth');
Route::get('/ecom-refunds/{id}/audit-trail', [App\Http\Controllers\Ecommerce\RefundsController::class, 'getAuditTrail'])->name('ecom-refunds.audit-trail')->middleware('auth');
Route::get('/ecom-refunds-audit-logs', [App\Http\Controllers\Ecommerce\RefundsController::class, 'getAllAuditLogs'])->name('ecom-refunds.all-audit-logs')->middleware('auth');

// CRM Leads routes
Route::get('/crm-leads', [App\Http\Controllers\CRM\LeadsController::class, 'index'])->name('crm-leads')->middleware('auth');
Route::get('/crm-leads/data', [App\Http\Controllers\CRM\LeadsController::class, 'getData'])->name('crm-leads.data')->middleware('auth');
Route::get('/crm-leads-add', [App\Http\Controllers\CRM\LeadsController::class, 'create'])->name('crm-leads.create')->middleware('auth');
Route::post('/crm-leads-add', [App\Http\Controllers\CRM\LeadsController::class, 'store'])->name('crm-leads.store')->middleware('auth');
Route::get('/crm-leads-view', [App\Http\Controllers\CRM\LeadsController::class, 'show'])->name('crm-leads.show')->middleware('auth');
Route::get('/crm-leads-edit', [App\Http\Controllers\CRM\LeadsController::class, 'edit'])->name('crm-leads.edit')->middleware('auth');
Route::put('/crm-leads', [App\Http\Controllers\CRM\LeadsController::class, 'update'])->name('crm-leads.update')->middleware('auth');
Route::delete('/crm-leads', [App\Http\Controllers\CRM\LeadsController::class, 'destroy'])->name('crm-leads.destroy')->middleware('auth');
Route::post('/crm-leads/update-status', [App\Http\Controllers\CRM\LeadsController::class, 'updateStatus'])->name('crm-leads.update-status')->middleware('auth');
Route::post('/crm-leads/add-activity', [App\Http\Controllers\CRM\LeadsController::class, 'addActivity'])->name('crm-leads.add-activity')->middleware('auth');
Route::get('/crm-leads/{id}/activities', [App\Http\Controllers\CRM\LeadsController::class, 'getActivities'])->name('crm-leads.activities')->middleware('auth');
Route::get('/crm-leads/sources', [App\Http\Controllers\CRM\LeadsController::class, 'getSources'])->name('crm-leads.sources')->middleware('auth');
Route::get('/crm-leads/potential-logins', [App\Http\Controllers\CRM\LeadsController::class, 'findPotentialLogins'])->name('crm-leads.potential-logins')->middleware('auth');
Route::get('/crm-leads/potential-clients', [App\Http\Controllers\CRM\LeadsController::class, 'findPotentialClients'])->name('crm-leads.potential-clients')->middleware('auth');
Route::post('/crm-leads/parse-import', [App\Http\Controllers\CRM\LeadsController::class, 'parseImportFile'])->name('crm-leads.parse-import')->middleware('auth');
Route::post('/crm-leads/process-import', [App\Http\Controllers\CRM\LeadsController::class, 'processImport'])->name('crm-leads.process-import')->middleware('auth');
Route::post('/crm-leads/custom-data', [App\Http\Controllers\CRM\LeadsController::class, 'addCustomData'])->name('crm-leads.add-custom-data')->middleware('auth');
Route::put('/crm-leads/custom-data', [App\Http\Controllers\CRM\LeadsController::class, 'updateCustomData'])->name('crm-leads.update-custom-data')->middleware('auth');
Route::delete('/crm-leads/custom-data', [App\Http\Controllers\CRM\LeadsController::class, 'deleteCustomData'])->name('crm-leads.delete-custom-data')->middleware('auth');
Route::post('/crm-leads/link-store-login', [App\Http\Controllers\CRM\LeadsController::class, 'linkStoreLogin'])->name('crm-leads.link-store-login')->middleware('auth');
Route::post('/crm-leads/unlink-store-login', [App\Http\Controllers\CRM\LeadsController::class, 'unlinkStoreLogin'])->name('crm-leads.unlink-store-login')->middleware('auth');
Route::post('/crm-leads/link-client', [App\Http\Controllers\CRM\LeadsController::class, 'linkClient'])->name('crm-leads.link-client')->middleware('auth');
Route::post('/crm-leads/unlink-client', [App\Http\Controllers\CRM\LeadsController::class, 'unlinkClient'])->name('crm-leads.unlink-client')->middleware('auth');
Route::get('/crm-leads/linked-connections', [App\Http\Controllers\CRM\LeadsController::class, 'getLinkedConnections'])->name('crm-leads.linked-connections')->middleware('auth');

// CRM Business Contacts routes
Route::get('/crm-business-contacts', [App\Http\Controllers\CRM\BusinessContactsController::class, 'index'])->name('crm-business-contacts')->middleware('auth');
Route::get('/crm-business-contacts-add', [App\Http\Controllers\CRM\BusinessContactsController::class, 'create'])->name('crm-business-contacts.create')->middleware('auth');
Route::post('/crm-business-contacts-add', [App\Http\Controllers\CRM\BusinessContactsController::class, 'store'])->name('crm-business-contacts.store')->middleware('auth');
Route::get('/crm-business-contacts-view', [App\Http\Controllers\CRM\BusinessContactsController::class, 'show'])->name('crm-business-contacts.show')->middleware('auth');
Route::get('/crm-business-contacts-edit', [App\Http\Controllers\CRM\BusinessContactsController::class, 'edit'])->name('crm-business-contacts.edit')->middleware('auth');
Route::put('/crm-business-contacts', [App\Http\Controllers\CRM\BusinessContactsController::class, 'update'])->name('crm-business-contacts.update')->middleware('auth');
Route::delete('/crm-business-contacts/{id}', [App\Http\Controllers\CRM\BusinessContactsController::class, 'destroy'])->name('crm-business-contacts.destroy')->middleware('auth');
Route::post('/crm-business-contacts/update-status', [App\Http\Controllers\CRM\BusinessContactsController::class, 'updateStatus'])->name('crm-business-contacts.update-status')->middleware('auth');
Route::post('/crm-business-contacts/update-last-contact', [App\Http\Controllers\CRM\BusinessContactsController::class, 'updateLastContact'])->name('crm-business-contacts.update-last-contact')->middleware('auth');

// CRM Forms routes
Route::get('/crm-forms', [App\Http\Controllers\CRM\CrmFormsController::class, 'index'])->name('crm-forms')->middleware('auth');
Route::get('/crm-forms-create', [App\Http\Controllers\CRM\CrmFormsController::class, 'create'])->name('crm-forms.create')->middleware('auth');
Route::post('/crm-forms-store', [App\Http\Controllers\CRM\CrmFormsController::class, 'store'])->name('crm-forms.store')->middleware('auth');
Route::get('/crm-forms-edit', [App\Http\Controllers\CRM\CrmFormsController::class, 'edit'])->name('crm-forms.edit')->middleware('auth');
Route::put('/crm-forms-update', [App\Http\Controllers\CRM\CrmFormsController::class, 'update'])->name('crm-forms.update')->middleware('auth');
Route::delete('/crm-forms-delete', [App\Http\Controllers\CRM\CrmFormsController::class, 'destroy'])->name('crm-forms.destroy')->middleware('auth');
Route::post('/crm-forms-duplicate', [App\Http\Controllers\CRM\CrmFormsController::class, 'duplicate'])->name('crm-forms.duplicate')->middleware('auth');
Route::post('/crm-forms-toggle-status', [App\Http\Controllers\CRM\CrmFormsController::class, 'toggleStatus'])->name('crm-forms.toggle-status')->middleware('auth');
Route::get('/crm-forms-preview', [App\Http\Controllers\CRM\CrmFormsController::class, 'preview'])->name('crm-forms.preview')->middleware('auth');
Route::get('/crm-forms-submissions', [App\Http\Controllers\CRM\CrmFormsController::class, 'submissions'])->name('crm-forms.submissions')->middleware('auth');
Route::get('/crm-forms-submission', [App\Http\Controllers\CRM\CrmFormsController::class, 'getSubmission'])->name('crm-forms.submission')->middleware('auth');
Route::delete('/crm-forms-submission-delete', [App\Http\Controllers\CRM\CrmFormsController::class, 'deleteSubmission'])->name('crm-forms.submission.delete')->middleware('auth');
Route::get('/crm-forms-export', [App\Http\Controllers\CRM\CrmFormsController::class, 'exportSubmissions'])->name('crm-forms.export')->middleware('auth');
Route::post('/crm-forms-upload-image', [App\Http\Controllers\CRM\CrmFormsController::class, 'uploadImage'])->name('crm-forms.upload-image')->middleware('auth');
Route::post('/crm-forms-generate-api-key', [App\Http\Controllers\CRM\CrmFormsController::class, 'generateApiKey'])->name('crm-forms.generate-api-key')->middleware('auth');
Route::post('/crm-forms-toggle-api', [App\Http\Controllers\CRM\CrmFormsController::class, 'toggleApi'])->name('crm-forms.toggle-api')->middleware('auth');
Route::get('/crm-forms-lead-fields', [App\Http\Controllers\CRM\CrmFormsController::class, 'getLeadFields'])->name('crm-forms.lead-fields')->middleware('auth');

// Public Form routes (no auth required)
Route::get('/f/{slug}', [App\Http\Controllers\CRM\PublicFormController::class, 'show'])->name('public-form.show');
Route::post('/f/{slug}/submit', [App\Http\Controllers\CRM\PublicFormController::class, 'submit'])->name('public-form.submit');

// API Documentation routes
Route::get('/api-docs-leads', [App\Http\Controllers\Api\ApiDocumentationController::class, 'leads'])->name('api-docs.leads')->middleware('auth');
Route::post('/api-docs/regenerate-key', [App\Http\Controllers\Api\ApiDocumentationController::class, 'regenerateApiKey'])->name('api-docs.regenerate-key')->middleware('auth');

// AI Technician KB Docs Settings routes (legacy - kept for backwards compatibility)
Route::get('/ai-technician-kb-docs-settings', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'index'])->name('ai-technician.kb-docs-settings')->middleware('auth');
Route::post('/ai-technician-kb-docs-settings/settings', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'storeSettings'])->name('ai-technician.kb-docs-settings.store')->middleware('auth');
Route::post('/ai-technician-kb-docs-settings/test', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'testConnection'])->name('ai-technician.kb-docs-settings.test')->middleware('auth');
Route::post('/ai-technician-kb-docs-settings/upload', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'uploadFile'])->name('ai-technician.kb-docs-settings.upload')->middleware('auth');
Route::get('/ai-technician-kb-docs-settings/files', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'getFiles'])->name('ai-technician.kb-docs-settings.files')->middleware('auth');
Route::delete('/ai-technician-kb-docs-settings/files/{id}', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'deleteFile'])->name('ai-technician.kb-docs-settings.delete')->middleware('auth');
Route::post('/ai-technician-kb-docs-settings/files/{id}/retry', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'retryFile'])->name('ai-technician.kb-docs-settings.retry')->middleware('auth');
Route::post('/ai-technician-kb-docs-settings/files/{id}/refresh', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'refreshFileStatus'])->name('ai-technician.kb-docs-settings.refresh')->middleware('auth');
Route::post('/ai-technician-kb-docs-settings/sync', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'syncFiles'])->name('ai-technician.kb-docs-settings.sync')->middleware('auth');

// AI Technician Unified Knowledge Base routes
Route::get('/ai-technician-knowledge-base', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'unifiedIndex'])->name('ai-technician.knowledge-base')->middleware('auth');
Route::post('/ai-technician-knowledge-base/settings', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'saveUnifiedSettings'])->name('ai-technician.knowledge-base.settings.save')->middleware('auth');
Route::post('/ai-technician-knowledge-base/settings/test', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'testConnection'])->name('ai-technician.knowledge-base.settings.test')->middleware('auth');
Route::post('/ai-technician-knowledge-base/upload-doc', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'uploadFile'])->name('ai-technician.knowledge-base.upload-doc')->middleware('auth');
Route::post('/ai-technician-knowledge-base/add-website', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'store'])->name('ai-technician.knowledge-base.add-website')->middleware('auth');
Route::post('/ai-technician-knowledge-base/upload-image', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'store'])->name('ai-technician.knowledge-base.upload-image')->middleware('auth');
Route::delete('/ai-technician-knowledge-base/docs/{id}', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'deleteFile'])->name('ai-technician.knowledge-base.delete-doc')->middleware('auth');
Route::delete('/ai-technician-knowledge-base/websites/{id}', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'destroy'])->name('ai-technician.knowledge-base.delete-website')->middleware('auth');
Route::delete('/ai-technician-knowledge-base/images/{id}', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'destroy'])->name('ai-technician.knowledge-base.delete-image')->middleware('auth');
Route::put('/ai-technician-knowledge-base/images/{id}', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'update'])->name('ai-technician.knowledge-base.update-image')->middleware('auth');
Route::post('/ai-technician-knowledge-base/check-index-status', [App\Http\Controllers\AiTechnician\RagSettingsController::class, 'batchCheckIndexStatus'])->name('ai-technician.knowledge-base.check-index-status')->middleware('auth');

// AI Technician External Products routes (for Knowledge Base)
Route::get('/ai-technician-knowledge-base/products', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'index'])->name('ai-technician.knowledge-base.products')->middleware('auth');
Route::get('/ai-technician-knowledge-base/products/types', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'getProductTypes'])->name('ai-technician.knowledge-base.products.types')->middleware('auth');
Route::post('/ai-technician-knowledge-base/products', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'store'])->name('ai-technician.knowledge-base.products.store')->middleware('auth');
Route::get('/ai-technician-knowledge-base/products/{id}', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'show'])->name('ai-technician.knowledge-base.products.show')->middleware('auth');
Route::post('/ai-technician-knowledge-base/products/{id}/process', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'process'])->name('ai-technician.knowledge-base.products.process')->middleware('auth');
Route::post('/ai-technician-knowledge-base/products/{id}/retry', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'retry'])->name('ai-technician.knowledge-base.products.retry')->middleware('auth');
Route::delete('/ai-technician-knowledge-base/products/{id}', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'destroy'])->name('ai-technician.knowledge-base.products.delete')->middleware('auth');
// Product image management routes
Route::post('/ai-technician-knowledge-base/products/{id}/images', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'addImages'])->name('ai-technician.knowledge-base.products.images.add')->middleware('auth');
Route::delete('/ai-technician-knowledge-base/products/{productId}/images/{imageId}', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'deleteImage'])->name('ai-technician.knowledge-base.products.images.delete')->middleware('auth');
Route::post('/ai-technician-knowledge-base/products/{productId}/images/{imageId}/primary', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'setPrimaryImage'])->name('ai-technician.knowledge-base.products.images.primary')->middleware('auth');

// Product document management routes
Route::post('/ai-technician-knowledge-base/products/{id}/documents', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'addDocuments'])->name('ai-technician.knowledge-base.products.documents.add')->middleware('auth');
Route::delete('/ai-technician-knowledge-base/products/{productId}/documents/{documentId}', [App\Http\Controllers\AiTechnician\ExternalProductsController::class, 'deleteDocument'])->name('ai-technician.knowledge-base.products.documents.delete')->middleware('auth');

// AI Technician Reply Flow routes (single settings page)
Route::get('/ai-technician-reply-flow', [App\Http\Controllers\AiTechnician\AiReplyFlowsController::class, 'index'])->name('ai-technician.reply-flow')->middleware('auth');
Route::post('/ai-technician-reply-flow/save', [App\Http\Controllers\AiTechnician\AiReplyFlowsController::class, 'save'])->name('ai-technician.reply-flow.save')->middleware('auth');
Route::post('/ai-technician-reply-flow/toggle', [App\Http\Controllers\AiTechnician\AiReplyFlowsController::class, 'toggleStatus'])->name('ai-technician.reply-flow.toggle')->middleware('auth');
Route::post('/ai-technician-reply-flow/reset', [App\Http\Controllers\AiTechnician\AiReplyFlowsController::class, 'reset'])->name('ai-technician.reply-flow.reset')->middleware('auth');

// AI Technician Query Rules routes
Route::get('/ai-technician-query-rules', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'index'])->name('ai-technician.query-rules')->middleware('auth');
Route::get('/ai-technician-query-rules/create', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'create'])->name('ai-technician.query-rules.create')->middleware('auth');
Route::post('/ai-technician-query-rules', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'store'])->name('ai-technician.query-rules.store')->middleware('auth');
Route::get('/ai-technician-query-rules/compiled', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'getCompiled'])->name('ai-technician.query-rules.compiled')->middleware('auth');
Route::post('/ai-technician-query-rules/reset', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'resetToDefaults'])->name('ai-technician.query-rules.reset')->middleware('auth');
Route::get('/ai-technician-query-rules/{id}/edit', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'edit'])->name('ai-technician.query-rules.edit')->middleware('auth');
Route::put('/ai-technician-query-rules/{id}', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'update'])->name('ai-technician.query-rules.update')->middleware('auth');
Route::post('/ai-technician-query-rules/{id}/toggle', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'toggleStatus'])->name('ai-technician.query-rules.toggle')->middleware('auth');
Route::delete('/ai-technician-query-rules/{id}', [App\Http\Controllers\AiTechnician\AiQueryRulesController::class, 'destroy'])->name('ai-technician.query-rules.destroy')->middleware('auth');

// AI Technician KB Websites Settings routes
Route::get('/ai-technician-kb-websites-settings', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'index'])->name('ai-technician.kb-websites-settings')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'store'])->name('ai-technician.kb-websites-settings.store')->middleware('auth');
Route::get('/ai-technician-kb-websites-settings-active', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'getActiveWebsites'])->name('ai-technician.kb-websites-settings.active')->middleware('auth');
// Settings routes must come BEFORE {id} routes to avoid "settings" being treated as an ID
Route::post('/ai-technician-kb-websites-settings/settings', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'saveSettings'])->name('ai-technician.kb-websites-settings.settings.save')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/settings/test', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'testSettings'])->name('ai-technician.kb-websites-settings.settings.test')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/check-processing', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'checkProcessingStatus'])->name('ai-technician.kb-websites-settings.check-processing')->middleware('auth');
// Routes with {id} parameter
Route::get('/ai-technician-kb-websites-settings/{id}', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'show'])->name('ai-technician.kb-websites-settings.show')->middleware('auth');
Route::put('/ai-technician-kb-websites-settings/{id}', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'update'])->name('ai-technician.kb-websites-settings.update')->middleware('auth');
Route::delete('/ai-technician-kb-websites-settings/{id}', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'destroy'])->name('ai-technician.kb-websites-settings.destroy')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/{id}/toggle', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'toggleStatus'])->name('ai-technician.kb-websites-settings.toggle')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/{id}/test', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'testScrape'])->name('ai-technician.kb-websites-settings.test')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/{id}/scrape', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'scrape'])->name('ai-technician.kb-websites-settings.scrape')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/{id}/upload-pinecone', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'uploadToPinecone'])->name('ai-technician.kb-websites-settings.upload-pinecone')->middleware('auth');
Route::post('/ai-technician-kb-websites-settings/{id}/refresh-pinecone', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'refreshPineconeStatus'])->name('ai-technician.kb-websites-settings.refresh-pinecone')->middleware('auth');
Route::get('/ai-technician-kb-websites-settings/{id}/pages', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'getPages'])->name('ai-technician.kb-websites-settings.pages')->middleware('auth');
Route::get('/ai-technician-kb-websites-settings/{websiteId}/pages/{pageId}', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'getPageContent'])->name('ai-technician.kb-websites-settings.page-content')->middleware('auth');
Route::delete('/ai-technician-kb-websites-settings/{websiteId}/pages/{pageId}', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'deletePage'])->name('ai-technician.kb-websites-settings.delete-page')->middleware('auth');
Route::delete('/ai-technician-kb-websites-settings/{id}/pages', [App\Http\Controllers\AiTechnician\AiWebsitesController::class, 'clearPages'])->name('ai-technician.kb-websites-settings.clear-pages')->middleware('auth');

// AI Technician Settings routes
Route::get('/ai-technician-settings', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'index'])->name('ai-technician.settings')->middleware('auth');
Route::get('/ai-technician-settings/active-provider', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'getActiveProvider'])->name('ai-technician.settings.active')->middleware('auth');

// AI Technician Image Search Settings routes (MUST be before {provider} wildcard)
Route::put('/ai-technician-settings/image-search', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'updateImageSearchSettings'])->name('ai-technician.settings.image-search.update')->middleware('auth');
Route::post('/ai-technician-settings/image-search/test', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'testImageSearchApi'])->name('ai-technician.settings.image-search.test')->middleware('auth');
Route::post('/ai-technician-settings/image-search/test-serper', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'testSerperApi'])->name('ai-technician.settings.image-search.test-serper')->middleware('auth');

// AI Technician Access Tags routes (MUST be before {provider} wildcard)
Route::get('/ai-technician-settings/access-tags', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'getAccessTags'])->name('ai-technician.settings.access-tags.list')->middleware('auth');
Route::post('/ai-technician-settings/access-tags', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'storeAccessTag'])->name('ai-technician.settings.access-tags.store')->middleware('auth');
Route::get('/ai-technician-settings/access-tags/{id}', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'getAccessTag'])->name('ai-technician.settings.access-tags.show')->middleware('auth');
Route::put('/ai-technician-settings/access-tags/{id}', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'updateAccessTag'])->name('ai-technician.settings.access-tags.update')->middleware('auth');
Route::delete('/ai-technician-settings/access-tags/{id}', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'destroyAccessTag'])->name('ai-technician.settings.access-tags.destroy')->middleware('auth');

// AI Technician Currency Settings routes
Route::get('/ai-technician-settings/currency', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'getCurrencySettings'])->name('ai-technician.settings.currency.get')->middleware('auth');
Route::put('/ai-technician-settings/currency', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'updateCurrencySettings'])->name('ai-technician.settings.currency.update')->middleware('auth');
Route::post('/ai-technician-settings/currency/refresh', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'refreshExchangeRate'])->name('ai-technician.settings.currency.refresh')->middleware('auth');

// AI Technician Avatar Settings routes
Route::get('/ai-technician-settings/avatar', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'getAvatarSettings'])->name('ai-technician.settings.avatar.get')->middleware('auth');
Route::post('/ai-technician-settings/avatar', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'updateAvatarSettings'])->name('ai-technician.settings.avatar.update')->middleware('auth');
Route::delete('/ai-technician-settings/avatar', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'deleteAvatar'])->name('ai-technician.settings.avatar.delete')->middleware('auth');

// AI Provider Settings (wildcard routes - MUST be AFTER specific routes)
Route::put('/ai-technician-settings/{provider}', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'update'])->name('ai-technician.settings.update')->middleware('auth');
Route::post('/ai-technician-settings/{provider}/default', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'setDefault'])->name('ai-technician.settings.default')->middleware('auth');
Route::post('/ai-technician-settings/{provider}/test', [App\Http\Controllers\AiTechnician\AiSettingsController::class, 'testConnection'])->name('ai-technician.settings.test')->middleware('auth');

// AI Technician Clients routes
Route::get('/ai-technician-clients', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'index'])->name('ai-technician.clients')->middleware('auth');
Route::get('/ai-technician-clients/data', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'getClients'])->name('ai-technician.clients.data')->middleware('auth');
Route::get('/ai-technician-clients/search', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'searchAvailableClients'])->name('ai-technician.clients.search')->middleware('auth');
Route::post('/ai-technician-clients/grant', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'grantAccess'])->name('ai-technician.clients.grant')->middleware('auth');
Route::post('/ai-technician-clients/bulk-extend', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'bulkExtend'])->name('ai-technician.clients.bulk-extend')->middleware('auth');
Route::get('/ai-technician-clients/{id}', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'getClient'])->name('ai-technician.clients.show')->middleware('auth');
Route::put('/ai-technician-clients/{id}', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'updateAccess'])->name('ai-technician.clients.update')->middleware('auth');
Route::delete('/ai-technician-clients/{id}', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'revokeAccess'])->name('ai-technician.clients.revoke')->middleware('auth');
Route::post('/ai-technician-clients/{id}/toggle', [App\Http\Controllers\AiTechnician\AiTechnicianClientsController::class, 'toggleStatus'])->name('ai-technician.clients.toggle')->middleware('auth');

// AI Technician KB Images routes
Route::get('/ai-technician-kb-images-settings', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'index'])->name('ai-technician.kb-images-settings')->middleware('auth');
Route::get('/ai-technician-kb-images-settings/images', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'getImages'])->name('ai-technician.kb-images-settings.images')->middleware('auth');
Route::post('/ai-technician-kb-images-settings/upload', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'store'])->name('ai-technician.kb-images-settings.upload')->middleware('auth');
Route::put('/ai-technician-kb-images-settings/images/{id}', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'update'])->name('ai-technician.kb-images-settings.update')->middleware('auth');
Route::delete('/ai-technician-kb-images-settings/images/{id}', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'destroy'])->name('ai-technician.kb-images-settings.delete')->middleware('auth');
Route::post('/ai-technician-kb-images-settings/images/{id}/upload-to-pinecone', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'uploadToPinecone'])->name('ai-technician.kb-images-settings.upload-to-pinecone')->middleware('auth');
Route::post('/ai-technician-kb-images-settings/images/{id}/refresh-status', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'refreshPineconeStatus'])->name('ai-technician.kb-images-settings.refresh-status')->middleware('auth');
Route::post('/ai-technician-kb-images-settings/images/{id}/retry', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'retryUpload'])->name('ai-technician.kb-images-settings.retry')->middleware('auth');
Route::post('/ai-technician-kb-images-settings/settings', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'saveSettings'])->name('ai-technician.kb-images-settings.settings.save')->middleware('auth');
Route::post('/ai-technician-kb-images-settings/settings/test', [App\Http\Controllers\AiTechnician\AiKbImagesController::class, 'testSettings'])->name('ai-technician.kb-images-settings.settings.test')->middleware('auth');

// AI Technician - Chat Routes
Route::get('/ai-technician-chat', [App\Http\Controllers\AiTechnician\AiChatController::class, 'index'])->name('ai-technician.chat')->middleware('auth');
Route::post('/ai-technician-chat/session/create', [App\Http\Controllers\AiTechnician\AiChatController::class, 'createSession'])->name('ai-technician.chat.session.create')->middleware('auth');
Route::get('/ai-technician-chat/session/{sessionId}/messages', [App\Http\Controllers\AiTechnician\AiChatController::class, 'getMessages'])->name('ai-technician.chat.messages')->middleware('auth');
Route::post('/ai-technician-chat/message', [App\Http\Controllers\AiTechnician\AiChatController::class, 'sendMessage'])->name('ai-technician.chat.message.send')->middleware('auth');
Route::post('/ai-technician-chat/message/stream', [App\Http\Controllers\AiTechnician\AiChatController::class, 'sendMessageStream'])->name('ai-technician.chat.message.stream')->middleware('auth');
Route::put('/ai-technician-chat/session/{sessionId}/rename', [App\Http\Controllers\AiTechnician\AiChatController::class, 'renameSession'])->name('ai-technician.chat.session.rename')->middleware('auth');
Route::delete('/ai-technician-chat/session/{sessionId}', [App\Http\Controllers\AiTechnician\AiChatController::class, 'deleteSession'])->name('ai-technician.chat.session.delete')->middleware('auth');
Route::delete('/ai-technician-chat/message/{messageId}', [App\Http\Controllers\AiTechnician\AiChatController::class, 'deleteMessage'])->name('ai-technician.chat.message.delete')->middleware('auth');
Route::delete('/ai-technician-chat/session/{sessionId}/clear', [App\Http\Controllers\AiTechnician\AiChatController::class, 'clearSession'])->name('ai-technician.chat.session.clear')->middleware('auth');
Route::post('/ai-technician-chat/session/{sessionId}/generate-title', [App\Http\Controllers\AiTechnician\AiChatController::class, 'generateTitle'])->name('ai-technician.chat.session.generate-title')->middleware('auth');
Route::get('/ai-technician-chat/search', [App\Http\Controllers\AiTechnician\AiChatController::class, 'searchSessions'])->name('ai-technician.chat.search')->middleware('auth');
Route::get('/ai-technician-chat/sessions/load-more', [App\Http\Controllers\AiTechnician\AiChatController::class, 'loadMoreSessions'])->name('ai-technician.chat.sessions.load-more')->middleware('auth');

// Catch-all route - must be last
Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
