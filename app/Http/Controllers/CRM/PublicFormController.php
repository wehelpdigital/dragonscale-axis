<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmForm;
use App\Models\CrmFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicFormController extends Controller
{
    /**
     * Display a public form
     */
    public function show($slug)
    {
        $form = CrmForm::active()
            ->published()
            ->where('formSlug', $slug)
            ->firstOrFail();

        // Increment view count
        $form->incrementViews();

        return view('crm.forms.public', compact('form'));
    }

    /**
     * Handle form submission
     */
    public function submit(Request $request, $slug)
    {
        $form = CrmForm::active()
            ->published()
            ->where('formSlug', $slug)
            ->firstOrFail();

        // Build validation rules from form elements
        $rules = [];
        $messages = [];
        $formElements = $form->formElements ?? [];

        // Non-input element types that should be skipped
        $skipTypes = ['heading', 'paragraph', 'divider', 'submit_button', 'image', 'video'];

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
                    $fieldRules[] = 'regex:/^[0-9+\-\s()]+$/';
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
                case 'file':
                    $fieldRules[] = 'file';
                    if (!empty($element['maxSize'])) {
                        $fieldRules[] = 'max:' . ($element['maxSize'] * 1024); // Convert MB to KB
                    }
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'checkbox':
                    $fieldRules = ['nullable', 'array'];
                    break;
            }

            if (!empty($fieldRules)) {
                $rules[$fieldId] = $fieldRules;
            }
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
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
            $value = $request->input($fieldId);

            // Handle file uploads
            if ($element['type'] === 'file' && $request->hasFile($fieldId)) {
                $file = $request->file($fieldId);
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/form-submissions'), $filename);
                $value = $filename;
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
            'delete_status' => 'active',
        ]);

        // Increment submission count
        $form->incrementSubmissions();

        // Execute triggers
        $this->executeTriggers($form, $submission);

        // Get success response from settings
        $settings = $form->formSettings ?? [];
        $successMessage = $settings['successMessage'] ?? 'Thank you for your submission!';
        $redirectUrl = $settings['redirectUrl'] ?? null;

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'redirect' => $redirectUrl,
        ]);
    }

    /**
     * Execute form triggers from embedded trigger flow
     */
    private function executeTriggers(CrmForm $form, CrmFormSubmission $submission)
    {
        $triggerFlow = $form->triggerFlow ?? [];

        if (empty($triggerFlow)) {
            return;
        }

        $executionDetails = [];

        foreach ($triggerFlow as $index => $step) {
            try {
                $result = $this->executeStep($step, $submission);
                $executionDetails[] = [
                    'step' => $index,
                    'action' => $step['type'] ?? 'unknown',
                    'status' => 'success',
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                Log::error('Form trigger step failed', [
                    'formId' => $form->id,
                    'submissionId' => $submission->id,
                    'step' => $index,
                    'error' => $e->getMessage(),
                ]);

                $executionDetails[] = [
                    'step' => $index,
                    'action' => $step['type'] ?? 'unknown',
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Log execution details if needed
        Log::info('Trigger flow executed', [
            'formId' => $form->id,
            'submissionId' => $submission->id,
            'executionDetails' => $executionDetails,
        ]);
    }

    /**
     * Execute a single trigger step
     */
    private function executeStep(array $step, CrmFormSubmission $submission)
    {
        $type = $step['type'] ?? null;
        $config = $step['config'] ?? [];

        switch ($type) {
            case 'send_email':
                return $this->sendEmail($config, $submission);

            case 'create_lead':
                return $this->createLead($config, $submission);

            case 'webhook':
                return $this->callWebhook($config, $submission);

            case 'notify_admin':
                return $this->notifyAdmin($config, $submission);

            case 'delay':
                // For now, delays are not implemented (would need queue jobs)
                return ['skipped' => true, 'reason' => 'Delays require queue processing'];

            case 'condition':
                // Conditions would branch the flow
                return $this->evaluateCondition($config, $submission);

            default:
                return ['skipped' => true, 'reason' => 'Unknown action type'];
        }
    }

    /**
     * Send email action
     */
    private function sendEmail(array $config, CrmFormSubmission $submission)
    {
        $to = $this->replaceVariables($config['to'] ?? '', $submission);
        $subject = $this->replaceVariables($config['subject'] ?? 'New Form Submission', $submission);
        $body = $this->replaceVariables($config['body'] ?? '', $submission);

        if (empty($to)) {
            throw new \Exception('Email recipient is required');
        }

        // Simple mail sending (in production, use proper Mail class)
        Mail::raw($body, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject);
        });

        return ['sent_to' => $to];
    }

    /**
     * Create CRM lead action
     */
    private function createLead(array $config, CrmFormSubmission $submission)
    {
        $submissionData = $submission->submissionData ?? [];
        $fieldMappings = $config['fieldMappings'] ?? [];
        $form = $submission->form;

        // Prepare lead data from mappings
        $leadData = [
            'usersId' => $form->usersId,
            'leadStatus' => $config['status'] ?? 'new',
            'leadSourceOther' => $config['source'] ?? 'form',
            'delete_status' => 'active',
        ];
        $customFields = [];

        // Process field mappings
        foreach ($fieldMappings as $mapping) {
            $formField = $mapping['formField'] ?? '';
            $leadField = $mapping['leadField'] ?? '';

            if (empty($formField) || empty($leadField)) continue;

            $value = $submissionData[$formField] ?? null;
            if ($value === null) continue;

            // Handle array values (checkboxes)
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // Check if it's a custom field
            if (str_starts_with($leadField, 'custom:')) {
                $customFieldName = substr($leadField, 7);
                $customFields[$customFieldName] = $value;
            } else {
                // Standard field
                $leadData[$leadField] = $value;
            }
        }

        // Handle fullName -> split into firstName/lastName
        if (isset($leadData['fullName']) && !empty($leadData['fullName'])) {
            $nameParts = explode(' ', $leadData['fullName'], 2);
            $leadData['firstName'] = $nameParts[0] ?? '';
            $leadData['lastName'] = $nameParts[1] ?? '';
            unset($leadData['fullName']);
        }

        // Check for duplicate by email
        $existingLead = null;
        if (!empty($leadData['email'])) {
            $existingLead = \App\Models\CrmLead::active()
                ->forUser($form->usersId)
                ->where('email', $leadData['email'])
                ->first();
        }

        if ($existingLead) {
            // Update existing lead with new data (don't override non-empty fields)
            foreach ($leadData as $key => $value) {
                if (!empty($value) && $key !== 'usersId' && $key !== 'delete_status') {
                    $existingLead->{$key} = $value;
                }
            }
            $existingLead->save();
            $lead = $existingLead;
            $isNew = false;
        } else {
            // Create new lead
            $lead = \App\Models\CrmLead::create($leadData);
            $isNew = true;
        }

        // Handle custom fields
        foreach ($customFields as $fieldName => $fieldValue) {
            // Check if custom field exists for this lead
            $existingCustom = \App\Models\CrmLeadCustomData::where('leadId', $lead->id)
                ->where('fieldName', $fieldName)
                ->where('delete_status', 'active')
                ->first();

            if ($existingCustom) {
                $existingCustom->update(['fieldValue' => $fieldValue]);
            } else {
                \App\Models\CrmLeadCustomData::create([
                    'leadId' => $lead->id,
                    'fieldName' => $fieldName,
                    'fieldValue' => $fieldValue,
                    'usersId' => $form->usersId,
                    'delete_status' => 'active',
                ]);
            }
        }

        // Log activity
        $lead->logActivity(
            'form_submission',
            'Lead ' . ($isNew ? 'created' : 'updated') . ' from form submission: ' . $form->formName,
            $form->usersId
        );

        return [
            'lead_id' => $lead->id,
            'is_new' => $isNew,
            'lead_created' => $isNew,
        ];
    }

    /**
     * Call webhook action
     */
    private function callWebhook(array $config, CrmFormSubmission $submission)
    {
        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'POST');

        if (empty($url)) {
            throw new \Exception('Webhook URL is required');
        }

        $payload = [
            'form_id' => $submission->formId,
            'submission_id' => $submission->id,
            'submitted_at' => $submission->created_at->toIso8601String(),
            'data' => $submission->submissionData,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \Exception("Webhook returned HTTP {$httpCode}");
        }

        return ['http_code' => $httpCode];
    }

    /**
     * Notify admin action
     */
    private function notifyAdmin(array $config, CrmFormSubmission $submission)
    {
        $form = $submission->form;

        // Get email from config or fall back to form owner
        $adminEmail = $config['email'] ?? null;

        if (empty($adminEmail)) {
            // Get form owner's email
            $adminEmail = $form->user->email ?? null;
        }

        if (empty($adminEmail)) {
            return ['skipped' => true, 'reason' => 'No admin email configured'];
        }

        $message = $config['message'] ?? 'New form submission received';
        $message = $this->replaceVariables($message, $submission);

        // Build submission summary
        $summary = "Form: {$form->formName}\n";
        $summary .= "Submitted: {$submission->created_at->format('Y-m-d H:i:s')}\n\n";
        $summary .= "Data:\n";
        foreach ($submission->submissionData as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $summary .= "- {$key}: {$value}\n";
        }

        Mail::raw($message . "\n\n" . $summary, function ($mail) use ($adminEmail, $form) {
            $mail->to($adminEmail)
                ->subject("New Submission: {$form->formName}");
        });

        return ['notified' => $adminEmail];
    }

    /**
     * Evaluate condition
     */
    private function evaluateCondition(array $config, CrmFormSubmission $submission)
    {
        $field = $config['field'] ?? '';
        $operator = $config['operator'] ?? 'equals';
        $value = $config['value'] ?? '';

        $fieldValue = $submission->submissionData[$field] ?? null;

        $result = match($operator) {
            'equals' => $fieldValue == $value,
            'not_equals' => $fieldValue != $value,
            'contains' => str_contains($fieldValue ?? '', $value),
            'not_empty' => !empty($fieldValue),
            'empty' => empty($fieldValue),
            default => false,
        };

        return ['condition_met' => $result];
    }

    /**
     * Replace variables in text with submission data
     */
    private function replaceVariables(string $text, CrmFormSubmission $submission)
    {
        $data = $submission->submissionData ?? [];

        // Replace {{field_id}} patterns
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $text = str_replace('{{' . $key . '}}', $value ?? '', $text);
        }

        // Replace special variables
        $text = str_replace('{{submission_id}}', $submission->id, $text);
        $text = str_replace('{{submission_date}}', $submission->created_at->format('Y-m-d H:i:s'), $text);
        $text = str_replace('{{submitter_email}}', $submission->submitterEmail ?? '', $text);
        $text = str_replace('{{submitter_name}}', $submission->submitterName ?? '', $text);

        return $text;
    }
}
