<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseAuditLog;
use App\Models\AsCourseChapter;
use App\Models\AsTopic;
use App\Models\AsTopicResource;
use App\Models\AsImageLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AniSensoCourseController extends Controller
{
    /**
     * Display the Ani-Senso Course page
     */
    public function index(Request $request)
    {
        $query = AsCourse::where('deleteStatus', true);

        // Search filter by course name
        if ($request->filled('search')) {
            $query->where('courseName', 'like', '%' . $request->search . '%');
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('isActive', $request->status === 'active');
        }

        $courses = $query->orderBy('created_at', 'desc')->get();

        return view('aniSensoAdmin.courses', compact('courses'));
    }

    /**
     * Toggle the status of a course (active/inactive)
     */
    public function toggleStatus($id)
    {
        try {
            $course = AsCourse::findOrFail($id);
            $previousStatus = $course->isActive ? 'Active' : 'Inactive';
            $course->isActive = !$course->isActive;
            $course->save();
            $newStatus = $course->isActive ? 'Active' : 'Inactive';

            // Log audit
            AsCourseAuditLog::logAction(
                $course->id,
                'course_status_changed',
                'course',
                $course->id,
                $course->courseName,
                'isActive',
                $previousStatus,
                $newStatus,
                "Course status changed from {$previousStatus} to {$newStatus}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Course status updated successfully!',
                'isActive' => $course->isActive
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling course status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update course status.'
            ], 500);
        }
    }

    /**
     * Show the form for creating a new course
     */
    public function create()
    {
        return view('aniSensoAdmin.courses-add');
    }

    /**
     * Store a newly created course
     */
    public function store(Request $request)
    {
        $request->validate([
            'courseName' => 'required|string|max:255',
            'courseSmallDescription' => 'required|string|max:500',
            'courseBigDescription' => 'required|string',
            'courseImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        $course = new AsCourse();
        $course->courseName = $request->courseName;
        $course->courseSmallDescription = $request->courseSmallDescription;
        $course->courseBigDescription = $request->courseBigDescription;
        $course->isActive = true;
        $course->deleteStatus = true;

        if ($request->hasFile('courseImage')) {
            $image = $request->file('courseImage');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/courses'), $imageName);
            $course->courseImage = 'images/courses/' . $imageName;
        }

        $course->save();

        // Log audit
        AsCourseAuditLog::logAction(
            $course->id,
            'course_created',
            'course',
            $course->id,
            $course->courseName,
            null,
            null,
            null,
            "Course '{$course->courseName}' created"
        );

        return redirect()->route('anisenso-courses')->with('success', 'Course saved successfully!');
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'courseName' => 'required|string|max:255',
            'courseSmallDescription' => 'required|string|max:500',
            'courseBigDescription' => 'required|string',
            'courseImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        $course = AsCourse::findOrFail($id);

        // Track changes for audit
        $changes = [];
        if ($course->courseName !== $request->courseName) {
            $changes[] = ['field' => 'courseName', 'old' => $course->courseName, 'new' => $request->courseName];
        }
        if ($course->courseSmallDescription !== $request->courseSmallDescription) {
            $changes[] = ['field' => 'courseSmallDescription', 'old' => $course->courseSmallDescription, 'new' => $request->courseSmallDescription];
        }

        $course->courseName = $request->courseName;
        $course->courseSmallDescription = $request->courseSmallDescription;
        $course->courseBigDescription = $request->courseBigDescription;

        if ($request->hasFile('courseImage')) {
            // Delete old image if exists
            if ($course->courseImage && file_exists(public_path($course->courseImage))) {
                unlink(public_path($course->courseImage));
            }

            $image = $request->file('courseImage');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/courses'), $imageName);
            $course->courseImage = 'images/courses/' . $imageName;
            $changes[] = ['field' => 'courseImage', 'old' => 'Previous image', 'new' => 'New image uploaded'];
        }

        $course->save();

        // Log audit for each change
        foreach ($changes as $change) {
            AsCourseAuditLog::logAction(
                $course->id,
                'course_updated',
                'course',
                $course->id,
                $course->courseName,
                $change['field'],
                $change['old'],
                $change['new'],
                "Course field '{$change['field']}' updated"
            );
        }

        // If no specific field changes, log general update
        if (empty($changes)) {
            AsCourseAuditLog::logAction(
                $course->id,
                'course_updated',
                'course',
                $course->id,
                $course->courseName,
                null,
                null,
                null,
                "Course '{$course->courseName}' updated"
            );
        }

        return redirect()->route('anisenso-courses')->with('success', 'Course updated successfully!');
    }

    /**
     * Show the form for editing the specified course (JSON response for AJAX)
     */
    public function edit($id)
    {
        $course = AsCourse::findOrFail($id);
        return response()->json($course);
    }

    /**
     * Show the edit page for the specified course
     */
    public function editPage(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);
        return view('aniSensoAdmin.courses-edit', compact('course'));
    }

    /**
     * Remove the specified course
     */
    public function destroy($id)
    {
        try {
            $course = AsCourse::findOrFail($id);
            $courseName = $course->courseName;

            // Log audit before deletion
            AsCourseAuditLog::logAction(
                $course->id,
                'course_deleted',
                'course',
                $course->id,
                $courseName,
                null,
                null,
                null,
                "Course '{$courseName}' deleted"
            );

            // Soft delete by setting deleteStatus to false
            $course->deleteStatus = false;
            $course->save();

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the course contents page with nested chapters, topics, and contents
     */
    public function contents(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);

        // Get chapters with nested topics and contents
        $chapters = AsCourseChapter::where('asCoursesId', $courseId)
                                  ->where('deleteStatus', true)
                                  ->orderBy('chapterOrder', 'ASC')
                                  ->with(['topics' => function($query) {
                                      $query->where('deleteStatus', true)
                                            ->orderBy('topicsOrder', 'ASC')
                                            ->with(['contents' => function($q) {
                                                $q->where('deleteStatus', true)
                                                  ->orderBy('contentOrder', 'ASC')
                                                  ->with('resources');
                                            }]);
                                  }])
                                  ->get();

        // Get questionnaires with questions and answers
        $questionnaires = \App\Models\AsQuestionnaire::where('asCoursesId', $courseId)
                                                      ->where('deleteStatus', true)
                                                      ->orderBy('itemOrder', 'ASC')
                                                      ->with(['questions' => function($query) {
                                                          $query->where('deleteStatus', true)
                                                                ->orderBy('questionOrder', 'ASC')
                                                                ->with('answers');
                                                      }])
                                                      ->get();

        // Merge chapters and questionnaires into a unified list, sorted by order
        $courseItems = collect();

        foreach ($chapters as $chapter) {
            $courseItems->push([
                'type' => 'chapter',
                'order' => $chapter->chapterOrder,
                'data' => $chapter
            ]);
        }

        foreach ($questionnaires as $questionnaire) {
            $courseItems->push([
                'type' => 'questionnaire',
                'order' => $questionnaire->itemOrder,
                'data' => $questionnaire
            ]);
        }

        // Sort by order
        $courseItems = $courseItems->sortBy('order')->values();

        return view('aniSensoAdmin.courses-contents', compact('course', 'chapters', 'questionnaires', 'courseItems'));
    }

    /**
     * Show the form for adding a new chapter
     */
    public function addChapter(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);

        return view('aniSensoAdmin.courses-contents-add-chapter', compact('course'));
    }

    /**
     * Show the form for editing a chapter
     */
    public function editChapter(Request $request)
    {
        $chapterId = $request->query('chapid');
        $chapter = AsCourseChapter::findOrFail($chapterId);
        $course = AsCourse::findOrFail($chapter->asCoursesId);

        return view('aniSensoAdmin.courses-contents-edit', compact('chapter', 'course'));
    }

    /**
     * Store a new chapter
     */
    public function storeChapter(Request $request)
    {
        $request->validate([
            'courseId' => 'required|exists:as_courses,id',
            'chapterTitle' => 'required|string|max:255',
            'chapterDescription' => 'required|string|max:1000',
            'chapterCoverPhoto' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        // Get the highest order number for this course
        $maxOrder = AsCourseChapter::where('asCoursesId', $request->courseId)
                                  ->where('deleteStatus', 1)
                                  ->max('chapterOrder') ?? 0;

        $chapter = new AsCourseChapter();
        $chapter->asCoursesId = $request->courseId;
        $chapter->chapterTitle = $request->chapterTitle;
        $chapter->chapterDescription = $request->chapterDescription;
        $chapter->chapterOrder = $maxOrder + 1;
        $chapter->deleteStatus = 1;

        // Handle cover photo upload
        if ($request->hasFile('chapterCoverPhoto')) {
            $image = $request->file('chapterCoverPhoto');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/chapters'), $imageName);
            $chapter->chapterCoverPhoto = 'images/chapters/' . $imageName;
        }

        $chapter->save();

        // Log audit
        AsCourseAuditLog::logAction(
            $request->courseId,
            'chapter_created',
            'chapter',
            $chapter->id,
            $chapter->chapterTitle,
            null,
            null,
            null,
            "Chapter '{$chapter->chapterTitle}' created"
        );

        return redirect()->route('anisenso-courses.contents', ['id' => $request->courseId])
                        ->with('success', 'Chapter added successfully!');
    }

    /**
     * Update the specified chapter
     */
    public function updateChapter(Request $request, $id)
    {
        try {
            $request->validate([
                'chapterTitle' => 'required|string|max:255',
                'chapterDescription' => 'required|string|max:1000',
                'chapterCoverPhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120'
            ]);

            $chapter = AsCourseChapter::findOrFail($id);

            // Track changes for audit
            $changes = [];
            if ($chapter->chapterTitle !== $request->chapterTitle) {
                $changes[] = ['field' => 'chapterTitle', 'old' => $chapter->chapterTitle, 'new' => $request->chapterTitle];
            }
            if ($chapter->chapterDescription !== $request->chapterDescription) {
                $changes[] = ['field' => 'chapterDescription', 'old' => 'Previous description', 'new' => 'Updated description'];
            }

            $chapter->chapterTitle = $request->chapterTitle;
            $chapter->chapterDescription = $request->chapterDescription;

            // Handle cover photo upload if provided
            if ($request->hasFile('chapterCoverPhoto')) {
                // Delete old image if exists
                if ($chapter->chapterCoverPhoto && file_exists(public_path($chapter->chapterCoverPhoto))) {
                    unlink(public_path($chapter->chapterCoverPhoto));
                }

                $image = $request->file('chapterCoverPhoto');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('images/chapters'), $imageName);
                $chapter->chapterCoverPhoto = 'images/chapters/' . $imageName;
                $changes[] = ['field' => 'chapterCoverPhoto', 'old' => 'Previous image', 'new' => 'New image uploaded'];
            }

            $chapter->save();

            // Log audit for each change
            foreach ($changes as $change) {
                AsCourseAuditLog::logAction(
                    $chapter->asCoursesId,
                    'chapter_updated',
                    'chapter',
                    $chapter->id,
                    $chapter->chapterTitle,
                    $change['field'],
                    $change['old'],
                    $change['new'],
                    "Chapter field '{$change['field']}' updated"
                );
            }

            // If no specific field changes, log general update
            if (empty($changes)) {
                AsCourseAuditLog::logAction(
                    $chapter->asCoursesId,
                    'chapter_updated',
                    'chapter',
                    $chapter->id,
                    $chapter->chapterTitle,
                    null,
                    null,
                    null,
                    "Chapter '{$chapter->chapterTitle}' updated"
                );
            }

            Log::info('Chapter updated successfully', [
                'chapter_id' => $chapter->id,
                'course_id' => $chapter->asCoursesId
            ]);

            return redirect()->route('anisenso-courses.contents', ['id' => $chapter->asCoursesId])
                            ->with('success', 'Chapter updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating chapter: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Failed to update chapter: ' . $e->getMessage()]);
        }
    }

    /**
     * Update chapter order after drag and drop
     */
    public function updateChapterOrder(Request $request)
    {
        $request->validate([
            'chapters' => 'required|array',
            'chapters.*.id' => 'required|exists:as_courses_chapters,id',
            'chapters.*.order' => 'required|integer|min:1'
        ]);

        $courseId = null;
        foreach ($request->chapters as $chapterData) {
            $chapter = AsCourseChapter::find($chapterData['id']);
            if ($chapter && $courseId === null) {
                $courseId = $chapter->asCoursesId;
            }
            AsCourseChapter::where('id', $chapterData['id'])
                          ->update(['chapterOrder' => $chapterData['order']]);
        }

        // Log audit for order change
        if ($courseId) {
            AsCourseAuditLog::logAction(
                $courseId,
                'chapter_order_changed',
                'chapter',
                null,
                null,
                'chapterOrder',
                'Previous order',
                'New order',
                'Chapter order was reordered'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Chapter order updated successfully!'
        ]);
    }

    /**
     * Display the course topics page
     */
    public function courseTopics(Request $request)
    {
        $courseId = $request->query('id');
        $chapterId = $request->query('chap');

        $course = AsCourse::findOrFail($courseId);
        $chapter = AsCourseChapter::findOrFail($chapterId);

        // Get topics for this chapter, ordered by topicsOrder
        $topics = AsTopic::where('chapterId', $chapterId)
                        ->where('deleteStatus', 1)
                        ->orderBy('topicsOrder', 'ASC')
                        ->get();

        return view('aniSensoAdmin.course-topics', compact('course', 'chapter', 'topics'));
    }

    /**
     * Display all topics across all chapters for a course
     */
    public function courseAllTopics(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);

        // Get all chapters for this course with their topics
        $chapters = AsCourseChapter::where('asCoursesId', $courseId)
                                  ->where('deleteStatus', 1)
                                  ->orderBy('chapterOrder', 'ASC')
                                  ->with(['topics' => function($query) {
                                      $query->where('deleteStatus', 1)
                                            ->orderBy('topicsOrder', 'ASC');
                                  }])
                                  ->get();

        return view('aniSensoAdmin.course-all-topics', compact('course', 'chapters'));
    }

        /**
     * Show the form for adding a new topic
     */
    public function addTopic(Request $request)
    {
        $courseId = $request->query('id');
        $chapterId = $request->query('chap');

        $course = AsCourse::findOrFail($courseId);
        $chapter = AsCourseChapter::findOrFail($chapterId);

        return view('aniSensoAdmin.course-topics-add', compact('course', 'chapter'));
    }

    /**
     * Show the form for editing a topic
     */
    public function editTopic(Request $request)
    {
        $topicId = $request->query('topid');
        $topic = AsTopic::findOrFail($topicId);
        $chapter = AsCourseChapter::findOrFail($topic->chapterId);
        $course = AsCourse::findOrFail($chapter->asCoursesId);

        return view('aniSensoAdmin.course-topics-edit', compact('topic', 'chapter', 'course'));
    }

    /**
     * Show downloadable resources for a topic
     */
    public function topicResources(Request $request)
    {
        $topicId = $request->query('topid');
        $topic = AsTopic::findOrFail($topicId);
        $chapter = AsCourseChapter::findOrFail($topic->chapterId);
        $course = AsCourse::findOrFail($chapter->asCoursesId);

        // Fetch resources for this topic where deleteStatus = 1
        $resources = AsTopicResource::where('asTopicsId', $topicId)
                                   ->where('deleteStatus', 1)
                                   ->orderBy('resourcesOrder', 'asc')
                                   ->get();

        return view('aniSensoAdmin.course-topics-resources', compact('topic', 'chapter', 'course', 'resources'));
    }

    /**
     * Upload resource file for a topic
     */
    public function uploadResource(Request $request)
    {
        try {
            Log::info('Upload request received', [
                'allParams' => $request->all(),
                'topicId' => $request->input('topicId'),
                'hasFile' => $request->hasFile('file'),
                'fileSize' => $request->file('file') ? $request->file('file')->getSize() : 'no file'
            ]);
            $request->validate([
                'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:51200', // 50MB
                'topicId' => 'required|exists:as_courses_topics,id'
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = $file->getClientOriginalName();
                $fileExtension = $file->getClientOriginalExtension();

                // Generate unique filename
                $uniqueFileName = time() . '_' . uniqid() . '.' . $fileExtension;

                // Create directory if it doesn't exist
                $uploadPath = public_path('uploads/resources');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Move file to uploads directory
                $file->move($uploadPath, $uniqueFileName);
                $fileUrl = 'uploads/resources/' . $uniqueFileName;

                // Get the next order number
                $maxOrder = AsTopicResource::where('asTopicsId', $request->topicId)
                                         ->where('deleteStatus', 1)
                                         ->max('resourcesOrder');
                $nextOrder = ($maxOrder ?? 0) + 1;

                // Save to database
                $resource = new AsTopicResource();
                $resource->asTopicsId = $request->topicId;
                $resource->fileName = $fileName;
                $resource->fileUrl = $fileUrl;
                $resource->resourcesOrder = $nextOrder;
                $resource->deleteStatus = 1;
                $resource->save();

                Log::info('Resource uploaded successfully', [
                    'resource_id' => $resource->id,
                    'topic_id' => $request->topicId,
                    'file_name' => $fileName
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'resource' => [
                        'id' => $resource->id,
                        'fileName' => $resource->fileName,
                        'fileUrl' => asset($resource->fileUrl)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No file uploaded'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Error uploading resource: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created topic
     */
    public function storeTopic(Request $request)
    {
        try {
            $request->validate([
                'courseId' => 'required|exists:as_courses,id',
                'chapterId' => 'required|exists:as_courses_chapters,id',
                'topicTitle' => 'required|string|max:255',
                'topicDescription' => 'required|string|max:1000',
                'topicContent' => 'required|string'
            ]);

            // Get the highest order number for this chapter
            $maxOrder = AsTopic::where('chapterId', $request->chapterId)
                              ->where('deleteStatus', 1)
                              ->max('topicsOrder') ?? 0;

            $topic = new AsTopic();
            $topic->chapterId = $request->chapterId;
            $topic->topicTitle = $request->topicTitle;
            $topic->topicDescription = $request->topicDescription;
            $topic->topicContent = $request->topicContent;
            $topic->topicsOrder = $maxOrder + 1;
            $topic->deleteStatus = 1;

            $topic->save();

            // Log audit
            AsCourseAuditLog::logAction(
                $request->courseId,
                'topic_created',
                'topic',
                $topic->id,
                $topic->topicTitle,
                null,
                null,
                null,
                "Topic '{$topic->topicTitle}' created"
            );

            Log::info('Topic saved successfully', [
                'topic_id' => $topic->id,
                'chapter_id' => $request->chapterId,
                'course_id' => $request->courseId
            ]);

                    return redirect()->route('anisenso-courses-topics', ['id' => $request->courseId, 'chap' => $request->chapterId])
                        ->with('success', 'Topic added successfully!');
        } catch (\Exception $e) {
            Log::error('Error saving topic: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Failed to save topic: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified topic
     */
    public function updateTopic(Request $request, $id)
    {
        try {
            $request->validate([
                'topicTitle' => 'required|string|max:255',
                'topicDescription' => 'required|string|max:1000',
                'topicContent' => 'required|string'
            ]);

            $topic = AsTopic::findOrFail($id);

            // Track changes for audit
            $changes = [];
            if ($topic->topicTitle !== $request->topicTitle) {
                $changes[] = ['field' => 'topicTitle', 'old' => $topic->topicTitle, 'new' => $request->topicTitle];
            }
            if ($topic->topicDescription !== $request->topicDescription) {
                $changes[] = ['field' => 'topicDescription', 'old' => 'Previous description', 'new' => 'Updated description'];
            }
            if ($topic->topicContent !== $request->topicContent) {
                $changes[] = ['field' => 'topicContent', 'old' => 'Previous content', 'new' => 'Updated content'];
            }

            $topic->topicTitle = $request->topicTitle;
            $topic->topicDescription = $request->topicDescription;
            $topic->topicContent = $request->topicContent;

            $topic->save();

            // Log audit for each change
            foreach ($changes as $change) {
                AsCourseAuditLog::logAction(
                    $request->courseId,
                    'topic_updated',
                    'topic',
                    $topic->id,
                    $topic->topicTitle,
                    $change['field'],
                    $change['old'],
                    $change['new'],
                    "Topic field '{$change['field']}' updated"
                );
            }

            // If no specific field changes, log general update
            if (empty($changes)) {
                AsCourseAuditLog::logAction(
                    $request->courseId,
                    'topic_updated',
                    'topic',
                    $topic->id,
                    $topic->topicTitle,
                    null,
                    null,
                    null,
                    "Topic '{$topic->topicTitle}' updated"
                );
            }

            Log::info('Topic updated successfully', [
                'topic_id' => $topic->id,
                'chapter_id' => $topic->chapterId
            ]);

            return redirect()->route('anisenso-courses-topics', ['id' => $request->courseId, 'chap' => $request->chapterId])
                            ->with('success', 'Topic updated successfully!');
        } catch (\Exception $e) {
            Log::error('Error updating topic: ' . $e->getMessage());
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Failed to update topic: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete the specified chapter (soft delete)
     */
    public function destroyChapter($id)
    {
        try {
            $chapter = AsCourseChapter::findOrFail($id);
            $chapterTitle = $chapter->chapterTitle;
            $courseId = $chapter->asCoursesId;

            // Log audit before deletion
            AsCourseAuditLog::logAction(
                $courseId,
                'chapter_deleted',
                'chapter',
                $chapter->id,
                $chapterTitle,
                null,
                null,
                null,
                "Chapter '{$chapterTitle}' deleted"
            );

            // Soft delete - update deleteStatus to 0
            $chapter->deleteStatus = 0;
            $chapter->save();

            Log::info('Chapter soft deleted successfully', [
                'chapter_id' => $chapter->id,
                'course_id' => $courseId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chapter deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting chapter: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chapter: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update topic order after drag and drop
     */
    public function updateTopicOrder(Request $request)
    {
        $request->validate([
            'topics' => 'required|array',
            'topics.*.id' => 'required|exists:as_courses_topics,id',
            'topics.*.order' => 'required|integer|min:1'
        ]);

        $courseId = null;
        foreach ($request->topics as $topicData) {
            $topic = AsTopic::with('chapter')->find($topicData['id']);
            if ($topic && $topic->chapter && $courseId === null) {
                $courseId = $topic->chapter->asCoursesId;
            }
            AsTopic::where('id', $topicData['id'])
                  ->update(['topicsOrder' => $topicData['order']]);
        }

        // Log audit for order change
        if ($courseId) {
            AsCourseAuditLog::logAction(
                $courseId,
                'topic_order_changed',
                'topic',
                null,
                null,
                'topicsOrder',
                'Previous order',
                'New order',
                'Topic order was reordered'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Topic order updated successfully!'
        ]);
    }

    /**
     * Delete the specified topic (soft delete)
     */
    public function destroyTopic($id)
    {
        try {
            $topic = AsTopic::with('chapter')->findOrFail($id);
            $topicTitle = $topic->topicTitle;
            $courseId = $topic->chapter ? $topic->chapter->asCoursesId : null;

            // Log audit before deletion
            if ($courseId) {
                AsCourseAuditLog::logAction(
                    $courseId,
                    'topic_deleted',
                    'topic',
                    $topic->id,
                    $topicTitle,
                    null,
                    null,
                    null,
                    "Topic '{$topicTitle}' deleted"
                );
            }

            // Soft delete - update deleteStatus to 0
            $topic->deleteStatus = 0;
            $topic->save();

            Log::info('Topic soft deleted successfully', [
                'topic_id' => $topic->id,
                'chapter_id' => $topic->chapterId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Topic deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting topic: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete topic: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update resource order after drag and drop
     */
    public function updateResourceOrder(Request $request)
    {
        $request->validate([
            'resources' => 'required|array',
            'resources.*.id' => 'required|exists:as_courses_topics_resources,id',
            'resources.*.order' => 'required|integer|min:1'
        ]);

        foreach ($request->resources as $resourceData) {
            AsTopicResource::where('id', $resourceData['id'])
                          ->update(['resourcesOrder' => $resourceData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Resource order updated successfully!'
        ]);
    }

    /**
     * Upload image for TinyMCE editor
     */
    public function uploadImage(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!Auth::check()) {
                return response()->json(['error' => 'Authentication required'], 401);
            }

            $request->validate([
                'file' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240'
            ]);

            if ($request->hasFile('file')) {
                $image = $request->file('file');
                $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

                // Create directory if it doesn't exist
                $uploadPath = public_path('images/topics');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $image->move($uploadPath, $imageName);
                $imagePath = 'images/topics/' . $imageName;

                // Save to image library
                $imageLibrary = new AsImageLibrary();
                $imageLibrary->imageUrl = $imagePath;
                $imageLibrary->save();

                return response()->json([
                    'location' => asset($imagePath)
                ]);
            }

            return response()->json(['error' => 'No file uploaded'], 400);
        } catch (\Exception $e) {
            Log::error('Image upload error: ' . $e->getMessage());

            // Check if it's a file size error
            if (strpos($e->getMessage(), 'greater than') !== false) {
                return response()->json([
                    'error' => 'File size too large. Maximum allowed size is 10MB. Please compress your image or choose a smaller file.'
                ], 413);
            }

            return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
        }
    }
}
