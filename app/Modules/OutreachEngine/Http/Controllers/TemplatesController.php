<?php

namespace App\Modules\OutreachEngine\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OutreachEngine\Models\OutreachEmailTemplate;
use App\Modules\OutreachEngine\Models\OutreachLead;
use App\Modules\OutreachEngine\Services\SettingsResolver;
use App\Modules\OutreachEngine\Services\TemplateRenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * The outreach copy: create, edit, retire and preview the email templates the send
 * pipeline rotates through.
 *
 * Preview is deliberately free and instant. It renders against one of the operator's
 * own leads (or a believable stand-in before any leads exist) and only calls the LLM
 * when the request explicitly asks for a rephrase, so tapping "Preview" after every
 * keystroke costs nothing and never stalls on a provider timeout.
 */
class TemplatesController extends Controller
{
    /** Body column is longText; this is a sanity ceiling, not the column limit. */
    const MAX_BODY_CHARS = 200000;

    /** @var \App\Modules\OutreachEngine\Services\SettingsResolver */
    protected $resolver;

    public function __construct(SettingsResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Render the templates screen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $userId = (int) Auth::id();

        $templates = OutreachEmailTemplate::query()
            ->active()
            ->forUser($userId)
            ->ordered()
            ->get();

        // Preview needs something to render against; knowing whether real leads exist lets
        // the view say "previewing a sample" instead of quietly inventing a business.
        $sampleLead = OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->hasEmail()
            ->orderByDesc('id')
            ->first();

        if (!$sampleLead) {
            $sampleLead = OutreachLead::query()
                ->active()
                ->forUser($userId)
                ->orderByDesc('id')
                ->first();
        }

        return view('outreach::templates', [
            'templates' => $templates,
            'placeholders' => OutreachEmailTemplate::PLACEHOLDERS,
            'previewLead' => $sampleLead,
            'hasRealLead' => (bool) $sampleLead,
            'settings' => $this->resolver->forUser($userId),
        ]);
    }

