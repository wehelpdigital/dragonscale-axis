<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmForm;
use App\Models\CrmFormSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CrmFormsController extends Controller
{
    /**
     * Display forms listing
     */
    public function index()
    {
        $forms = CrmForm::active()
            ->forUser(Auth::id())
            ->withCount(['submissions' => function($query) {
                $query->active();
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('crm.forms.index', compact('forms'));
    }

    /**
     * Show form builder for creating a new form
     */
    public function create()
    {
        $formElements = $this->getAvailableFormElements();
        $accessTags = $this->getAccessTags();

        return view('crm.forms.builder', [
            'form' => null,
            'formElements' => $formElements,
            'accessTags' => $accessTags,
            'mode' => 'create',
        ]);
    }

    /**
     * Store a new form
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'formName' => 'required|string|max:255',
            'formDescription' => 'nullable|string|max:1000',
            'formStatus' => 'required|in:draft,active,inactive',
            'formElements' => 'nullable|array',
            'formSettings' => 'nullable|array',
            'triggerFlow' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $form = CrmForm::create([
            'usersId' => Auth::id(),
            'formName' => $request->formName,
            'formSlug' => CrmForm::generateUniqueSlug($request->formName),
            'formDescription' => $request->formDescription,
            'formStatus' => $request->formStatus,
            'formElements' => $request->formElements ?? [],
            'formSettings' => array_merge(CrmForm::getDefaultSettings(), $request->formSettings ?? []),
            'triggerFlow' => $request->triggerFlow ?? [],
            'delete_status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form created successfully',
            'data' => $form,
            'redirect' => route('crm-forms.edit', ['id' => $form->id]),
        ]);
    }

    /**
     * Show form builder for editing
     */
    public function edit(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $formElements = $this->getAvailableFormElements();
        $accessTags = $this->getAccessTags();

        return view('crm.forms.builder', [
            'form' => $form,
            'formElements' => $formElements,
            'accessTags' => $accessTags,
            'mode' => 'edit',
        ]);
    }

    /**
     * Update an existing form
     */
    public function update(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $validator = Validator::make($request->all(), [
            'formName' => 'required|string|max:255',
            'formDescription' => 'nullable|string|max:1000',
            'formStatus' => 'required|in:draft,active,inactive',
            'formElements' => 'nullable|array',
            'formSettings' => 'nullable|array',
            'triggerFlow' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $form->update([
            'formName' => $request->formName,
            'formDescription' => $request->formDescription,
            'formStatus' => $request->formStatus,
            'formElements' => $request->formElements ?? [],
            'formSettings' => array_merge(CrmForm::getDefaultSettings(), $request->formSettings ?? []),
            'triggerFlow' => $request->triggerFlow ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form updated successfully',
            'data' => $form,
        ]);
    }

    /**
     * Delete a form (soft delete)
     */
    public function destroy(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $form->update(['delete_status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Form deleted successfully',
        ]);
    }

    /**
     * Duplicate a form
     */
    public function duplicate(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $newForm = CrmForm::create([
            'usersId' => Auth::id(),
            'formName' => $form->formName . ' (Copy)',
            'formSlug' => CrmForm::generateUniqueSlug($form->formName . ' Copy'),
            'formDescription' => $form->formDescription,
            'formStatus' => 'draft',
            'formElements' => $form->formElements,
            'formSettings' => $form->formSettings,
            'triggerFlow' => $form->triggerFlow,
            'delete_status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form duplicated successfully',
            'data' => $newForm,
        ]);
    }

    /**
     * Toggle form status
     */
    public function toggleStatus(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $newStatus = $form->formStatus === 'active' ? 'inactive' : 'active';
        $form->update(['formStatus' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Form status updated',
            'status' => $newStatus,
        ]);
    }

    /**
     * View form submissions
     */
    public function submissions(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $submissions = CrmFormSubmission::active()
            ->forForm($request->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('crm.forms.submissions', compact('form', 'submissions'));
    }

    /**
     * Get submission details
     */
    public function getSubmission(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $submission = CrmFormSubmission::active()
            ->forForm($request->formId)
            ->findOrFail($request->submissionId);

        // Mark as read if new
        if ($submission->submissionStatus === 'new') {
            $submission->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'submission' => $submission,
                'formElements' => $form->formElements,
            ],
        ]);
    }

    /**
     * Delete a submission
     */
    public function deleteSubmission(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->formId);

        $submission = CrmFormSubmission::active()
            ->forForm($request->formId)
            ->findOrFail($request->submissionId);

        $submission->update(['delete_status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Submission deleted successfully',
        ]);
    }

    /**
     * Export submissions as CSV
     */
    public function exportSubmissions(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $submissions = CrmFormSubmission::active()
            ->forForm($request->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = Str::slug($form->formName) . '_submissions_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($form, $submissions) {
            $file = fopen('php://output', 'w');

            // Build header row from form elements
            $headerRow = ['Submission ID', 'Submitted At', 'Status', 'IP Address'];
            $formElements = $form->formElements ?? [];
            foreach ($formElements as $element) {
                if (isset($element['label']) && !in_array($element['type'], ['heading', 'paragraph', 'divider'])) {
                    $headerRow[] = $element['label'];
                }
            }
            fputcsv($file, $headerRow);

            // Add data rows
            foreach ($submissions as $submission) {
                $row = [
                    $submission->id,
                    $submission->created_at->format('Y-m-d H:i:s'),
                    $submission->submissionStatus,
                    $submission->submitterIp,
                ];

                $data = $submission->submissionData ?? [];
                foreach ($formElements as $element) {
                    if (isset($element['id']) && !in_array($element['type'], ['heading', 'paragraph', 'divider'])) {
                        $value = $data[$element['id']] ?? '';
                        if (is_array($value)) {
                            $value = implode(', ', $value);
                        }
                        $row[] = $value;
                    }
                }

                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get available form elements for the builder
     */
    private function getAvailableFormElements()
    {
        return [
            'basic' => [
                [
                    'type' => 'text',
                    'name' => 'Text Input',
                    'icon' => 'bx-text',
                    'defaults' => [
                        'label' => 'Text Field',
                        'placeholder' => 'Enter text...',
                        'required' => false,
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'email',
                    'name' => 'Email',
                    'icon' => 'bx-envelope',
                    'defaults' => [
                        'label' => 'Email Address',
                        'placeholder' => 'Enter email...',
                        'required' => false,
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'phone',
                    'name' => 'Phone',
                    'icon' => 'bx-phone',
                    'defaults' => [
                        'label' => 'Phone Number',
                        'placeholder' => 'Enter phone...',
                        'required' => false,
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'number',
                    'name' => 'Number',
                    'icon' => 'bx-hash',
                    'defaults' => [
                        'label' => 'Number',
                        'placeholder' => 'Enter number...',
                        'required' => false,
                        'width' => 'col-12',
                        'min' => null,
                        'max' => null,
                    ],
                ],
                [
                    'type' => 'textarea',
                    'name' => 'Text Area',
                    'icon' => 'bx-align-left',
                    'defaults' => [
                        'label' => 'Message',
                        'placeholder' => 'Enter your message...',
                        'required' => false,
                        'width' => 'col-12',
                        'rows' => 4,
                    ],
                ],
            ],
            'selection' => [
                [
                    'type' => 'select',
                    'name' => 'Dropdown',
                    'icon' => 'bx-chevron-down-circle',
                    'defaults' => [
                        'label' => 'Select Option',
                        'placeholder' => 'Choose...',
                        'required' => false,
                        'width' => 'col-12',
                        'options' => ['Option 1', 'Option 2', 'Option 3'],
                    ],
                ],
                [
                    'type' => 'radio',
                    'name' => 'Radio Buttons',
                    'icon' => 'bx-radio-circle-marked',
                    'defaults' => [
                        'label' => 'Choose One',
                        'required' => false,
                        'width' => 'col-12',
                        'options' => ['Option 1', 'Option 2', 'Option 3'],
                        'inline' => false,
                    ],
                ],
                [
                    'type' => 'checkbox',
                    'name' => 'Checkboxes',
                    'icon' => 'bx-checkbox-checked',
                    'defaults' => [
                        'label' => 'Select Multiple',
                        'required' => false,
                        'width' => 'col-12',
                        'options' => ['Option 1', 'Option 2', 'Option 3'],
                        'inline' => false,
                    ],
                ],
                [
                    'type' => 'single_checkbox',
                    'name' => 'Single Checkbox',
                    'icon' => 'bx-check-square',
                    'defaults' => [
                        'label' => 'I agree to the terms',
                        'required' => false,
                        'width' => 'col-12',
                    ],
                ],
            ],
            'advanced' => [
                [
                    'type' => 'date',
                    'name' => 'Date',
                    'icon' => 'bx-calendar',
                    'defaults' => [
                        'label' => 'Date',
                        'required' => false,
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'time',
                    'name' => 'Time',
                    'icon' => 'bx-time',
                    'defaults' => [
                        'label' => 'Time',
                        'required' => false,
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'file',
                    'name' => 'File Upload',
                    'icon' => 'bx-upload',
                    'defaults' => [
                        'label' => 'Upload File',
                        'required' => false,
                        'width' => 'col-12',
                        'accept' => '.pdf,.doc,.docx,.jpg,.png',
                        'maxSize' => 5, // MB
                    ],
                ],
                [
                    'type' => 'hidden',
                    'name' => 'Hidden Field',
                    'icon' => 'bx-hide',
                    'defaults' => [
                        'label' => 'Hidden Field',
                        'value' => '',
                    ],
                ],
            ],
            'media' => [
                [
                    'type' => 'image',
                    'name' => 'Image',
                    'icon' => 'bx-image',
                    'defaults' => [
                        'imageUrl' => '',
                        'caption' => '',
                        'imageSize' => 'medium', // small, medium, large, full
                        'imagePosition' => 'center', // left, center, right
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'video',
                    'name' => 'Video/YouTube',
                    'icon' => 'bx-play-circle',
                    'defaults' => [
                        'videoUrl' => '',
                        'width' => 'col-12',
                    ],
                ],
            ],
            'layout' => [
                [
                    'type' => 'heading',
                    'name' => 'Heading',
                    'icon' => 'bx-heading',
                    'defaults' => [
                        'text' => 'Section Heading',
                        'size' => 'h4',
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'name' => 'Paragraph',
                    'icon' => 'bx-paragraph',
                    'defaults' => [
                        'text' => 'Add some descriptive text here.',
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'divider',
                    'name' => 'Divider',
                    'icon' => 'bx-minus',
                    'defaults' => [
                        'width' => 'col-12',
                    ],
                ],
                [
                    'type' => 'submit_button',
                    'name' => 'Submit Button',
                    'icon' => 'bx-check-circle',
                    'defaults' => [
                        'buttonText' => 'Submit',
                        'buttonColor' => '#556ee6',
                    ],
                ],
            ],
        ];
    }

    /**
     * Preview form
     */
    public function preview(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        return view('crm.forms.preview', compact('form'));
    }

    /**
     * Upload image for form builder
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('image'),
            ], 422);
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'form_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Create directory if it doesn't exist
            $uploadPath = public_path('images/crm-forms');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $file->move($uploadPath, $filename);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'imageUrl' => url('images/crm-forms/' . $filename),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image file provided',
        ], 422);
    }

    /**
     * Get available access tags for trigger actions
     */
    private function getAccessTags()
    {
        return \DB::table('axis_tags')
            ->where('deleteStatus', 1)
            ->orderBy('tagName', 'asc')
            ->get(['id', 'tagName', 'tagType', 'targetId', 'expirationLength']);
    }

    /**
     * Generate or regenerate API key for a form
     */
    public function generateApiKey(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $apiKey = $form->generateApiKey();

        return response()->json([
            'success' => true,
            'message' => 'API key generated successfully',
            'apiKey' => $apiKey,
        ]);
    }

    /**
     * Toggle API enabled status
     */
    public function toggleApi(Request $request)
    {
        $form = CrmForm::active()
            ->forUser(Auth::id())
            ->findOrFail($request->id);

        $form->apiEnabled = !$form->apiEnabled;
        $form->save();

        return response()->json([
            'success' => true,
            'message' => $form->apiEnabled ? 'API enabled' : 'API disabled',
            'apiEnabled' => $form->apiEnabled,
        ]);
    }

    /**
     * Get lead fields for mapping in trigger actions
     */
    public function getLeadFields()
    {
        $standardFields = \App\Models\CrmLead::IMPORTABLE_FIELDS;

        // Get custom field names used across all leads for this user
        $customFields = \DB::table('crm_lead_custom_data')
            ->where('usersId', Auth::id())
            ->where('delete_status', 'active')
            ->select('fieldName')
            ->distinct()
            ->orderBy('fieldName')
            ->pluck('fieldName')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'standard' => $standardFields,
                'custom' => $customFields,
            ],
        ]);
    }
}
