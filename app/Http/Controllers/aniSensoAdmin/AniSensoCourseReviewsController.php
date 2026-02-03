<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseReview;
use App\Models\AsReviewReply;
use App\Models\AsCourseAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AniSensoCourseReviewsController extends Controller
{
    /**
     * Get reviews for a course (AJAX)
     */
    public function getReviews(Request $request, $courseId)
    {
        try {
            $course = AsCourse::where('deleteStatus', true)->findOrFail($courseId);

            $query = AsCourseReview::active()
                ->forCourse($courseId)
                ->with(['replies', 'enrollment']);

            // Filter by rating
            if ($request->filled('rating')) {
                $query->byRating($request->rating);
            }

            // Filter by approval status
            if ($request->filled('approved')) {
                $query->where('isApproved', $request->approved === 'true');
            }

            // Pagination
            $perPage = $request->perPage ?? 10;
            $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Get enrollment client details
            $enrollmentIds = $reviews->pluck('enrollmentId')->unique();
            $enrollments = DB::table('as_course_enrollments as e')
                ->join('clients_access_login as c', 'e.accessClientId', '=', 'c.id')
                ->whereIn('e.id', $enrollmentIds)
                ->select('e.id as enrollmentId', 'c.clientFirstName', 'c.clientMiddleName', 'c.clientLastName', 'c.clientEmailAddress')
                ->get()
                ->keyBy('enrollmentId');

            // Calculate stats
            $stats = AsCourseReview::active()->forCourse($courseId)->selectRaw('
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            ')->first();

            // Format reviews
            $formattedReviews = $reviews->map(function($review) use ($enrollments) {
                $enrollment = $enrollments->get($review->enrollmentId);
                $studentName = $enrollment
                    ? trim("{$enrollment->clientFirstName} {$enrollment->clientMiddleName} {$enrollment->clientLastName}")
                    : 'Unknown Student';

                return [
                    'id' => $review->id,
                    'enrollmentId' => $review->enrollmentId,
                    'studentName' => $studentName,
                    'studentEmail' => $enrollment->clientEmailAddress ?? 'N/A',
                    'rating' => $review->rating,
                    'starsHtml' => $review->stars_html,
                    'reviewTitle' => $review->reviewTitle,
                    'reviewText' => $review->reviewText,
                    'isApproved' => $review->isApproved,
                    'isFeatured' => $review->isFeatured,
                    'formattedDate' => $review->formatted_date,
                    'timeAgo' => $review->time_ago,
                    'replies' => $review->replies->map(function($reply) {
                        return [
                            'id' => $reply->id,
                            'userName' => $reply->userName,
                            'replyText' => $reply->replyText,
                            'parsedReplyText' => $reply->parsed_reply_text,
                            'formattedDate' => $reply->formatted_date,
                            'timeAgo' => $reply->time_ago
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'reviews' => $formattedReviews,
                'stats' => [
                    'totalReviews' => (int) $stats->total_reviews,
                    'averageRating' => round($stats->average_rating, 1),
                    'fiveStar' => (int) $stats->five_star,
                    'fourStar' => (int) $stats->four_star,
                    'threeStar' => (int) $stats->three_star,
                    'twoStar' => (int) $stats->two_star,
                    'oneStar' => (int) $stats->one_star
                ],
                'pagination' => [
                    'currentPage' => $reviews->currentPage(),
                    'lastPage' => $reviews->lastPage(),
                    'perPage' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'from' => $reviews->firstItem(),
                    'to' => $reviews->lastItem()
                ],
                'courseName' => $course->courseName
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching reviews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load reviews'
            ], 500);
        }
    }

    /**
     * Delete a review (soft delete)
     */
    public function deleteReview($reviewId)
    {
        try {
            $review = AsCourseReview::active()->findOrFail($reviewId);

            // Get student name for audit
            $enrollment = DB::table('as_course_enrollments as e')
                ->join('clients_access_login as c', 'e.accessClientId', '=', 'c.id')
                ->where('e.id', $review->enrollmentId)
                ->select('c.clientFirstName', 'c.clientLastName')
                ->first();
            $studentName = $enrollment ? trim("{$enrollment->clientFirstName} {$enrollment->clientLastName}") : 'Unknown';

            // Log audit before deletion
            AsCourseAuditLog::logAction(
                $review->asCoursesId,
                'review_deleted',
                'review',
                $review->id,
                "{$review->rating}-star review by {$studentName}",
                null,
                null,
                null,
                "Deleted {$review->rating}-star review by {$studentName}"
            );

            $review->deleteStatus = 0;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting review: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review'
            ], 500);
        }
    }

    /**
     * Toggle review approval status
     */
    public function toggleApproval($reviewId)
    {
        try {
            $review = AsCourseReview::active()->findOrFail($reviewId);
            $review->isApproved = !$review->isApproved;
            $review->save();

            // Log audit
            AsCourseAuditLog::logAction(
                $review->asCoursesId,
                $review->isApproved ? 'review_approved' : 'review_unapproved',
                'review',
                $review->id,
                null,
                'isApproved',
                $review->isApproved ? 'false' : 'true',
                $review->isApproved ? 'true' : 'false',
                "Review " . ($review->isApproved ? 'approved' : 'unapproved')
            );

            return response()->json([
                'success' => true,
                'message' => 'Review ' . ($review->isApproved ? 'approved' : 'unapproved'),
                'isApproved' => $review->isApproved
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling review approval: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review'
            ], 500);
        }
    }

    /**
     * Toggle review featured status
     */
    public function toggleFeatured($reviewId)
    {
        try {
            $review = AsCourseReview::active()->findOrFail($reviewId);
            $review->isFeatured = !$review->isFeatured;
            $review->save();

            return response()->json([
                'success' => true,
                'message' => 'Review ' . ($review->isFeatured ? 'featured' : 'unfeatured'),
                'isFeatured' => $review->isFeatured
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling review featured: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review'
            ], 500);
        }
    }

    /**
     * Add a reply to a review
     */
    public function addReply(Request $request, $reviewId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'replyText' => 'required|string|max:2000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            $review = AsCourseReview::active()->findOrFail($reviewId);

            $reply = AsReviewReply::create([
                'reviewId' => $reviewId,
                'userId' => Auth::id(),
                'userName' => Auth::user()->name ?? 'Admin',
                'replyText' => $request->replyText,
                'deleteStatus' => 1
            ]);

            // Log audit
            AsCourseAuditLog::logAction(
                $review->asCoursesId,
                'review_replied',
                'review',
                $review->id,
                null,
                null,
                null,
                null,
                "Admin replied to review"
            );

            return response()->json([
                'success' => true,
                'message' => 'Reply added successfully',
                'reply' => [
                    'id' => $reply->id,
                    'userName' => $reply->userName,
                    'replyText' => $reply->replyText,
                    'parsedReplyText' => $reply->parsed_reply_text,
                    'formattedDate' => $reply->formatted_date,
                    'timeAgo' => $reply->time_ago
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to add reply'
            ], 500);
        }
    }

    /**
     * Delete a reply
     */
    public function deleteReply($replyId)
    {
        try {
            $reply = AsReviewReply::where('deleteStatus', 1)->findOrFail($replyId);
            $reply->deleteStatus = 0;
            $reply->save();

            return response()->json([
                'success' => true,
                'message' => 'Reply deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting reply: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reply'
            ], 500);
        }
    }
}
