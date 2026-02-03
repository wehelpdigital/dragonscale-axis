<?php

namespace App\Models;

class AsCourseAuditLog extends BaseModel
{
    protected $table = 'as_course_audit_logs';

    protected $fillable = [
        'asCoursesId',
        'userId',
        'userName',
        'actionType',
        'entityType',
        'entityId',
        'entityName',
        'fieldChanged',
        'previousValue',
        'newValue',
        'description',
        'ipAddress',
        'userAgent',
        'deleteStatus'
    ];

    protected $casts = [
        'asCoursesId' => 'integer',
        'userId' => 'integer',
        'entityId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('asCoursesId', $courseId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    public function scopeByEntityType($query, $entityType)
    {
        return $query->where('entityType', $entityType);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('actionType', $actionType);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('userId', $userId);
    }

    /**
     * Get human-readable action type label
     */
    public function getActionTypeLabelAttribute()
    {
        $labels = [
            // Course actions
            'course_created' => 'Course Created',
            'course_updated' => 'Course Updated',
            'course_status_changed' => 'Course Status Changed',
            'course_deleted' => 'Course Deleted',
            // Chapter actions
            'chapter_created' => 'Chapter Created',
            'chapter_updated' => 'Chapter Updated',
            'chapter_order_changed' => 'Chapter Order Changed',
            'chapter_deleted' => 'Chapter Deleted',
            // Topic actions
            'topic_created' => 'Topic Created',
            'topic_updated' => 'Topic Updated',
            'topic_order_changed' => 'Topic Order Changed',
            'topic_deleted' => 'Topic Deleted',
            // Content actions
            'content_created' => 'Content Created',
            'content_updated' => 'Content Updated',
            'content_order_changed' => 'Content Order Changed',
            'content_deleted' => 'Content Deleted',
            // Questionnaire actions
            'questionnaire_created' => 'Questionnaire Created',
            'questionnaire_updated' => 'Questionnaire Updated',
            'questionnaire_deleted' => 'Questionnaire Deleted',
            // Question actions
            'question_created' => 'Question Created',
            'question_updated' => 'Question Updated',
            'question_deleted' => 'Question Deleted',
            // Student/Enrollment actions
            'student_enrolled' => 'Student Enrolled',
            'student_expiration_changed' => 'Student Expiration Changed',
            'student_progress_reset' => 'Student Progress Reset',
            'student_removed' => 'Student Removed',
            // Comment actions
            'comment_created' => 'Comment Created',
            'comment_replied' => 'Comment Replied',
            'comment_deleted' => 'Comment Deleted',
            'comment_status_changed' => 'Comment Status Changed',
        ];

        return $labels[$this->actionType] ?? ucfirst(str_replace('_', ' ', $this->actionType));
    }

    /**
     * Get entity type badge class
     */
    public function getEntityTypeBadgeAttribute()
    {
        $badges = [
            'course' => 'bg-primary',
            'chapter' => 'bg-info',
            'topic' => 'bg-success',
            'content' => 'bg-warning',
            'questionnaire' => 'bg-purple',
            'question' => 'bg-pink',
            'student' => 'bg-secondary',
            'enrollment' => 'bg-secondary',
            'comment' => 'bg-dark',
        ];

        return $badges[$this->entityType] ?? 'bg-light';
    }

    /**
     * Static helper to create an audit log entry
     * Usage: AsCourseAuditLog::logAction($courseId, 'course_updated', 'course', $course->id, $course->courseName, 'courseName', $oldName, $newName, 'Course name updated');
     */
    public static function logAction(
        int $courseId,
        string $actionType,
        string $entityType,
        ?int $entityId = null,
        ?string $entityName = null,
        ?string $fieldChanged = null,
        ?string $previousValue = null,
        ?string $newValue = null,
        ?string $description = null
    ): self {
        $user = auth()->user();

        return self::create([
            'asCoursesId' => $courseId,
            'userId' => $user?->id,
            'userName' => $user?->name ?? 'System',
            'actionType' => $actionType,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'entityName' => $entityName,
            'fieldChanged' => $fieldChanged,
            'previousValue' => $previousValue,
            'newValue' => $newValue,
            'description' => $description,
            'ipAddress' => request()->ip(),
            'userAgent' => request()->userAgent(),
            'deleteStatus' => 1
        ]);
    }
}
