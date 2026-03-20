<?php

namespace App\Http\Controllers\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\EcomThankYouPageSetting;
use App\Models\EcomProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MiscSettingsController extends Controller
{
    /**
     * Display the misc settings page with tabs.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $variantId = $request->query('variant_id');
        $tab = $request->query('tab', 'thank-you-page');

        // Get the variant if provided (for back navigation)
        $variant = null;
        $product = null;
        if ($variantId) {
            $variant = EcomProductVariant::find($variantId);
            if ($variant) {
                $product = $variant->product;
            }
        }

        // Get the thank you page settings for the current user
        $thankYouSettings = EcomThankYouPageSetting::getForUser(Auth::id());

        return view('ecommerce.misc-settings.index', compact(
            'variant',
            'product',
            'variantId',
            'tab',
            'thankYouSettings'
        ));
    }

    /**
     * Update the thank you page settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateThankYouPage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mainHeading' => 'required|string|max:255',
            'subHeading' => 'required|string|max:255',
            'subHeadingText' => 'nullable|string|max:500',
            'whatsNextTitle' => 'required|string|max:255',
            'whatsNextSteps' => 'required|array|min:1',
            'whatsNextSteps.*.text' => 'required|string|max:500',
            'inspirationalEmoji' => 'nullable|string|max:10',
            'inspirationalTitle' => 'required|string|max:255',
            'inspirationalMessage' => 'required|string|max:1000',
            'bookmarkTitle' => 'required|string|max:255',
            'bookmarkMessage' => 'required|string|max:500',
            'copyLinkButtonText' => 'required|string|max:100',
            'copyLinkSuccessText' => 'required|string|max:100',
            'savePhotoButtonText' => 'required|string|max:100',
            'savingText' => 'required|string|max:100',
            'homeButtonText' => 'required|string|max:100',
            'footerText' => 'required|string|max:255',
            'statusVerifiedText' => 'required|string|max:100',
            'statusPendingText' => 'required|string|max:100',
        ], [
            'mainHeading.required' => 'Main heading is required.',
            'subHeading.required' => 'Sub heading is required.',
            'whatsNextTitle.required' => 'What\'s Next title is required.',
            'whatsNextSteps.required' => 'At least one step is required.',
            'whatsNextSteps.min' => 'At least one step is required.',
            'whatsNextSteps.*.text.required' => 'Step text is required.',
            'inspirationalTitle.required' => 'Inspirational title is required.',
            'inspirationalMessage.required' => 'Inspirational message is required.',
            'bookmarkTitle.required' => 'Bookmark title is required.',
            'bookmarkMessage.required' => 'Bookmark message is required.',
            'copyLinkButtonText.required' => 'Copy Link button text is required.',
            'copyLinkSuccessText.required' => 'Copy Link success text is required.',
            'savePhotoButtonText.required' => 'Save Photo button text is required.',
            'savingText.required' => 'Saving text is required.',
            'homeButtonText.required' => 'Home button text is required.',
            'footerText.required' => 'Footer text is required.',
            'statusVerifiedText.required' => 'Status verified text is required.',
            'statusPendingText.required' => 'Status pending text is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find or create the settings for the current user
            $settings = EcomThankYouPageSetting::active()->forUser(Auth::id())->first();

            $data = [
                'mainHeading' => $request->mainHeading,
                'subHeading' => $request->subHeading,
                'subHeadingText' => $request->subHeadingText,
                'whatsNextTitle' => $request->whatsNextTitle,
                'whatsNextSteps' => $request->whatsNextSteps,
                'inspirationalEmoji' => $request->inspirationalEmoji,
                'inspirationalTitle' => $request->inspirationalTitle,
                'inspirationalMessage' => $request->inspirationalMessage,
                'bookmarkTitle' => $request->bookmarkTitle,
                'bookmarkMessage' => $request->bookmarkMessage,
                'copyLinkButtonText' => $request->copyLinkButtonText,
                'copyLinkSuccessText' => $request->copyLinkSuccessText,
                'savePhotoButtonText' => $request->savePhotoButtonText,
                'savingText' => $request->savingText,
                'homeButtonText' => $request->homeButtonText,
                'footerText' => $request->footerText,
                'statusVerifiedText' => $request->statusVerifiedText,
                'statusPendingText' => $request->statusPendingText,
            ];

            if ($settings) {
                // Update existing settings
                $settings->update($data);
            } else {
                // Create new settings
                $data['usersId'] = Auth::id();
                $data['delete_status'] = 'active';
                $settings = EcomThankYouPageSetting::create($data);
            }

            return response()->json([
                'success' => true,
                'message' => 'Thank You Page settings saved successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving settings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset the thank you page settings to defaults.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetThankYouPage()
    {
        try {
            // Delete any existing settings for this user (soft delete)
            EcomThankYouPageSetting::active()
                ->forUser(Auth::id())
                ->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Thank You Page settings reset to defaults successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting settings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
