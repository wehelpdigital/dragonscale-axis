<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCertificateTemplate;
use App\Models\AsCertificateAsset;
use App\Models\AsCourseAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AniSensoCourseCertificatesController extends Controller
{
    /**
     * Show the certificate designer page
     */
    public function designer(Request $request)
    {
        $courseId = $request->query('id');

        if (!$courseId) {
            return redirect()->route('anisenso-courses')
                ->with('error', 'Course ID is required');
        }

        $course = AsCourse::where('deleteStatus', true)->find($courseId);

        if (!$course) {
            return redirect()->route('anisenso-courses')
                ->with('error', 'Course not found');
        }

        $template = AsCertificateTemplate::getOrCreate($courseId);
        $dimensions = $template->getCanvasDimensions();
        $placeholders = AsCertificateTemplate::getPlaceholders();

        // Get course-specific and global assets
        $assets = AsCertificateAsset::active()
            ->where(function($q) use ($courseId) {
                $q->where('asCoursesId', $courseId)
                  ->orWhereNull('asCoursesId');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('aniSensoAdmin.certificate-designer', compact(
            'course',
            'template',
            'dimensions',
            'placeholders',
            'assets'
        ));
    }

    /**
     * Get certificate template data (AJAX)
     */
    public function getTemplate($courseId)
    {
        try {
            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);
            $template = AsCertificateTemplate::getOrCreate($courseId);
            $dimensions = $template->getCanvasDimensions();

            return response()->json([
                'success' => true,
                'template' => [
                    'id' => $template->id,
                    'certificateName' => $template->certificateName,
                    'paperSize' => $template->paperSize,
                    'orientation' => $template->orientation,
                    'templateData' => $template->templateData,
                    'backgroundColor' => $template->backgroundColor,
                    'backgroundImage' => $template->backgroundImage ? asset($template->backgroundImage) : null,
                    'isActive' => $template->isActive
                ],
                'dimensions' => $dimensions,
                'courseName' => $course->courseName
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching certificate template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load certificate template'
            ], 500);
        }
    }

    /**
     * Save certificate template
     */
    public function saveTemplate(Request $request, $courseId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'certificateName' => 'required|string|max:255',
                'paperSize' => 'required|in:letter,a4',
                'orientation' => 'required|in:landscape,portrait',
                'templateData' => 'required',
                'backgroundColor' => 'required|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);
            $template = AsCertificateTemplate::getOrCreate($courseId);

            // Decode templateData if it's a string
            $templateData = $request->templateData;
            if (is_string($templateData)) {
                $templateData = json_decode($templateData, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid template data format'
                    ], 422);
                }
            }

            $template->update([
                'certificateName' => $request->certificateName,
                'paperSize' => $request->paperSize,
                'orientation' => $request->orientation,
                'templateData' => $templateData,
                'backgroundColor' => $request->backgroundColor,
                'isActive' => $request->boolean('isActive', false)
            ]);

            // Log audit
            try {
                AsCourseAuditLog::logAction(
                    (int) $courseId,
                    'certificate_updated',
                    'certificate',
                    $template->id,
                    $template->certificateName,
                    null,
                    null,
                    null,
                    'Certificate template saved'
                );
            } catch (\Exception $auditException) {
                // Don't fail the save if audit logging fails
                Log::warning('Audit log failed for certificate save: ' . $auditException->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Certificate template saved successfully',
                'template' => [
                    'id' => $template->id,
                    'isActive' => $template->isActive
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error saving certificate template: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save certificate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload background image
     */
    public function uploadBackground(Request $request, $courseId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'background' => 'required|image|mimes:jpeg,png,jpg|max:5120' // 5MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);
            $template = AsCertificateTemplate::getOrCreate($courseId);

            // Delete old background if exists
            if ($template->backgroundImage && file_exists(public_path($template->backgroundImage))) {
                unlink(public_path($template->backgroundImage));
            }

            // Save new background
            $file = $request->file('background');
            $filename = 'cert_bg_' . $courseId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = 'images/anisenso/certificates/' . $filename;

            // Ensure directory exists
            $dir = public_path('images/anisenso/certificates');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $filename);

            $template->update(['backgroundImage' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Background uploaded successfully',
                'backgroundUrl' => asset($path)
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading certificate background: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload background'
            ], 500);
        }
    }

    /**
     * Remove background image
     */
    public function removeBackground($courseId)
    {
        try {
            $template = AsCertificateTemplate::active()->forCourse($courseId)->firstOrFail();

            if ($template->backgroundImage && file_exists(public_path($template->backgroundImage))) {
                unlink(public_path($template->backgroundImage));
            }

            $template->update(['backgroundImage' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Background removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing certificate background: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove background'
            ], 500);
        }
    }

    /**
     * Upload asset (image, logo, signature)
     */
    public function uploadAsset(Request $request, $courseId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'asset' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB max
                'assetType' => 'required|in:image,logo,signature,icon'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $file = $request->file('asset');
            $filename = 'cert_asset_' . $courseId . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = 'images/anisenso/certificates/assets/' . $filename;

            // Ensure directory exists
            $dir = public_path('images/anisenso/certificates/assets');
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }

            $file->move($dir, $filename);

            $asset = AsCertificateAsset::create([
                'asCoursesId' => $courseId,
                'assetName' => $file->getClientOriginalName(),
                'assetPath' => $path,
                'assetType' => $request->assetType,
                'fileSize' => $file->getSize(),
                'deleteStatus' => 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Asset uploaded successfully',
                'asset' => [
                    'id' => $asset->id,
                    'name' => $asset->assetName,
                    'url' => asset($asset->assetPath),
                    'type' => $asset->assetType
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading certificate asset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload asset'
            ], 500);
        }
    }

    /**
     * Get assets for a course
     */
    public function getAssets($courseId)
    {
        try {
            $assets = AsCertificateAsset::active()
                ->where(function($q) use ($courseId) {
                    $q->where('asCoursesId', $courseId)
                      ->orWhereNull('asCoursesId');
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->assetName,
                        'url' => asset($asset->assetPath),
                        'type' => $asset->assetType
                    ];
                });

            return response()->json([
                'success' => true,
                'assets' => $assets
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching certificate assets: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load assets'
            ], 500);
        }
    }

    /**
     * Delete an asset
     */
    public function deleteAsset($assetId)
    {
        try {
            $asset = AsCertificateAsset::where('deleteStatus', 1)->findOrFail($assetId);

            // Delete file
            if (file_exists(public_path($asset->assetPath))) {
                unlink(public_path($asset->assetPath));
            }

            $asset->update(['deleteStatus' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Asset deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting certificate asset: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete asset'
            ], 500);
        }
    }

    /**
     * Toggle certificate active status
     */
    public function toggleStatus($courseId)
    {
        try {
            $template = AsCertificateTemplate::active()->forCourse($courseId)->firstOrFail();
            $template->isActive = !$template->isActive;
            $template->save();

            AsCourseAuditLog::logAction(
                $courseId,
                $template->isActive ? 'certificate_enabled' : 'certificate_disabled',
                'certificate',
                $template->id,
                $template->certificateName,
                'isActive',
                $template->isActive ? 'false' : 'true',
                $template->isActive ? 'true' : 'false',
                'Certificate ' . ($template->isActive ? 'enabled' : 'disabled')
            );

            return response()->json([
                'success' => true,
                'message' => 'Certificate ' . ($template->isActive ? 'enabled' : 'disabled'),
                'isActive' => $template->isActive
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling certificate status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update certificate status'
            ], 500);
        }
    }
}
