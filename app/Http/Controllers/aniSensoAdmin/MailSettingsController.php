<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsEmailTemplate;
use App\Models\AsMailSmtpSetting;
use App\Services\AnisystemSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailSettingsController extends Controller
{
    /**
     * Display the mail settings page (SMTP + templates tabs).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $activeTab = $request->query('tab', 'smtp');
        $group = $request->query('group', 'AniSystem');

        // All known groups: distinct groupKeys from both tables + the seeded defaults.
        $groups = AsMailSmtpSetting::active()->pluck('groupKey')
            ->merge(AsEmailTemplate::active()->pluck('groupKey'))
            ->merge(['AniSystem'])
            ->unique()
            ->sort()
            ->values();

        $smtpSettings = AsMailSmtpSetting::active()->forGroup($group)->first();

        return view('aniSensoAdmin.mailSettings.index', compact('activeTab', 'group', 'groups', 'smtpSettings'));
    }

    /**
     * Save SMTP settings for a group.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSmtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'groupKey' => 'required|string|max:100',
                'smtpHost' => 'required|string|max:255',
                'smtpPort' => 'required|integer|min:1|max:65535',
                'smtpUsername' => 'nullable|string|max:255',
                'smtpPassword' => 'nullable|string|max:255',
                'smtpEncryption' => 'required|in:tls,ssl,none',
                'smtpFromEmail' => 'required|email|max:255',
                'smtpFromName' => 'required|string|max:255',
                'isActive' => 'boolean',
            ], [
                'groupKey.required' => 'Mail group is required.',
                'smtpHost.required' => 'SMTP Host is required.',
                'smtpPort.required' => 'SMTP Port is required.',
                'smtpFromEmail.required' => 'From Email is required.',
                'smtpFromEmail.email' => 'Please enter a valid email address.',
                'smtpFromName.required' => 'From Name is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $smtpSettings = AsMailSmtpSetting::firstOrNew([
                'groupKey' => $request->groupKey,
                'deleteStatus' => 1
            ]);

            $smtpSettings->smtpHost = $request->smtpHost;
            $smtpSettings->smtpPort = $request->smtpPort;
            $smtpSettings->smtpUsername = $request->smtpUsername;

            // Only overwrite the password when a new one is typed.
            if ($request->filled('smtpPassword')) {
                $smtpSettings->smtpPassword = $request->smtpPassword;
            }

            $smtpSettings->smtpEncryption = $request->smtpEncryption;
            $smtpSettings->smtpFromEmail = $request->smtpFromEmail;
            $smtpSettings->smtpFromName = $request->smtpFromName;
            $smtpSettings->isActive = $request->boolean('isActive');
            $smtpSettings->isVerified = false; // Reset verification when settings change
            $smtpSettings->save();

            Log::info('AniSenso mail SMTP settings saved', [
                'group' => $request->groupKey,
                'smtp_host' => $request->smtpHost,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMTP settings saved successfully!',
                'data' => [
                    'isConfigured' => $smtpSettings->isConfigured()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving AniSenso mail SMTP settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving SMTP settings.'
            ], 500);
        }
    }

    /**
     * Send a test email using a group's SMTP settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSmtp(Request $request)
    {
        try {
            $group = $request->input('groupKey', 'AniSystem');

            $smtpSettings = AsMailSmtpSetting::active()->forGroup($group)->first();

            if (!$smtpSettings || !$smtpSettings->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please save SMTP settings first.'
                ], 400);
            }

            $testEmail = $request->input('testEmail', $smtpSettings->smtpFromEmail);

            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = $smtpSettings->smtpHost;
                $mail->Port = $smtpSettings->smtpPort;
                $mail->CharSet = 'UTF-8';

                if ($smtpSettings->smtpUsername) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpSettings->smtpUsername;
                    $mail->Password = $smtpSettings->smtpPassword; // plain text column
                }

                if ($smtpSettings->smtpEncryption !== 'none') {
                    $mail->SMTPSecure = $smtpSettings->smtpEncryption === 'ssl'
                        ? PHPMailer::ENCRYPTION_SMTPS
                        : PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->setFrom($smtpSettings->smtpFromEmail, $smtpSettings->smtpFromName ?: $group);
                $mail->addAddress($testEmail);

                $mail->isHTML(true);
                $mail->Subject = 'SMTP Test - ' . $group;
                $mail->Body = '<h2>SMTP Connection Test</h2>
                    <p>This is a test email for mail group: <strong>' . e($group) . '</strong></p>
                    <p>If you received this email, your SMTP settings are configured correctly!</p>
                    <p><small>Sent at: ' . now()->format('Y-m-d H:i:s') . '</small></p>';
                $mail->AltBody = 'SMTP Test - This is a test email for mail group: ' . $group;

                $mail->send();

                $smtpSettings->isVerified = true;
                $smtpSettings->lastTestedAt = Carbon::now('Asia/Manila');
                $smtpSettings->save();

                Log::info('AniSenso mail SMTP test successful', [
                    'group' => $group,
                    'test_email' => $testEmail,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $testEmail
                ]);
            } catch (PHPMailerException $e) {
                Log::error('AniSenso mail SMTP test failed: ' . $mail->ErrorInfo);

                return response()->json([
                    'success' => false,
                    'message' => 'SMTP test failed: ' . $mail->ErrorInfo
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error testing AniSenso mail SMTP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while testing SMTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle a group's SMTP active status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSmtp(Request $request)
    {
        try {
            $group = $request->input('groupKey', 'AniSystem');

            $smtpSettings = AsMailSmtpSetting::active()->forGroup($group)->first();

            if (!$smtpSettings) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP settings not found. Please configure SMTP first.'
                ], 404);
            }

            if (!$smtpSettings->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please complete SMTP configuration before enabling.'
                ], 400);
            }

            $smtpSettings->isActive = !$smtpSettings->isActive;
            $smtpSettings->save();

            $status = $smtpSettings->isActive ? 'enabled' : 'disabled';

            return response()->json([
                'success' => true,
                'message' => "SMTP has been {$status}.",
                'data' => [
                    'isActive' => (bool) $smtpSettings->isActive
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling AniSenso mail SMTP status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while toggling SMTP status.'
            ], 500);
        }
    }

    /**
     * List email templates as JSON (optionally filtered by group).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function templates(Request $request)
    {
        try {
            $query = AsEmailTemplate::active()
                ->orderBy('groupKey')
                ->orderBy('templateName');

            if ($request->filled('group')) {
                $query->forGroup($request->group);
            }

            $templates = $query->get()->map(function ($t) {
                return [
                    'id' => $t->id,
                    'groupKey' => $t->groupKey,
                    'templateKey' => $t->templateKey,
                    'templateName' => $t->templateName,
                    'subject' => $t->subject,
                    'bodyHtml' => $t->bodyHtml,
                    'availableTags' => $t->availableTags,
                    'isActive' => (bool) $t->isActive,
                    'updatedAt' => $t->updated_at ? $t->updated_at->format('M j, Y g:i A') : null,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Templates loaded',
                'data' => [
                    'templates' => $templates
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading email templates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while loading templates.'
            ], 500);
        }
    }

    /**
     * Update a template's name, subject and body.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTemplate(Request $request, $id)
    {
        try {
            $template = AsEmailTemplate::active()->find($id);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'templateName' => 'required|string|max:255',
                'subject' => 'required|string|max:500',
                'bodyHtml' => 'required|string',
            ], [
                'templateName.required' => 'Template name is required.',
                'subject.required' => 'Subject is required.',
                'bodyHtml.required' => 'Email body is required.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $template->templateName = $request->templateName;
            $template->subject = $request->subject;
            $template->bodyHtml = $request->bodyHtml;
            $template->save();

            Log::info('Email template updated', [
                'template_id' => $template->id,
                'template_key' => $template->templateKey,
                'group' => $template->groupKey,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully!'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating email template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the template.'
            ], 500);
        }
    }

    /**
     * Toggle a template's active status.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleTemplate($id)
    {
        try {
            $template = AsEmailTemplate::active()->find($id);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            $template->isActive = !$template->isActive;
            $template->save();

            $status = $template->isActive ? 'enabled' : 'disabled';

            return response()->json([
                'success' => true,
                'message' => "Template '{$template->templateName}' has been {$status}.",
                'data' => [
                    'isActive' => (bool) $template->isActive
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling email template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while toggling the template.'
            ], 500);
        }
    }

    /**
     * Render a template with sample data and send it as a test email using
     * the template's own group SMTP settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function testTemplate(Request $request, $id, AnisystemSubscriptionService $service)
    {
        try {
            $template = AsEmailTemplate::active()->find($id);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'testEmail' => 'required|email|max:255',
            ], [
                'testEmail.required' => 'Please enter an email address to send the test to.',
                'testEmail.email' => 'Please enter a valid email address.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            $testEmail = $request->testEmail;
            $now = Carbon::now('Asia/Manila');

            $sampleTags = [
                'firstName' => 'Juan',
                'lastName' => 'Dela Cruz',
                'email' => $testEmail,
                'phone' => '09171234567',
                'siteName' => 'AniSystem',
                'loginUrl' => AnisystemSubscriptionService::LOGIN_URL,
                'planName' => 'Sample Plan (12 Months)',
                'price' => number_format(1999, 2),
                'orderNumber' => 'ANI-' . $now->format('Ymd') . '-TEST',
                'expiresAt' => $now->copy()->addDays(365)->format('M j, Y'),
                'startsAt' => $now->format('M j, Y'),
                'daysRemaining' => '7',
                'resetUrl' => 'http://anisystem.test/reset-password/sample-token',
                'name' => 'Juan Dela Cruz',
                'subject' => 'Sample inquiry subject',
                'message' => 'This is a sample contact message body.',
            ];

            $subject = AsEmailTemplate::renderTags($template->subject, $sampleTags);
            $body = AsEmailTemplate::renderTags($template->bodyHtml, $sampleTags);

            // Any tag we do not have sample data for gets a visible placeholder.
            $subject = preg_replace('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', '[sample $1]', $subject);
            $body = preg_replace('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', '[sample $1]', $body);

            $sent = $service->sendViaGroupSmtp(
                $template->groupKey,
                $testEmail,
                '[TEST] ' . $subject,
                $body,
                'Test Recipient'
            );

            if (!$sent) {
                return response()->json([
                    'success' => false,
                    'message' => "Test email could not be sent — check that the '{$template->groupKey}' SMTP settings are configured and active."
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $testEmail
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending test template email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the test email.'
            ], 500);
        }
    }
}
