<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductStore;
use App\Models\EcomStoreSmtpSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class StoreSettingsController extends Controller
{
    /**
     * Display the store settings page.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $storeId = $request->query('id');

        if (!$storeId) {
            return redirect()->route('ecom-stores')->with('error', 'Store ID is required.');
        }

        $store = EcomProductStore::where('deleteStatus', 1)->findOrFail($storeId);

        // Get SMTP settings for this store
        $smtpSettings = EcomStoreSmtpSetting::where('storeId', $storeId)
            ->where('deleteStatus', 1)
            ->first();

        // Get the active tab from query string, default to 'smtp'
        $activeTab = $request->query('tab', 'smtp');

        return view('ecommerce.stores.settings', compact('store', 'smtpSettings', 'activeTab'));
    }

    /**
     * Save SMTP settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSmtp(Request $request)
    {
        try {
            $storeId = $request->query('id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            $store = EcomProductStore::where('deleteStatus', 1)->find($storeId);

            if (!$store) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store not found.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'smtpHost' => 'required|string|max:255',
                'smtpPort' => 'required|integer|min:1|max:65535',
                'smtpUsername' => 'nullable|string|max:255',
                'smtpPassword' => 'nullable|string|max:255',
                'smtpEncryption' => 'required|in:tls,ssl,none',
                'smtpFromEmail' => 'required|email|max:255',
                'smtpFromName' => 'required|string|max:255',
                'isActive' => 'boolean',
            ], [
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

            // Find or create SMTP settings for this store
            $smtpSettings = EcomStoreSmtpSetting::firstOrNew([
                'storeId' => $storeId,
                'deleteStatus' => 1
            ]);

            $smtpSettings->smtpHost = $request->smtpHost;
            $smtpSettings->smtpPort = $request->smtpPort;
            $smtpSettings->smtpUsername = $request->smtpUsername;

            // Only update password if provided
            if ($request->filled('smtpPassword')) {
                $smtpSettings->smtpPassword = $request->smtpPassword;
            }

            $smtpSettings->smtpEncryption = $request->smtpEncryption;
            $smtpSettings->smtpFromEmail = $request->smtpFromEmail;
            $smtpSettings->smtpFromName = $request->smtpFromName;
            $smtpSettings->isActive = $request->boolean('isActive');
            $smtpSettings->isVerified = false; // Reset verification when settings change
            $smtpSettings->save();

            Log::info('SMTP settings saved', [
                'store_id' => $storeId,
                'smtp_host' => $request->smtpHost
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMTP settings saved successfully!',
                'data' => [
                    'isConfigured' => $smtpSettings->isConfigured()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving SMTP settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving SMTP settings.'
            ], 500);
        }
    }

    /**
     * Test SMTP connection.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testSmtp(Request $request)
    {
        try {
            $storeId = $request->query('id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            $smtpSettings = EcomStoreSmtpSetting::where('storeId', $storeId)
                ->where('deleteStatus', 1)
                ->first();

            if (!$smtpSettings || !$smtpSettings->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please save SMTP settings first.'
                ], 400);
            }

            $testEmail = $request->input('testEmail', $smtpSettings->smtpFromEmail);

            // Test SMTP connection using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = $smtpSettings->smtpHost;
                $mail->Port = $smtpSettings->smtpPort;

                if ($smtpSettings->smtpUsername) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $smtpSettings->smtpUsername;
                    $mail->Password = $smtpSettings->decrypted_password;
                }

                if ($smtpSettings->smtpEncryption !== 'none') {
                    $mail->SMTPSecure = $smtpSettings->smtpEncryption === 'ssl'
                        ? PHPMailer::ENCRYPTION_SMTPS
                        : PHPMailer::ENCRYPTION_STARTTLS;
                }

                $mail->setFrom($smtpSettings->smtpFromEmail, $smtpSettings->smtpFromName);
                $mail->addAddress($testEmail);

                $mail->isHTML(true);
                $mail->Subject = 'SMTP Test - ' . $smtpSettings->store->storeName;
                $mail->Body = '<h2>SMTP Connection Test</h2>
                    <p>This is a test email from your store: <strong>' . $smtpSettings->store->storeName . '</strong></p>
                    <p>If you received this email, your SMTP settings are configured correctly!</p>
                    <p><small>Sent at: ' . now()->format('Y-m-d H:i:s') . '</small></p>';
                $mail->AltBody = 'SMTP Test - This is a test email from your store: ' . $smtpSettings->store->storeName;

                $mail->send();

                // Update verification status
                $smtpSettings->isVerified = true;
                $smtpSettings->lastTestedAt = now();
                $smtpSettings->save();

                Log::info('SMTP test successful', [
                    'store_id' => $storeId,
                    'test_email' => $testEmail
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $testEmail
                ]);

            } catch (Exception $e) {
                Log::error('SMTP test failed: ' . $mail->ErrorInfo);

                return response()->json([
                    'success' => false,
                    'message' => 'SMTP test failed: ' . $mail->ErrorInfo
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Error testing SMTP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while testing SMTP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle SMTP active status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleSmtpStatus(Request $request)
    {
        try {
            $storeId = $request->query('id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            $smtpSettings = EcomStoreSmtpSetting::where('storeId', $storeId)
                ->where('deleteStatus', 1)
                ->first();

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
                'isActive' => $smtpSettings->isActive
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling SMTP status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while toggling SMTP status.'
            ], 500);
        }
    }
}
