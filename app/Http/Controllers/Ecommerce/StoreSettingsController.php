<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomProductStore;
use App\Models\EcomStoreSmtpSetting;
use App\Models\EcomStorePaymentSetting;
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

        // Get Payment settings for this store
        $paymentSettings = EcomStorePaymentSetting::where('storeId', $storeId)
            ->where('deleteStatus', 1)
            ->first();

        // Get the active tab from query string, default to 'smtp'
        $activeTab = $request->query('tab', 'smtp');

        return view('ecommerce.stores.settings', compact('store', 'smtpSettings', 'paymentSettings', 'activeTab'));
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

    /**
     * Save Payment settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function savePayment(Request $request)
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
                'bankName' => 'nullable|string|max:255',
                'bankAccountName' => 'nullable|string|max:255',
                'bankAccountNumber' => 'nullable|string|max:100',
                'gcashNumber' => 'nullable|string|max:20',
                'gcashAccountName' => 'nullable|string|max:255',
                'paymentInstructions' => 'nullable|string|max:2000',
            ], [
                'bankAccountNumber.max' => 'Bank account number is too long.',
                'gcashNumber.max' => 'GCash number should not exceed 20 characters.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find or create payment settings for this store
            $paymentSettings = EcomStorePaymentSetting::firstOrNew([
                'storeId' => $storeId,
                'deleteStatus' => 1
            ]);

            $paymentSettings->bankName = $request->bankName;
            $paymentSettings->bankAccountName = $request->bankAccountName;
            $paymentSettings->bankAccountNumber = $request->bankAccountNumber;
            $paymentSettings->gcashNumber = $request->gcashNumber;
            $paymentSettings->gcashAccountName = $request->gcashAccountName;
            $paymentSettings->paymentInstructions = $request->paymentInstructions;
            $paymentSettings->save();

            Log::info('Payment settings saved', [
                'store_id' => $storeId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment settings saved successfully!',
                'data' => [
                    'isConfigured' => $paymentSettings->isConfigured()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving payment settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving payment settings.'
            ], 500);
        }
    }

    /**
     * Upload payment image (screenshot or QR code).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadPaymentImage(Request $request)
    {
        try {
            $storeId = $request->query('id');
            $imageType = $request->input('imageType'); // 'screenshot' or 'qrcode'

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            if (!in_array($imageType, ['screenshot', 'qrcode'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image type.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120' // 5MB max
            ], [
                'image.required' => 'Please select an image to upload.',
                'image.image' => 'The file must be an image.',
                'image.max' => 'Image size should not exceed 5MB.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Find or create payment settings
            $paymentSettings = EcomStorePaymentSetting::firstOrNew([
                'storeId' => $storeId,
                'deleteStatus' => 1
            ]);

            // Determine the field to update
            $fieldName = $imageType === 'screenshot' ? 'paymentScreenshot' : 'qrCodeImage';

            // Delete old image if exists
            if ($paymentSettings->$fieldName && file_exists(public_path($paymentSettings->$fieldName))) {
                unlink(public_path($paymentSettings->$fieldName));
            }

            // Save new image
            $file = $request->file('image');
            $filename = 'payment_' . $imageType . '_' . $storeId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'images/ecommerce/payments/' . $filename;

            // Ensure directory exists
            $dir = public_path('images/ecommerce/payments');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $filename);

            $paymentSettings->$fieldName = $path;
            $paymentSettings->save();

            Log::info('Payment image uploaded', [
                'store_id' => $storeId,
                'image_type' => $imageType
            ]);

            return response()->json([
                'success' => true,
                'message' => ucfirst($imageType) . ' uploaded successfully!',
                'imageUrl' => asset($path)
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading payment image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading image.'
            ], 500);
        }
    }

    /**
     * Remove payment image.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removePaymentImage(Request $request)
    {
        try {
            $storeId = $request->query('id');
            $imageType = $request->input('imageType'); // 'screenshot' or 'qrcode'

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            if (!in_array($imageType, ['screenshot', 'qrcode'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid image type.'
                ], 400);
            }

            $paymentSettings = EcomStorePaymentSetting::where('storeId', $storeId)
                ->where('deleteStatus', 1)
                ->first();

            if (!$paymentSettings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment settings not found.'
                ], 404);
            }

            // Determine the field to update
            $fieldName = $imageType === 'screenshot' ? 'paymentScreenshot' : 'qrCodeImage';

            // Delete file if exists
            if ($paymentSettings->$fieldName && file_exists(public_path($paymentSettings->$fieldName))) {
                unlink(public_path($paymentSettings->$fieldName));
            }

            $paymentSettings->$fieldName = null;
            $paymentSettings->save();

            return response()->json([
                'success' => true,
                'message' => ucfirst($imageType) . ' removed successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing payment image: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing image.'
            ], 500);
        }
    }

    /**
     * Toggle individual payment method status (bank or gcash).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function togglePaymentMethod(Request $request)
    {
        try {
            $storeId = $request->query('id');
            $method = $request->input('method'); // 'bank' or 'gcash'

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required.'
                ], 400);
            }

            if (!in_array($method, ['bank', 'gcash'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method.'
                ], 400);
            }

            $paymentSettings = EcomStorePaymentSetting::where('storeId', $storeId)
                ->where('deleteStatus', 1)
                ->first();

            if (!$paymentSettings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment settings not found.'
                ], 404);
            }

            // Check if the method details are complete before enabling
            if ($method === 'bank') {
                if (!$paymentSettings->isBankComplete()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please complete all bank details (Bank Name, Account Name, Account Number) before enabling.'
                    ], 400);
                }
                $paymentSettings->isBankActive = !$paymentSettings->isBankActive;
                $status = $paymentSettings->isBankActive ? 'enabled' : 'disabled';
                $methodName = 'Bank Transfer';
            } else {
                if (!$paymentSettings->isGcashComplete()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Please complete GCash details (Number and Account Name) before enabling.'
                    ], 400);
                }
                $paymentSettings->isGcashActive = !$paymentSettings->isGcashActive;
                $status = $paymentSettings->isGcashActive ? 'enabled' : 'disabled';
                $methodName = 'GCash';
            }

            $paymentSettings->save();

            return response()->json([
                'success' => true,
                'message' => "{$methodName} has been {$status}.",
                'isActive' => $method === 'bank' ? $paymentSettings->isBankActive : $paymentSettings->isGcashActive
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling payment method: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while toggling payment method.'
            ], 500);
        }
    }
}
