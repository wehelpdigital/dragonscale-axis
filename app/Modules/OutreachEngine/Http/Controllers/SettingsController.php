<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachSetting;
use App\Modules\OutreachEngine\Services\DnsAuthService;
use App\Modules\OutreachEngine\Services\GooglePlacesService;
use App\Modules\OutreachEngine\Services\ImapClientService;
use App\Modules\OutreachEngine\Services\LlmService;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Services\SmtpMailerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Lead Finder settings: every credential and guard rail the module runs on.
 *
 * Two rules shape this whole file.
 *
 * 1. Secrets are write-only from the browser's point of view. The form renders
 *    maskedKey() as a placeholder and posts an EMPTY field when the operator does not
 *    want to change it, so a blank secret always means "keep what is stored" and can
 *    never blank out a working key.
 * 2. The five test endpoints are diagnostics, not transactions. They are reached from
 *    a half-filled form far more often than from a complete one, so every one of them
 *    answers 200 with success=false and a sentence explaining what is missing. A test
 *    that 500s teaches the operator nothing.
 */
class SettingsController extends Controller
{
    /** Fields that describe a mail connection - changing any of them invalidates the last test result. */
    const CONNECTION_FIELDS = [
        'smtpHost', 'smtpPort', 'smtpUsername', 'smtpPassword', 'smtpEncryption', 'smtpFromEmail',
        'imapHost', 'imapPort', 'imapUsername', 'imapPassword', 'imapEncryption', 'imapFolder',
    ];

    /** Where testPlaces aims its probe search - Manila city hall, a spot guaranteed to return results. */
    const PROBE_LATITUDE = 14.5995;
    const PROBE_LONGITUDE = 120.9842;

    /** @var \App\Modules\OutreachEngine\Services\SettingsResolver */
    protected $resolver;

    public function __construct(SettingsResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Render the settings screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $settings = $this->resolver->forUser((int) Auth::id());

        // The view must never touch $settings->smtpPassword and friends directly, so the
        // display-safe form of each secret is handed over pre-computed.
        $maskedKeys = [];
        foreach (OutreachSetting::ENCRYPTED_ATTRIBUTES as $attribute) {
            $maskedKeys[$attribute] = $settings->maskedKey($attribute);
        }

        $dns = new DnsAuthService();

        return view('outreach::settings', [
            'settings' => $settings,
            'maskedKeys' => $maskedKeys,
            'providerLabels' => OutreachSetting::getProviderLabels(),
            'dayLabels' => OutreachSetting::getDayLabels(),
            'selectedDays' => $settings->sendDaysArray(),
            'effectiveDailyCap' => $settings->effectiveDailyCap(),
            'sendingDomain' => $dns->domainFromEmail((string) $settings->smtpFromEmail),
            'webhookToken' => InboxController::webhookToken($settings),
            'webhookUrl' => InboxController::webhookUrl($settings),
            'cronCommand' => '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
            'activeTab' => $request->query('tab', 'google'),
        ]);
    }

