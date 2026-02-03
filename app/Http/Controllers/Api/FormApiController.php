<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CRM\PublicFormController;
use App\Models\CrmForm;
use App\Models\CrmFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormApiController extends Controller
{
    /**
     * Handle form submission via API (GET request)
     */
    public function submit(Request $request, $slug)
    {
        // Find form by slug
        $form = CrmForm::active()
            ->published()
            ->where('formSlug', $slug)
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'error' => 'form_not_found',
                'message' => 'Form not found or inactive',
            ], 404);
        }

        // Check if API is enabled
        if (!$form->apiEnabled) {
            return response()->json([
                'success' => false,
                'error' => 'api_disabled',
                'message' => 'API is not enabled for this form',
            ], 403);
        }

        // Validate API key
        $apiKey = $request->query('api_key') ?? $request->header('X-API-Key');
        if (!$apiKey || $apiKey !== $form->apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_api_key',
                'message' => 'Invalid or missing API key',
            ], 401);
        }

        // Build validation rules from form elements
        $rules = [];
        $messages = [];
        $formElements = $form->formElements ?? [];

        // Non-input element types that should be skipped
        $skipTypes = ['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video', 'hidden'];

        foreach ($formElements as $element) {
            if (!isset($element['id']) || in_array($element['type'], $skipTypes)) {
                continue;
            }

            $fieldRules = [];
            $fieldId = $element['id'];

            if (!empty($element['required'])) {
                $fieldRules[] = 'required';
                $messages["{$fieldId}.required"] = ($element['label'] ?? 'This field') . ' is required.';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Type-specific validation
            switch ($element['type']) {
                case 'email':
                    $fieldRules[] = 'email';
                    $messages["{$fieldId}.email"] = 'Please enter a valid email address.';
                    break;
                case 'phone':
                    $fieldRules[] = 'regex:/^[0-9+\\-\\s()]+$/';
                    $messages["{$fieldId}.regex"] = 'Please enter a valid phone number.';
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    if (isset($element['min'])) {
                        $fieldRules[] = 'min:' . $element['min'];
                    }
                    if (isset($element['max'])) {
                        $fieldRules[] = 'max:' . $element['max'];
                    }
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'checkbox':
                    $fieldRules = ['nullable', 'string']; // Accept comma-separated values
                    break;
            }

            if (!empty($fieldRules)) {
                $rules[$fieldId] = $fieldRules;
            }
        }

        // Validate all query parameters
        $validator = Validator::make($request->query(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Please correct the errors below.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Collect submission data
        $submissionData = [];
        $submitterEmail = null;
        $submitterName = null;

        foreach ($formElements as $element) {
            if (!isset($element['id']) || in_array($element['type'], $skipTypes)) {
                continue;
            }

            $fieldId = $element['id'];
            $value = $request->query($fieldId);

            // Handle checkbox values (convert comma-separated to array)
            if ($element['type'] === 'checkbox' && $value) {
                $value = array_map('trim', explode(',', $value));
            }

            $submissionData[$fieldId] = $value;

            // Extract email and name for quick reference
            if ($element['type'] === 'email' && !$submitterEmail) {
                $submitterEmail = $value;
            }
            if ($element['type'] === 'text' && stripos($element['label'], 'name') !== false && !$submitterName) {
                $submitterName = $value;
            }
        }

        // Create submission
        $submission = CrmFormSubmission::create([
            'formId' => $form->id,
            'submissionData' => $submissionData,
            'submitterIp' => $request->ip(),
            'submitterUserAgent' => $request->userAgent(),
            'submitterEmail' => $submitterEmail,
            'submitterName' => $submitterName,
            'submissionStatus' => 'new',
            'submissionSource' => 'api',
            'delete_status' => 'active',
        ]);

        // Increment submission count
        $form->incrementSubmissions();

        // Execute triggers using the PublicFormController
        $this->executeTriggers($form, $submission);

        return response()->json([
            'success' => true,
            'message' => 'Form submitted successfully',
            'data' => [
                'submission_id' => $submission->id,
                'submitted_at' => $submission->created_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Execute form triggers (reuse from PublicFormController)
     */
    private function executeTriggers(CrmForm $form, CrmFormSubmission $submission)
    {
        // Use reflection to access the private method in PublicFormController
        $controller = new PublicFormController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('executeTriggers');
        $method->setAccessible(true);
        $method->invoke($controller, $form, $submission);
    }

    /**
     * Get form API documentation
     */
    public function documentation(Request $request, $slug)
    {
        $form = CrmForm::active()
            ->where('formSlug', $slug)
            ->first();

        if (!$form) {
            return response()->json([
                'success' => false,
                'error' => 'form_not_found',
                'message' => 'Form not found',
            ], 404);
        }

        $inputElements = $form->getInputElements();
        $parameters = [];

        foreach ($inputElements as $element) {
            $parameters[] = [
                'name' => $element['id'],
                'type' => $element['type'],
                'label' => $element['label'] ?? $element['id'],
                'required' => $element['required'] ?? false,
                'options' => $element['options'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'form_name' => $form->formName,
                'endpoint' => $form->apiUrl,
                'method' => 'GET',
                'authentication' => [
                    'type' => 'api_key',
                    'parameter' => 'api_key',
                    'header' => 'X-API-Key',
                ],
                'parameters' => $parameters,
            ],
        ]);
    }
}
