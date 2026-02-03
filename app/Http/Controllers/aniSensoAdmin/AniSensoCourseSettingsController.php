<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseSettings;
use App\Models\AsCourseAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AniSensoCourseSettingsController extends Controller
{
    /**
     * Get settings for a course
     */
    public function getSettings($courseId)
    {
        try {
            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);
            $settings = AsCourseSettings::getOrCreate($courseId);

            return response()->json([
                'success' => true,
                'settings' => [
                    'id' => $settings->id,
                    'asCoursesId' => $settings->asCoursesId,
                    'contentAccessMode' => $settings->contentAccessMode,
                    'quizBlocksNextChapter' => $settings->quizBlocksNextChapter,
                    'accessModeLabel' => $settings->access_mode_label,
                    'settingsDescription' => $settings->settings_description
                ],
                'courseName' => $course->courseName
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching course settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings'
            ], 500);
        }
    }

    /**
     * Update Course Flow settings
     */
    public function updateCourseFlow(Request $request, $courseId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'contentAccessMode' => 'required|in:open,sequential',
                'quizBlocksNextChapter' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);
            $settings = AsCourseSettings::getOrCreate($courseId);

            // Track changes for audit
            $changes = [];

            if ($settings->contentAccessMode !== $request->contentAccessMode) {
                $changes[] = [
                    'field' => 'contentAccessMode',
                    'old' => $settings->contentAccessMode,
                    'new' => $request->contentAccessMode,
                    'label' => 'Content Access Mode'
                ];
            }

            if ($settings->quizBlocksNextChapter !== (bool) $request->quizBlocksNextChapter) {
                $changes[] = [
                    'field' => 'quizBlocksNextChapter',
                    'old' => $settings->quizBlocksNextChapter ? 'Yes' : 'No',
                    'new' => $request->quizBlocksNextChapter ? 'Yes' : 'No',
                    'label' => 'Quiz Blocks Next Chapter'
                ];
            }

            // Update settings
            $settings->update([
                'contentAccessMode' => $request->contentAccessMode,
                'quizBlocksNextChapter' => (bool) $request->quizBlocksNextChapter
            ]);

            // Log audit for each change
            foreach ($changes as $change) {
                AsCourseAuditLog::logAction(
                    $courseId,
                    'settings_updated',
                    'settings',
                    $settings->id,
                    'Course Flow Settings',
                    $change['field'],
                    $change['old'],
                    $change['new'],
                    "{$change['label']} changed from '{$change['old']}' to '{$change['new']}'"
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Course flow settings saved successfully',
                'settings' => [
                    'contentAccessMode' => $settings->contentAccessMode,
                    'quizBlocksNextChapter' => $settings->quizBlocksNextChapter,
                    'accessModeLabel' => $settings->access_mode_label,
                    'settingsDescription' => $settings->settings_description
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating course flow settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings'
            ], 500);
        }
    }
}