    /**
     * Save the settings row.
     *
     * outreach_settings carries unique(usersId, delete_status), which lets a user hold
     * one active row plus one deleted row. The active row is therefore UPDATED in place
     * - a delete-and-recreate would collide with the surviving deleted row on the
     * second save.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            // Checkboxes post as an array of ISO day numbers; the column stores CSV.
            $input = $request->all();
            $input['sendDays'] = $this->normalizeSendDays($request->input('sendDays'));
            $input['sendWindowStart'] = $this->normalizeTime($request->input('sendWindowStart'));
            $input['sendWindowEnd'] = $this->normalizeTime($request->input('sendWindowEnd'));

            $validator = Validator::make($input, [
                'googlePlacesApiKey' => 'nullable|string|max:400',
                'googleSearchApiKey' => 'nullable|string|max:400',
                'googleSearchEngineId' => 'nullable|string|max:255',
                'llmProvider' => 'required|in:claude,openai,gemini',
                'llmApiKey' => 'nullable|string|max:400',
                'llmModel' => 'nullable|string|max:120',

                'smtpHost' => 'nullable|string|max:255',
                'smtpPort' => 'nullable|integer|min:1|max:65535',
                'smtpUsername' => 'nullable|string|max:255',
                'smtpPassword' => 'nullable|string|max:400',
                'smtpEncryption' => 'required|in:tls,ssl,none',
                'smtpFromName' => 'nullable|string|max:255',
                'smtpFromEmail' => 'nullable|email|max:255',

                'imapHost' => 'nullable|string|max:255',
                'imapPort' => 'nullable|integer|min:1|max:65535',
                'imapUsername' => 'nullable|string|max:255',
                'imapPassword' => 'nullable|string|max:400',
                'imapEncryption' => 'required|in:ssl,tls,none',
                'imapFolder' => 'nullable|string|max:120',

                'dailySendCap' => 'required|integer|min:1|max:' . OutreachSetting::MAX_DAILY_SEND_CAP,
                'sendWindowStart' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/'],
                'sendWindowEnd' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/'],
                'sendDays' => 'required|array|min:1',
                'sendDays.*' => 'integer|min:1|max:7',
                'minDelayMinutes' => 'required|integer|min:0|max:1440',
                'maxDelayMinutes' => 'required|integer|min:0|max:1440|gte:minDelayMinutes',

                'defaultGridRadiusKm' => 'required|numeric|min:0.5|max:50',
                'minGridRadiusKm' => 'required|numeric|min:0.1|max:50|lte:defaultGridRadiusKm',
                'maxSubdivisionDepth' => 'required|integer|min:0|max:8',

                'warmupEnabled' => 'nullable|boolean',
                'warmupStartCap' => 'required|integer|min:1|max:' . OutreachSetting::MAX_DAILY_SEND_CAP,
                'warmupIncrementPerDay' => 'required|integer|min:0|max:50',
                'warmupStartedOn' => 'nullable|date',
                'aiRephraseEnabled' => 'nullable|boolean',
                'outreachEnabled' => 'nullable|boolean',
            ], [
                'llmProvider.required' => 'Choose an AI provider.',
                'llmProvider.in' => 'Choose Claude, OpenAI or Gemini.',
                'smtpEncryption.in' => 'SMTP encryption must be TLS, SSL or none.',
                'imapEncryption.in' => 'IMAP encryption must be SSL, TLS or none.',
                'smtpFromEmail.email' => 'The From address must be a valid email address.',
                'dailySendCap.required' => 'Set a daily send cap.',
                'dailySendCap.min' => 'The daily send cap must be at least 1.',
                'dailySendCap.max' => 'The daily send cap cannot exceed ' . OutreachSetting::MAX_DAILY_SEND_CAP . ' - anything higher gets an account flagged as a spam source.',
                'sendWindowStart.required' => 'Set the time the sending window opens.',
                'sendWindowStart.regex' => 'The window start must be a time like 08:30.',
                'sendWindowEnd.required' => 'Set the time the sending window closes.',
                'sendWindowEnd.regex' => 'The window end must be a time like 17:00.',
                'sendDays.required' => 'Pick at least one sending day.',
                'sendDays.min' => 'Pick at least one sending day.',
                'maxDelayMinutes.gte' => 'The maximum delay cannot be shorter than the minimum delay.',
                'minGridRadiusKm.lte' => 'The smallest grid radius cannot be larger than the default radius.',
                'warmupStartCap.min' => 'The warm-up starting cap must be at least 1.',
            ]);

            $validator->after(function ($v) use ($input) {
                $start = (string) ($input['sendWindowStart'] ?? '');
                $end = (string) ($input['sendWindowEnd'] ?? '');

                // Plain string comparison is exact for zero-padded HH:MM:SS and avoids
                // dragging a date into a question that only concerns the clock.
                if ($start !== '' && $end !== '' && $end <= $start) {
                    $v->errors()->add('sendWindowEnd', 'The sending window must close after it opens.');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $settings = OutreachSetting::forUserOrNew($userId);

            $settings->usersId = $userId;
            $settings->delete_status = 'active';

            $settings->googleSearchEngineId = $this->nullableString($request->input('googleSearchEngineId'));
            $settings->llmProvider = $request->input('llmProvider');
            $settings->llmModel = $this->nullableString($request->input('llmModel'));

            $settings->smtpHost = $this->nullableString($request->input('smtpHost'));
            $settings->smtpPort = $request->filled('smtpPort') ? (int) $request->input('smtpPort') : null;
            $settings->smtpUsername = $this->nullableString($request->input('smtpUsername'));
            $settings->smtpEncryption = $request->input('smtpEncryption');
            $settings->smtpFromName = $this->nullableString($request->input('smtpFromName'));
            $settings->smtpFromEmail = $this->nullableString($request->input('smtpFromEmail'));

            $settings->imapHost = $this->nullableString($request->input('imapHost'));
            $settings->imapPort = $request->filled('imapPort') ? (int) $request->input('imapPort') : null;
            $settings->imapUsername = $this->nullableString($request->input('imapUsername'));
            $settings->imapEncryption = $request->input('imapEncryption');
            $settings->imapFolder = $this->nullableString($request->input('imapFolder')) ?: 'INBOX';

            $settings->dailySendCap = (int) $request->input('dailySendCap');
            $settings->sendWindowStart = $input['sendWindowStart'];
            $settings->sendWindowEnd = $input['sendWindowEnd'];
            $settings->sendDays = implode(',', $input['sendDays']);
            $settings->minDelayMinutes = (int) $request->input('minDelayMinutes');
            $settings->maxDelayMinutes = (int) $request->input('maxDelayMinutes');

            $settings->defaultGridRadiusKm = (float) $request->input('defaultGridRadiusKm');
            $settings->minGridRadiusKm = (float) $request->input('minGridRadiusKm');
            $settings->maxSubdivisionDepth = (int) $request->input('maxSubdivisionDepth');

            $settings->warmupEnabled = $request->boolean('warmupEnabled');
            $settings->warmupStartCap = (int) $request->input('warmupStartCap');
            $settings->warmupIncrementPerDay = (int) $request->input('warmupIncrementPerDay');
            $settings->aiRephraseEnabled = $request->boolean('aiRephraseEnabled');
            $settings->outreachEnabled = $request->boolean('outreachEnabled');

            // Warm-up only ramps once it has a start date. Stamping today the moment it is
            // switched on saves the operator from wondering why the cap never moves; the
            // stored date survives a later switch-off so re-enabling resumes the ramp
            // instead of restarting it.
            if ($request->filled('warmupStartedOn')) {
                $settings->warmupStartedOn = Carbon::parse($request->input('warmupStartedOn'))->toDateString();
            } elseif ($settings->warmupEnabled && empty($settings->warmupStartedOn)) {
                $settings->warmupStartedOn = Carbon::now('Asia/Manila')->toDateString();
            }

            // Blank secret = keep the stored one. Only a non-empty field overwrites.
            foreach (OutreachSetting::ENCRYPTED_ATTRIBUTES as $attribute) {
                $submitted = trim((string) $request->input($attribute, ''));

                if ($submitted !== '') {
                    $settings->{$attribute} = $submitted;
                }
            }

            // The master switch is the one place where a wrong setting costs real money and
            // reputation, so it refuses to come on over an unusable mail configuration.
            if ($settings->outreachEnabled && !$settings->smtpConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Outreach cannot be switched on until SMTP is complete: host, username, password and From address are all required.',
                ], 422);
            }

            // A changed connection makes the previous "Connected" badge a lie.
            if ($settings->exists && $settings->isDirty(self::CONNECTION_FIELDS)) {
                $settings->lastTestStatus = OutreachSetting::STATUS_PENDING;
                $settings->lastTestError = null;
            }

            $settings->save();

            $maskedKeys = [];
            foreach (OutreachSetting::ENCRYPTED_ATTRIBUTES as $attribute) {
                $maskedKeys[$attribute] = $settings->maskedKey($attribute);
            }

            Log::info('[OutreachEngine] Settings saved for user ' . $userId, [
                'outreachEnabled' => $settings->outreachEnabled,
                'dailySendCap' => $settings->dailySendCap,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully.',
                'data' => [
                    'maskedKeys' => $maskedKeys,
                    'effectiveDailyCap' => $settings->effectiveDailyCap(),
                    'sendDaysLabel' => $settings->send_days_label,
                    'sendWindowLabel' => $settings->send_window_label,
                    'outreachEnabled' => (bool) $settings->outreachEnabled,
                    'smtpConfigured' => $settings->smtpConfigured(),
                    'imapConfigured' => $settings->imapConfigured(),
                    'webhookUrl' => InboxController::webhookUrl($settings),
                    'webhookToken' => InboxController::webhookToken($settings),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Saving settings failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving your settings.',
            ], 500);
        }
    }

    /**
     * Test the SMTP credentials.
     *
     * With a testEmail supplied this actually delivers a message, which is the only way
     * to prove the whole path works; without one it stops at connect + authenticate.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSmtp(Request $request)
    {
        try {
            $settings = $this->resolver->forUser((int) Auth::id());

            if (!$settings->smtpConfigured()) {
                return $this->testResult(false, 'Fill in the SMTP host, username, password and From address, then save before testing.');
            }

            $mailer = new SmtpMailerService($settings);
            $testEmail = trim((string) $request->input('testEmail', ''));

            if ($testEmail !== '') {
                if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    return $this->testResult(false, 'That test address is not a valid email address.');
                }

                $sent = $mailer->send(
                    $testEmail,
                    (string) $settings->smtpFromName,
                    'Lead Finder SMTP test',
                    '<p>This is a test message from Lead Finder.</p>'
                    . '<p>If you are reading it, outbound mail is working from <strong>'
                    . e((string) $settings->smtpHost) . '</strong>.</p>'
                    . '<p style="color:#6c757d;font-size:12px;">Sent ' . Carbon::now('Asia/Manila')->format('M j, Y g:i A') . ' (Asia/Manila)</p>'
                );

                $this->recordTestResult($settings, (bool) $sent['success'], $sent['error'] ?? null);

                return $this->testResult(
                    (bool) $sent['success'],
                    $sent['success']
                        ? 'Test email sent to ' . $testEmail . '. Check the inbox (and the spam folder).'
                        : ($sent['error'] ?: 'The test email could not be sent.'),
                    [
                        'messageId' => $sent['messageId'] ?? null,
                        'response' => $sent['response'] ?? null,
                    ]
                );
            }

            $result = $mailer->testConnection();
            $this->recordTestResult($settings, (bool) $result['success'], $result['error'] ?? null);

            return $this->testResult(
                (bool) $result['success'],
                $result['success']
                    ? 'Connected and authenticated with ' . $settings->smtpHost . ':' . $settings->smtpPort . '.'
                    : ($result['error'] ?: 'The SMTP server refused the connection.'),
                [
                    'host' => $settings->smtpHost,
                    'port' => $settings->smtpPort,
                    'encryption' => $settings->smtpEncryption,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] SMTP test failed: ' . $e->getMessage());

            return $this->testResult(false, 'The SMTP test could not be completed: ' . $e->getMessage());
        }
    }

    /**
     * Test the IMAP credentials - connect, authenticate, open the folder.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testImap(Request $request)
    {
        try {
            $settings = $this->resolver->forUser((int) Auth::id());

            if (!$settings->imapConfigured()) {
                return $this->testResult(false, 'Fill in the IMAP host, username and password, then save before testing.');
            }

            $imap = new ImapClientService($settings);
            $result = $imap->testConnection();

            $this->recordTestResult($settings, (bool) $result['success'], $result['error'] ?? null);

            return $this->testResult(
                (bool) $result['success'],
                $result['success']
                    ? 'Signed in and opened ' . $settings->imapFolder . ' on ' . $settings->imapHost . '.'
                    : ($result['error'] ?: 'The IMAP server refused the connection.'),
                [
                    'host' => $settings->imapHost,
                    'port' => $settings->imapPort,
                    'encryption' => $settings->imapEncryption,
                    'folder' => $settings->imapFolder,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] IMAP test failed: ' . $e->getMessage());

            return $this->testResult(false, 'The IMAP test could not be completed: ' . $e->getMessage());
        }
    }

    /**
     * Check SPF, DKIM, DMARC and MX for the sending domain.
     *
     * The domain comes from the From address - the one typed into the form when the
     * operator is still filling it in, otherwise the saved one, so the check can be run
     * before the first save.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testDns(Request $request)
    {
        try {
            $settings = $this->resolver->forUser((int) Auth::id());
            $dns = new DnsAuthService();

            $fromEmail = trim((string) $request->input('smtpFromEmail', ''));
            if ($fromEmail === '') {
                $fromEmail = (string) $settings->smtpFromEmail;
            }

            $domain = $dns->domainFromEmail($fromEmail);

            if ($domain === '') {
                return $this->testResult(false, 'Add a From address in the SMTP tab first - the sending domain is taken from it.');
            }

            $selector = trim((string) $request->input('dkimSelector', ''));
            if ($selector === '') {
                $selector = 'default';
            }

            $checks = $dns->check($domain, $selector);

            // One failing record is enough to hurt deliverability, so the headline verdict
            // is the worst of the four rather than an average.
            $statuses = [];
            foreach ($checks as $check) {
                $statuses[] = $check['status'] ?? DnsAuthService::STATUS_FAIL;
            }

            $overall = in_array(DnsAuthService::STATUS_FAIL, $statuses, true)
                ? DnsAuthService::STATUS_FAIL
                : (in_array(DnsAuthService::STATUS_WARN, $statuses, true) ? DnsAuthService::STATUS_WARN : DnsAuthService::STATUS_PASS);

            $summaries = [
                DnsAuthService::STATUS_PASS => 'SPF, DKIM, DMARC and MX all look healthy for ' . $domain . '.',
                DnsAuthService::STATUS_WARN => 'DNS for ' . $domain . ' works but has weak spots - see the details below.',
                DnsAuthService::STATUS_FAIL => 'DNS for ' . $domain . ' is missing records that receivers rely on - see the details below.',
            ];

            return $this->testResult(
                $overall !== DnsAuthService::STATUS_FAIL,
                $summaries[$overall],
                [
                    'domain' => $domain,
                    'selector' => $selector,
                    'overall' => $overall,
                    'checks' => $checks,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] DNS test failed: ' . $e->getMessage());

            return $this->testResult(false, 'The DNS check could not be completed: ' . $e->getMessage());
        }
    }

    /**
     * Spend one Places call to prove the key works and billing is enabled.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testPlaces(Request $request)
    {
        try {
            $settings = $this->resolver->forUser((int) Auth::id());

            if (!$settings->hasPlacesKey()) {
                return $this->testResult(false, 'Add your Google Places API key and save it, then run the test.');
            }

            $places = new GooglePlacesService($settings);
            $keyword = trim((string) $request->input('keyword', '')) ?: 'restaurant';

            $result = $places->nearbySearch(
                self::PROBE_LATITUDE,
                self::PROBE_LONGITUDE,
                1000,
                $keyword
            );

            $ok = empty($result['error']);
            $count = is_array($result['results'] ?? null) ? count($result['results']) : 0;

            $this->recordTestResult($settings, $ok, $ok ? null : (string) $result['error']);

            return $this->testResult(
                $ok,
                $ok
                    ? 'The key works. A probe search near Manila returned ' . $count . ' ' . ($count === 1 ? 'place' : 'places') . '.'
                    : (string) $result['error'],
                [
                    'status' => $result['status'] ?? null,
                    'resultCount' => $count,
                    'keyword' => $keyword,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Places test failed: ' . $e->getMessage());

            return $this->testResult(false, 'The Places test could not be completed: ' . $e->getMessage());
        }
    }

    /**
     * Ask the configured model for two letters back - the cheapest possible proof that
     * the provider, key and model name all line up.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testLlm(Request $request)
    {
        try {
            $settings = $this->resolver->forUser((int) Auth::id());

            if (!$settings->hasLlm()) {
                return $this->testResult(false, 'Choose an AI provider, add its API key and save, then run the test.');
            }

            $llm = new LlmService($settings);

            if (!$llm->isConfigured()) {
                return $this->testResult(false, 'The AI provider is not fully configured. Check the provider and API key.');
            }

            $reply = trim($llm->complete(
                'You are a connection test. Answer with a single short word.',
                'Reply with the word OK and nothing else.',
                16,
                0.0
            ));

            if ($reply === '') {
                $this->recordTestResult($settings, false, 'The AI provider returned an empty response.');

                return $this->testResult(
                    false,
                    'No answer came back from ' . $settings->provider_label . '. Check the API key, the model name and the account credit.',
                    [
                        'provider' => $llm->provider(),
                        'model' => $llm->model(),
                    ]
                );
            }

            $this->recordTestResult($settings, true, null);

            return $this->testResult(
                true,
                $settings->provider_label . ' answered using model ' . $llm->model() . '.',
                [
                    'provider' => $llm->provider(),
                    'model' => $llm->model(),
                    'reply' => mb_substr($reply, 0, 200),
                ]
            );
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] LLM test failed: ' . $e->getMessage());

            return $this->testResult(false, 'The AI test could not be completed: ' . $e->getMessage());
        }
    }

    // ==================== INTERNALS ====================

    /**
     * Uniform envelope for the five diagnostics.
     *
     * Always HTTP 200: a failed test is a successful diagnosis, and the settings screen
     * renders the message either way rather than falling into a jQuery error handler
     * that has no message to show.
     *
     * @param  array<string,mixed>  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function testResult(bool $success, string $message, array $data = [])
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Stamp the outcome of a connection test onto the settings row.
     *
     * Skipped for an unsaved row - there is nothing to stamp yet, and creating one here
     * would write a half-filled configuration the operator never asked to save.
     */
    protected function recordTestResult(OutreachSetting $settings, bool $success, ?string $error): void
    {
        if (!$settings->exists) {
            return;
        }

        try {
            $settings->update([
                'lastTestedAt' => Carbon::now('Asia/Manila'),
                'lastTestStatus' => $success ? OutreachSetting::STATUS_SUCCESS : OutreachSetting::STATUS_FAILED,
                'lastTestError' => $success ? null : mb_substr((string) $error, 0, 2000),
            ]);
        } catch (\Throwable $e) {
            // The test result itself is the answer the operator wanted; failing to record
            // it must not turn a working diagnosis into an error page.
            Log::warning('[OutreachEngine] Could not record the connection test result: ' . $e->getMessage());
        }
    }