    /**
     * Create a template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            $validator = $this->validateTemplate($request);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $template = OutreachEmailTemplate::create([
                'usersId' => $userId,
                'name' => trim((string) $request->input('name')),
                'subjectTemplate' => trim((string) $request->input('subjectTemplate')),
                'bodyTemplate' => (string) $request->input('bodyTemplate'),
                'isActive' => $request->boolean('isActive'),
                'sendOrder' => $this->resolveSendOrder($request, $userId),
                'timesUsed' => 0,
                'delete_status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template created successfully.',
                'data' => $this->presentTemplate($template),
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Creating a template failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the template.',
            ], 500);
        }
    }

    /**
     * Update a template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $userId = (int) Auth::id();
            $template = $this->findOwned((int) $id, $userId);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found.',
                ], 404);
            }

            $validator = $this->validateTemplate($request);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $template->name = trim((string) $request->input('name'));
            $template->subjectTemplate = trim((string) $request->input('subjectTemplate'));
            $template->bodyTemplate = (string) $request->input('bodyTemplate');
            $template->isActive = $request->boolean('isActive');

            if ($request->filled('sendOrder')) {
                $template->sendOrder = max(1, (int) $request->input('sendOrder'));
            }

            $template->save();

            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully.',
                'data' => $this->presentTemplate($template),
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Updating template ' . $id . ' failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the template.',
            ], 500);
        }
    }

    /**
     * Soft delete a template.
     *
     * The email log keeps its own copy of every subject and body it sent, so removing a
     * template never rewrites history in the inbox.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $userId = (int) Auth::id();
            $template = $this->findOwned((int) $id, $userId);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found.',
                ], 404);
            }

            $template->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Template deleted successfully.',
                'data' => ['id' => $template->id],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Deleting template ' . $id . ' failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the template.',
            ], 500);
        }
    }

    /**
     * Flip a template in or out of the send rotation.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggle($id)
    {
        try {
            $userId = (int) Auth::id();
            $template = $this->findOwned((int) $id, $userId);

            if (!$template) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template not found.',
                ], 404);
            }

            $template->isActive = !$template->isActive;
            $template->save();

            return response()->json([
                'success' => true,
                'message' => $template->isActive
                    ? 'Template is now active and back in the rotation.'
                    : 'Template is now inactive and will be skipped.',
                'data' => $this->presentTemplate($template),
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Toggling template ' . $id . ' failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the template.',
            ], 500);
        }
    }

    /**
     * Render a subject and body exactly as a prospect would receive them.
     *
     * Works on unsaved editor content, so the operator can see the result before the
     * first save. The LLM is only involved when 'rephrase' is true.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        try {
            $userId = (int) Auth::id();

            $validator = Validator::make($request->all(), [
                'subjectTemplate' => 'required|string|max:500',
                'bodyTemplate' => 'required|string|max:' . self::MAX_BODY_CHARS,
                'leadId' => 'nullable|integer|min:1',
                'rephrase' => 'nullable|boolean',
            ], [
                'subjectTemplate.required' => 'There is no subject line to preview.',
                'bodyTemplate.required' => 'There is no body to preview.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }

            $settings = $this->resolver->forUser($userId);
            $renderer = new TemplateRenderService($settings);

            $lead = $this->resolvePreviewLead($request, $userId);
            $isSample = $lead === null;

            if ($isSample) {
                $lead = $renderer->sampleLead();
            }

            $wantsRephrase = $request->boolean('rephrase');

            if (!$wantsRephrase) {
                // The common path: pure string substitution, no network, no cost.
                return response()->json([
                    'success' => true,
                    'message' => 'Preview rendered.',
                    'data' => [
                        'subject' => $renderer->renderSubject((string) $request->input('subjectTemplate'), $lead),
                        'body' => $renderer->render((string) $request->input('bodyTemplate'), $lead),
                        'rephrased' => false,
                        'usedSampleLead' => $isSample,
                        'leadId' => $isSample ? null : $lead->id,
                        'leadName' => (string) $lead->businessName,
                    ],
                ]);
            }

            if (!$settings->hasLlm()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Add an AI provider and API key in Settings before previewing a rephrased version.',
                ], 422);
            }

            // personalize() honours the account-wide aiRephraseEnabled switch. Asking for a
            // rephrase here is an explicit, one-off request, so the flag is lifted on this
            // in-memory copy of the settings only - it is never saved.
            $previewSettings = clone $settings;
            $previewSettings->aiRephraseEnabled = true;
            $rephraser = new TemplateRenderService($previewSettings);

            // An unsaved template carrying the editor's current content: personalize() only
            // reads subjectTemplate/bodyTemplate/id off it, and this one is never persisted.
            $draft = new OutreachEmailTemplate([
                'usersId' => $userId,
                'name' => (string) $request->input('name', 'Preview'),
                'subjectTemplate' => (string) $request->input('subjectTemplate'),
                'bodyTemplate' => (string) $request->input('bodyTemplate'),
            ]);

            $result = $rephraser->personalize($draft, $lead);

            return response()->json([
                'success' => true,
                'message' => $result['rephrased']
                    ? 'Preview rendered with an AI variation.'
                    : 'Preview rendered from the template - the AI variation was not usable, so the original copy is shown.',
                'data' => [
                    'subject' => $result['subject'],
                    'body' => $result['body'],
                    'rephrased' => (bool) $result['rephrased'],
                    'usedSampleLead' => $isSample,
                    'leadId' => $isSample ? null : $lead->id,
                    'leadName' => (string) $lead->businessName,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] Template preview failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while rendering the preview.',
            ], 500);
        }
    }

    // ==================== INTERNALS ====================

    /**
     * Shared validation rules for store and update.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validateTemplate(Request $request)
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:190',
            'subjectTemplate' => 'required|string|max:500',
            'bodyTemplate' => 'required|string|max:' . self::MAX_BODY_CHARS,
            'isActive' => 'nullable|boolean',
            'sendOrder' => 'nullable|integer|min:1|max:9999',
        ], [
            'name.required' => 'Give the template a name you will recognise later.',
            'subjectTemplate.required' => 'A subject line is required.',
            'subjectTemplate.max' => 'The subject line is too long - keep it under 500 characters.',
            'bodyTemplate.required' => 'The email body cannot be empty.',
            'sendOrder.min' => 'Send order starts at 1.',
        ]);
    }

    /**
     * The user's own template, or null. Never trust an id on its own.
     *
     * @return \App\Modules\OutreachEngine\Models\OutreachEmailTemplate|null
     */
    protected function findOwned(int $id, int $userId)
    {
        return OutreachEmailTemplate::query()
            ->active()
            ->forUser($userId)
            ->where('id', $id)
            ->first();
    }

    /**
     * Requested send order, or the next free slot so a new template lands at the end of
     * the rotation instead of silently tying with an existing one.
     */
    protected function resolveSendOrder(Request $request, int $userId): int
    {
        if ($request->filled('sendOrder')) {
            return max(1, (int) $request->input('sendOrder'));
        }

        $highest = (int) OutreachEmailTemplate::query()
            ->active()
            ->forUser($userId)
            ->max('sendOrder');

        return $highest + 1;
    }

    /**
     * Which lead to render the preview against.
     *
     * An explicit leadId wins (scoped to the user, of course). Otherwise the newest lead
     * that has an email address, because that is the one closest to a real send. Null
     * means the caller should fall back to the synthetic sample lead.
     *
     * @return \App\Modules\OutreachEngine\Models\OutreachLead|null
     */
    protected function resolvePreviewLead(Request $request, int $userId)
    {
        if ($request->filled('leadId')) {
            return OutreachLead::query()
                ->active()
                ->forUser($userId)
                ->where('id', (int) $request->input('leadId'))
                ->first();
        }

        $lead = OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->hasEmail()
            ->orderByDesc('id')
            ->first();

        if ($lead) {
            return $lead;
        }

        return OutreachLead::query()
            ->active()
            ->forUser($userId)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Row payload for the templates table. Badges come from the model so the list built
     * in JavaScript and the one rendered by Blade cannot drift apart.
     *
     * @return array<string,mixed>
     */
    protected function presentTemplate(OutreachEmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'subjectTemplate' => $template->subjectTemplate,
            'bodyTemplate' => $template->bodyTemplate,
            'bodyPreview' => $template->body_preview,
            'isActive' => (bool) $template->isActive,
            'statusBadge' => $template->status_badge,
            'sendOrder' => (int) $template->sendOrder,
            'timesUsed' => (int) $template->timesUsed,
            'updatedAt' => $template->updated_at ? $template->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
