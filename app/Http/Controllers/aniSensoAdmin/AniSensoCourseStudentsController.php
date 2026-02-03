<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseEnrollment;
use App\Models\AsContentProgress;
use App\Models\AsTopicProgress;
use App\Models\AsCourseAuditLog;
use App\Models\AsTopic;
use App\Models\AsTopicContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AniSensoCourseStudentsController extends Controller
{
    /**
     * Get students list for a course (AJAX)
     */
    public function getStudents(Request $request, $courseId)
    {
        try {
            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);

            $query = AsCourseEnrollment::where('deleteStatus', 1)
                ->forCourse($courseId)
                ->with('topicProgress');

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereIn('accessClientId', function($q) use ($search) {
                    $q->select('id')
                      ->from('clients_access_login')
                      ->where('deleteStatus', 1)
                      ->where(function($sq) use ($search) {
                          $sq->where('clientFirstName', 'like', "%{$search}%")
                             ->orWhere('clientLastName', 'like', "%{$search}%")
                             ->orWhere('clientEmailAddress', 'like', "%{$search}%")
                             ->orWhere('clientPhoneNumber', 'like', "%{$search}%");
                      });
                });
            }

            // Apply status filter
            if ($request->filled('status')) {
                if ($request->status === 'expired') {
                    $query->where('expirationDate', '<', Carbon::now());
                } elseif ($request->status === 'active') {
                    $query->where(function($q) {
                        $q->whereNull('expirationDate')
                          ->orWhere('expirationDate', '>=', Carbon::now());
                    });
                } elseif ($request->status === 'inactive') {
                    $query->where('isActive', 0);
                }
            }

            // Apply expiration date range filter
            if ($request->filled('expirationFrom')) {
                $query->whereNotNull('expirationDate')
                      ->whereDate('expirationDate', '>=', $request->expirationFrom);
            }
            if ($request->filled('expirationTo')) {
                $query->whereNotNull('expirationDate')
                      ->whereDate('expirationDate', '<=', $request->expirationTo);
            }

            $enrollments = $query->orderBy('enrollmentDate', 'desc')->get();

            // Fetch client details
            $clientIds = $enrollments->pluck('accessClientId')->unique();
            $clients = DB::table('clients_access_login')
                ->whereIn('id', $clientIds)
                ->where('deleteStatus', 1)
                ->get()
                ->keyBy('id');

            // Calculate total topics for progress
            $totalTopics = $this->getTotalTopicsForCourse($courseId);

            // Build response
            $students = $enrollments->map(function($enrollment) use ($clients, $totalTopics) {
                $client = $clients->get($enrollment->accessClientId);
                $completedTopics = $enrollment->topicProgress->count();
                $progressPercent = $totalTopics > 0
                    ? round(($completedTopics / $totalTopics) * 100)
                    : 0;

                return [
                    'enrollmentId' => $enrollment->id,
                    'accessClientId' => $enrollment->accessClientId,
                    'fullName' => $client
                        ? trim("{$client->clientFirstName} {$client->clientMiddleName} {$client->clientLastName}")
                        : 'Unknown',
                    'email' => $client->clientEmailAddress ?? 'N/A',
                    'phone' => $client->clientPhoneNumber ?? 'N/A',
                    'enrollmentDate' => $enrollment->enrollmentDate->format('M j, Y'),
                    'expirationDate' => $enrollment->expirationDate?->format('Y-m-d'),
                    'formattedExpiration' => $enrollment->formatted_expiration,
                    'daysRemaining' => $enrollment->days_remaining,
                    'isExpired' => $enrollment->is_expired,
                    'progressPercent' => $progressPercent,
                    'completedTopics' => $completedTopics,
                    'totalTopics' => $totalTopics,
                    'isActive' => $enrollment->isActive
                ];
            });

            return response()->json([
                'success' => true,
                'students' => $students,
                'totalStudents' => $students->count(),
                'totalTopics' => $totalTopics,
                'courseName' => $course->courseName
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load students'
            ], 500);
        }
    }

    /**
     * Get single enrollment for editing
     */
    public function getEnrollment($enrollmentId)
    {
        try {
            $enrollment = AsCourseEnrollment::where('deleteStatus', 1)->findOrFail($enrollmentId);

            $client = DB::table('clients_access_login')
                ->where('id', $enrollment->accessClientId)
                ->where('deleteStatus', 1)
                ->first();

            $totalTopics = $this->getTotalTopicsForCourse($enrollment->asCoursesId);
            $completedTopics = $enrollment->topicProgress()->count();
            $progressPercent = $totalTopics > 0
                ? round(($completedTopics / $totalTopics) * 100)
                : 0;

            return response()->json([
                'success' => true,
                'enrollment' => [
                    'id' => $enrollment->id,
                    'accessClientId' => $enrollment->accessClientId,
                    'asCoursesId' => $enrollment->asCoursesId,
                    'enrollmentDate' => $enrollment->enrollmentDate->format('Y-m-d'),
                    'expirationDate' => $enrollment->expirationDate?->format('Y-m-d'),
                    'isActive' => $enrollment->isActive,
                    'progressPercent' => $progressPercent,
                    'completedTopics' => $completedTopics,
                    'totalTopics' => $totalTopics
                ],
                'client' => $client ? [
                    'id' => $client->id,
                    'fullName' => trim("{$client->clientFirstName} {$client->clientMiddleName} {$client->clientLastName}"),
                    'email' => $client->clientEmailAddress,
                    'phone' => $client->clientPhoneNumber
                ] : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found'
            ], 404);
        }
    }

    /**
     * Update enrollment (expiration, status, password)
     */
    public function updateEnrollment(Request $request, $enrollmentId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'expirationDate' => 'nullable|date',
                'isActive' => 'nullable|boolean',
                'newPassword' => 'nullable|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $enrollment = AsCourseEnrollment::where('deleteStatus', 1)->findOrFail($enrollmentId);
            $previousExpiration = $enrollment->expirationDate?->format('Y-m-d');
            $previousActive = $enrollment->isActive;

            // Get client name for audit log
            $client = DB::table('clients_access_login')
                ->where('id', $enrollment->accessClientId)
                ->first();
            $clientName = $client ? trim("{$client->clientFirstName} {$client->clientLastName}") : 'Unknown';

            // Update password if provided
            if ($request->filled('newPassword') && $request->filled('accessClientId')) {
                DB::table('clients_access_login')
                    ->where('id', $request->accessClientId)
                    ->update([
                        'clientPassword' => bcrypt($request->newPassword),
                        'updated_at' => Carbon::now()
                    ]);

                // Log password change
                AsCourseAuditLog::logAction(
                    $enrollment->asCoursesId,
                    'student_password_changed',
                    'student',
                    $enrollment->accessClientId,
                    $clientName,
                    'password',
                    '********',
                    '********',
                    "Password changed for {$clientName}"
                );
            }

            // Update expiration
            if ($request->has('expirationDate')) {
                $newExpiration = $request->expirationDate
                    ? Carbon::parse($request->expirationDate)->endOfDay()
                    : null;
                $enrollment->expirationDate = $newExpiration;

                // Log expiration change
                if ($previousExpiration !== ($newExpiration ? $newExpiration->format('Y-m-d') : null)) {
                    AsCourseAuditLog::logAction(
                        $enrollment->asCoursesId,
                        'student_expiration_changed',
                        'student',
                        $enrollment->accessClientId,
                        $clientName,
                        'expirationDate',
                        $previousExpiration ?? 'Lifetime',
                        $newExpiration ? $newExpiration->format('Y-m-d') : 'Lifetime',
                        "Student expiration updated for {$clientName}"
                    );
                }
            }

            // Update active status
            if ($request->has('isActive')) {
                $enrollment->isActive = $request->isActive;
            }

            $enrollment->save();

            return response()->json([
                'success' => true,
                'message' => 'Enrollment updated successfully',
                'enrollment' => [
                    'formattedExpiration' => $enrollment->formatted_expiration,
                    'daysRemaining' => $enrollment->days_remaining,
                    'isExpired' => $enrollment->is_expired,
                    'isActive' => $enrollment->isActive
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating enrollment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update enrollment'
            ], 500);
        }
    }

    /**
     * Enroll a new student
     */
    public function enrollStudent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'courseId' => 'required|integer',
                'accessClientId' => 'required|integer',
                'expirationDate' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            // Check if course exists
            $course = AsCourse::where('deleteStatus', true)->find($request->courseId);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found'
                ], 404);
            }

            // Check if already enrolled
            $existing = AsCourseEnrollment::where('accessClientId', $request->accessClientId)
                ->where('asCoursesId', $request->courseId)
                ->where('deleteStatus', 1)
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student is already enrolled in this course'
                ], 422);
            }

            $enrollment = AsCourseEnrollment::create([
                'accessClientId' => $request->accessClientId,
                'asCoursesId' => $request->courseId,
                'enrollmentDate' => Carbon::now(),
                'expirationDate' => $request->expirationDate
                    ? Carbon::parse($request->expirationDate)->endOfDay()
                    : null,
                'isActive' => 1,
                'deleteStatus' => 1
            ]);

            // Get client name for audit log
            $client = DB::table('clients_access_login')
                ->where('id', $request->accessClientId)
                ->first();
            $clientName = $client ? trim("{$client->clientFirstName} {$client->clientLastName}") : 'Unknown';

            // Log audit
            AsCourseAuditLog::logAction(
                $request->courseId,
                'student_enrolled',
                'student',
                $request->accessClientId,
                $clientName,
                null,
                null,
                null,
                "Student '{$clientName}' enrolled in course"
            );

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully',
                'enrollmentId' => $enrollment->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error enrolling student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll student'
            ], 500);
        }
    }

    /**
     * Remove student enrollment (soft delete)
     */
    public function removeStudent($enrollmentId)
    {
        try {
            $enrollment = AsCourseEnrollment::where('deleteStatus', 1)->findOrFail($enrollmentId);

            // Get client name for audit log
            $client = DB::table('clients_access_login')
                ->where('id', $enrollment->accessClientId)
                ->first();
            $clientName = $client ? trim("{$client->clientFirstName} {$client->clientLastName}") : 'Unknown';

            // Log audit before deletion
            AsCourseAuditLog::logAction(
                $enrollment->asCoursesId,
                'student_removed',
                'student',
                $enrollment->accessClientId,
                $clientName,
                null,
                null,
                null,
                "Student '{$clientName}' removed from course"
            );

            $enrollment->deleteStatus = 0;
            $enrollment->save();

            return response()->json([
                'success' => true,
                'message' => 'Student removed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove student'
            ], 500);
        }
    }

    /**
     * Reset student progress
     */
    public function resetProgress($enrollmentId)
    {
        try {
            $enrollment = AsCourseEnrollment::where('deleteStatus', 1)->findOrFail($enrollmentId);

            // Get previous progress count
            $previousProgress = $enrollment->topicProgress()->count();

            // Soft delete all topic progress records
            AsTopicProgress::where('enrollmentId', $enrollmentId)
                ->where('deleteStatus', 1)
                ->update(['deleteStatus' => 0]);

            // Get client name for audit log
            $client = DB::table('clients_access_login')
                ->where('id', $enrollment->accessClientId)
                ->first();
            $clientName = $client ? trim("{$client->clientFirstName} {$client->clientLastName}") : 'Unknown';

            // Log audit
            AsCourseAuditLog::logAction(
                $enrollment->asCoursesId,
                'student_progress_reset',
                'student',
                $enrollment->accessClientId,
                $clientName,
                'progress',
                "{$previousProgress} topics completed",
                '0 topics completed',
                "Progress reset for student '{$clientName}'"
            );

            return response()->json([
                'success' => true,
                'message' => 'Progress reset successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error resetting progress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset progress'
            ], 500);
        }
    }

    /**
     * Search available students (not yet enrolled in course)
     * Uses clients_access_login (store-specific logins)
     */
    public function searchAvailableStudents(Request $request, $courseId)
    {
        try {
            $search = $request->search ?? '';
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 20);

            // Get already enrolled client IDs
            $enrolledIds = AsCourseEnrollment::where('asCoursesId', $courseId)
                ->where('deleteStatus', 1)
                ->pluck('accessClientId')
                ->toArray();

            // Search in clients_access_login (store-specific logins)
            $query = DB::table('clients_access_login')
                ->where('deleteStatus', 1)
                ->where('isActive', 1);

            if (!empty($enrolledIds)) {
                $query->whereNotIn('id', $enrolledIds);
            }

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('clientFirstName', 'like', "%{$search}%")
                      ->orWhere('clientMiddleName', 'like', "%{$search}%")
                      ->orWhere('clientLastName', 'like', "%{$search}%")
                      ->orWhere('clientEmailAddress', 'like', "%{$search}%")
                      ->orWhere('clientPhoneNumber', 'like', "%{$search}%");
                });
            }

            // Get total count for pagination
            $total = $query->count();

            // Apply pagination
            $clients = $query->select('id', 'clientFirstName', 'clientMiddleName', 'clientLastName', 'clientPhoneNumber', 'clientEmailAddress', 'productStore')
                ->orderBy('clientFirstName', 'asc')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->map(function($client) {
                    return [
                        'id' => $client->id,
                        'fullName' => trim("{$client->clientFirstName} {$client->clientMiddleName} {$client->clientLastName}"),
                        'email' => $client->clientEmailAddress,
                        'phone' => $client->clientPhoneNumber,
                        'store' => $client->productStore
                    ];
                });

            $lastPage = ceil($total / $perPage);

            return response()->json([
                'success' => true,
                'data' => $clients,
                'current_page' => (int)$page,
                'last_page' => $lastPage,
                'per_page' => (int)$perPage,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to search students'
            ], 500);
        }
    }

    /**
     * Helper: Get total topics count for a course
     */
    private function getTotalTopicsForCourse($courseId)
    {
        return DB::table('as_courses_topics as t')
            ->join('as_courses_chapters as ch', 't.chapterId', '=', 'ch.id')
            ->where('ch.asCoursesId', $courseId)
            ->where('ch.deleteStatus', true)
            ->where('t.deleteStatus', true)
            ->count();
    }

    /**
     * Helper: Get total contents count for a course (legacy)
     */
    private function getTotalContentsForCourse($courseId)
    {
        return DB::table('as_topic_contents as c')
            ->join('as_courses_topics as t', 'c.topicId', '=', 't.id')
            ->join('as_courses_chapters as ch', 't.chapterId', '=', 'ch.id')
            ->where('ch.asCoursesId', $courseId)
            ->where('ch.deleteStatus', true)
            ->where('t.deleteStatus', true)
            ->where('c.deleteStatus', true)
            ->count();
    }

    /**
     * Send password reset email to student (placeholder)
     */
    public function sendPasswordResetEmail($accessClientId)
    {
        try {
            $client = DB::table('clients_access_login')
                ->where('id', $accessClientId)
                ->where('deleteStatus', 1)
                ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // TODO: Implement actual email sending
            // For now, just return a success message indicating it's queued
            Log::info("Password reset email requested for client: {$client->clientEmailAddress}");

            return response()->json([
                'success' => false,
                'message' => 'Email feature coming soon. Please use the password change fields above.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending password reset email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email'
            ], 500);
        }
    }
}
