<?php

use App\Modules\OutreachEngine\Http\Controllers\DashboardController;
use App\Modules\OutreachEngine\Http\Controllers\InboxController;
use App\Modules\OutreachEngine\Http\Controllers\LeadsController;
use App\Modules\OutreachEngine\Http\Controllers\ScraperController;
use App\Modules\OutreachEngine\Http\Controllers\SettingsController;
use App\Modules\OutreachEngine\Http\Controllers\TemplatesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| OutreachEngine ("Lead Finder") Routes
|--------------------------------------------------------------------------
|
| Loaded by OutreachEngineServiceProvider, which already wraps this file in the
| 'web' middleware group - do not re-apply it here or the session middleware
| runs twice. Every route carries 'auth' on its own, except the inbound webhook
| which no admin session can reach and which guards itself with a shared secret.
|
| Static segments are declared before their /{id} siblings, and the
| parameterised routes are pinned with whereNumber(), so /outreach/leads/data
| can never be swallowed by /outreach/leads/{id}.
|
*/

// Dashboard
Route::get('/outreach', [DashboardController::class, 'index'])->name('outreach.dashboard')->middleware('auth');
Route::get('/outreach/dashboard-data', [DashboardController::class, 'data'])->name('outreach.dashboard.data')->middleware('auth');

// Scraper (grid search)
Route::get('/outreach/scraper', [ScraperController::class, 'index'])->name('outreach.scraper')->middleware('auth');
Route::post('/outreach/scraper/start', [ScraperController::class, 'start'])->name('outreach.scraper.start')->middleware('auth');
Route::get('/outreach/scraper/progress', [ScraperController::class, 'progress'])->name('outreach.scraper.progress')->middleware('auth');
Route::post('/outreach/scraper/run-batch', [ScraperController::class, 'runBatch'])->name('outreach.scraper.run')->middleware('auth');
Route::post('/outreach/scraper/cancel', [ScraperController::class, 'cancel'])->name('outreach.scraper.cancel')->middleware('auth');
Route::get('/outreach/scraper/regions', [ScraperController::class, 'regions'])->name('outreach.scraper.regions')->middleware('auth');

// Leads - static segments first, then the numeric-only {id} routes
Route::get('/outreach/leads', [LeadsController::class, 'index'])->name('outreach.leads')->middleware('auth');
Route::get('/outreach/leads/data', [LeadsController::class, 'getData'])->name('outreach.leads.data')->middleware('auth');
Route::get('/outreach/leads/export/csv', [LeadsController::class, 'exportCsv'])->name('outreach.leads.export')->middleware('auth');
Route::post('/outreach/leads/enrich-batch', [LeadsController::class, 'enrichBatch'])->name('outreach.leads.enrichBatch')->middleware('auth');
Route::get('/outreach/leads/{id}', [LeadsController::class, 'show'])->whereNumber('id')->name('outreach.leads.show')->middleware('auth');
Route::put('/outreach/leads/{id}', [LeadsController::class, 'update'])->whereNumber('id')->name('outreach.leads.update')->middleware('auth');
Route::delete('/outreach/leads/{id}', [LeadsController::class, 'destroy'])->whereNumber('id')->name('outreach.leads.destroy')->middleware('auth');
Route::post('/outreach/leads/{id}/enrich', [LeadsController::class, 'enrichNow'])->whereNumber('id')->name('outreach.leads.enrich')->middleware('auth');

// Settings and connection tests
Route::get('/outreach/settings', [SettingsController::class, 'index'])->name('outreach.settings')->middleware('auth');
Route::post('/outreach/settings', [SettingsController::class, 'save'])->name('outreach.settings.save')->middleware('auth');
Route::post('/outreach/settings/test-smtp', [SettingsController::class, 'testSmtp'])->name('outreach.settings.testSmtp')->middleware('auth');
Route::post('/outreach/settings/test-imap', [SettingsController::class, 'testImap'])->name('outreach.settings.testImap')->middleware('auth');
Route::post('/outreach/settings/test-dns', [SettingsController::class, 'testDns'])->name('outreach.settings.testDns')->middleware('auth');
Route::post('/outreach/settings/test-places', [SettingsController::class, 'testPlaces'])->name('outreach.settings.testPlaces')->middleware('auth');
Route::post('/outreach/settings/test-llm', [SettingsController::class, 'testLlm'])->name('outreach.settings.testLlm')->middleware('auth');

// Email templates
Route::get('/outreach/templates', [TemplatesController::class, 'index'])->name('outreach.templates')->middleware('auth');
Route::post('/outreach/templates', [TemplatesController::class, 'store'])->name('outreach.templates.store')->middleware('auth');
Route::post('/outreach/templates/preview', [TemplatesController::class, 'preview'])->name('outreach.templates.preview')->middleware('auth');
Route::put('/outreach/templates/{id}', [TemplatesController::class, 'update'])->whereNumber('id')->name('outreach.templates.update')->middleware('auth');
Route::delete('/outreach/templates/{id}', [TemplatesController::class, 'destroy'])->whereNumber('id')->name('outreach.templates.destroy')->middleware('auth');
Route::post('/outreach/templates/{id}/toggle', [TemplatesController::class, 'toggle'])->whereNumber('id')->name('outreach.templates.toggle')->middleware('auth');

// Inbox
Route::get('/outreach/inbox', [InboxController::class, 'index'])->name('outreach.inbox')->middleware('auth');
Route::get('/outreach/inbox/threads', [InboxController::class, 'threads'])->name('outreach.inbox.threads')->middleware('auth');
Route::post('/outreach/inbox/reply', [InboxController::class, 'reply'])->name('outreach.inbox.reply')->middleware('auth');
Route::post('/outreach/inbox/fetch', [InboxController::class, 'fetchNow'])->name('outreach.inbox.fetch')->middleware('auth');
Route::get('/outreach/inbox/thread/{leadId}', [InboxController::class, 'thread'])->whereNumber('leadId')->name('outreach.inbox.thread')->middleware('auth');
Route::post('/outreach/inbox/read/{leadId}', [InboxController::class, 'markRead'])->whereNumber('leadId')->name('outreach.inbox.read')->middleware('auth');

// Inbound webhook for mail providers that push instead of waiting to be polled.
// There is no admin session to authenticate against and no CSRF token to
// present, so the controller compares a shared-secret query parameter with
// hash_equals before it trusts a single byte of the payload.
Route::post('/outreach/inbox/webhook', [InboxController::class, 'webhook'])
    ->name('outreach.inbox.webhook')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ]);
