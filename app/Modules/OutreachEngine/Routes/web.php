<?php

use App\Modules\OutreachEngine\Http\Controllers\BatchesController;
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
| Every URI here is ONE segment, hyphen-separated, matching the rest of this
| app (/crypto-set, /anisenso-courses, /crm-leads, /resort-guru-reviews-edit).
| There is not a nested path anywhere else in this codebase and there is not
| one here either: record ids travel as a query string on GET and in the body
| on POST, the way resortGuruAdmin does it.
|
| Writes are POST rather than PUT/DELETE for the same reason - the house forms
| and $.ajax calls post, and method spoofing buys nothing but another way for a
| proxy to reject the request.
|
*/

// Dashboard
Route::get('/outreach', [DashboardController::class, 'index'])->name('outreach.dashboard')->middleware('auth');
Route::get('/outreach-dashboard-data', [DashboardController::class, 'data'])->name('outreach.dashboard.data')->middleware('auth');

// Grid scraper
Route::get('/outreach-scraper', [ScraperController::class, 'index'])->name('outreach.scraper')->middleware('auth');
Route::post('/outreach-scraper-start', [ScraperController::class, 'start'])->name('outreach.scraper.start')->middleware('auth');
Route::get('/outreach-scraper-progress', [ScraperController::class, 'progress'])->name('outreach.scraper.progress')->middleware('auth');
Route::post('/outreach-scraper-run-batch', [ScraperController::class, 'runBatch'])->name('outreach.scraper.run')->middleware('auth');
Route::post('/outreach-scraper-cancel', [ScraperController::class, 'cancel'])->name('outreach.scraper.cancel')->middleware('auth');
Route::get('/outreach-scraper-regions', [ScraperController::class, 'regions'])->name('outreach.scraper.regions')->middleware('auth');

// Leads
Route::get('/outreach-leads', [LeadsController::class, 'index'])->name('outreach.leads')->middleware('auth');
Route::get('/outreach-leads-data', [LeadsController::class, 'getData'])->name('outreach.leads.data')->middleware('auth');
Route::get('/outreach-leads-export', [LeadsController::class, 'exportCsv'])->name('outreach.leads.export')->middleware('auth');
Route::get('/outreach-leads-show', [LeadsController::class, 'show'])->name('outreach.leads.show')->middleware('auth');
Route::post('/outreach-leads-update', [LeadsController::class, 'update'])->name('outreach.leads.update')->middleware('auth');
Route::post('/outreach-leads-delete', [LeadsController::class, 'destroy'])->name('outreach.leads.destroy')->middleware('auth');
Route::post('/outreach-leads-enrich', [LeadsController::class, 'enrichNow'])->name('outreach.leads.enrich')->middleware('auth');
Route::post('/outreach-leads-enrich-batch', [LeadsController::class, 'enrichBatch'])->name('outreach.leads.enrichBatch')->middleware('auth');

// Batch Search - every sweep this account has run
Route::get('/outreach-batches', [BatchesController::class, 'index'])->name('outreach.batches')->middleware('auth');
Route::get('/outreach-batches-data', [BatchesController::class, 'data'])->name('outreach.batches.data')->middleware('auth');
Route::get('/outreach-batches-show', [BatchesController::class, 'show'])->name('outreach.batches.show')->middleware('auth');
Route::post('/outreach-batches-rename', [BatchesController::class, 'rename'])->name('outreach.batches.rename')->middleware('auth');
Route::post('/outreach-batches-delete', [BatchesController::class, 'destroy'])->name('outreach.batches.destroy')->middleware('auth');

// Settings
Route::get('/outreach-settings', [SettingsController::class, 'index'])->name('outreach.settings')->middleware('auth');
Route::post('/outreach-settings-save', [SettingsController::class, 'save'])->name('outreach.settings.save')->middleware('auth');
Route::post('/outreach-settings-test-smtp', [SettingsController::class, 'testSmtp'])->name('outreach.settings.testSmtp')->middleware('auth');
Route::post('/outreach-settings-test-imap', [SettingsController::class, 'testImap'])->name('outreach.settings.testImap')->middleware('auth');
Route::post('/outreach-settings-test-dns', [SettingsController::class, 'testDns'])->name('outreach.settings.testDns')->middleware('auth');
Route::post('/outreach-settings-test-places', [SettingsController::class, 'testPlaces'])->name('outreach.settings.testPlaces')->middleware('auth');
Route::post('/outreach-settings-test-llm', [SettingsController::class, 'testLlm'])->name('outreach.settings.testLlm')->middleware('auth');
Route::post('/outreach-settings-test-verifier', [SettingsController::class, 'testVerifier'])->name('outreach.settings.testVerifier')->middleware('auth');

// Email templates
Route::get('/outreach-templates', [TemplatesController::class, 'index'])->name('outreach.templates')->middleware('auth');
Route::post('/outreach-templates-store', [TemplatesController::class, 'store'])->name('outreach.templates.store')->middleware('auth');
Route::post('/outreach-templates-update', [TemplatesController::class, 'update'])->name('outreach.templates.update')->middleware('auth');
Route::post('/outreach-templates-delete', [TemplatesController::class, 'destroy'])->name('outreach.templates.destroy')->middleware('auth');
Route::post('/outreach-templates-toggle', [TemplatesController::class, 'toggle'])->name('outreach.templates.toggle')->middleware('auth');
Route::post('/outreach-templates-preview', [TemplatesController::class, 'preview'])->name('outreach.templates.preview')->middleware('auth');

// Unified inbox
Route::get('/outreach-inbox', [InboxController::class, 'index'])->name('outreach.inbox')->middleware('auth');
Route::get('/outreach-inbox-threads', [InboxController::class, 'threads'])->name('outreach.inbox.threads')->middleware('auth');
Route::get('/outreach-inbox-thread', [InboxController::class, 'thread'])->name('outreach.inbox.thread')->middleware('auth');
Route::post('/outreach-inbox-reply', [InboxController::class, 'reply'])->name('outreach.inbox.reply')->middleware('auth');
Route::post('/outreach-inbox-fetch', [InboxController::class, 'fetchNow'])->name('outreach.inbox.fetch')->middleware('auth');
Route::post('/outreach-inbox-read', [InboxController::class, 'markRead'])->name('outreach.inbox.read')->middleware('auth');

// Inbound webhook for mail providers that push instead of waiting to be polled.
// There is no admin session to authenticate against and no CSRF token to
// present, so the controller compares a shared-secret query parameter with
// hash_equals before it trusts a single byte of the payload.
Route::post('/outreach-inbox-webhook', [InboxController::class, 'webhook'])
    ->name('outreach.inbox.webhook')
    ->withoutMiddleware([
        \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ]);