    /**
     * Sending days as a sorted, unique list of ISO day numbers.
     *
     * Accepts the checkbox array the form posts and the CSV string the column stores, so
     * the endpoint works for both the UI and a scripted call.
     *
     * @param  mixed  $raw
     * @return array<int,int>
     */
    protected function normalizeSendDays($raw): array
    {
        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        if (!is_array($raw)) {
            return [];
        }

        $days = [];
        foreach ($raw as $piece) {
            if (!is_scalar($piece)) {
                continue;
            }

            $day = (int) trim((string) $piece);
            if ($day >= 1 && $day <= 7) {
                $days[$day] = $day;
            }
        }

        $days = array_values($days);
        sort($days);

        return $days;
    }

    /**
     * An <input type="time"> posts HH:MM; the TIME column wants HH:MM:SS. Anything that
     * is not a time is returned untouched so the regex rule can report it properly.
     *
     * @param  mixed  $raw
     * @return string
     */
    protected function normalizeTime($raw): string
    {
        $value = trim((string) $raw);

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value . ':00';
        }

        return $value;
    }

    /**
     * Trim to a string, or null when nothing was typed - blank text columns should read
     * as "not set", not as an empty string that passes an !empty() check by accident.
     *
     * @param  mixed  $value
     * @return string|null
     */
    protected function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
