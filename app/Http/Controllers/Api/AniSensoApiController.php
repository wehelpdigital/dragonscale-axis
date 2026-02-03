<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseAuditLog;
use App\Models\AsCourseChapter;
use App\Models\AsTopic;
use App\Models\AsTopicContent;
use App\Models\AsContentResource;
use App\Models\AsQuestionnaire;
use App\Models\AsQuestionnaireQuestion;
use App\Models\AsQuestionnaireAnswer;
use App\Models\AsContentComment;
use App\Models\AsCommentMention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AniSensoApiController extends Controller
{
    // ==================== CHAPTERS ====================

    /**
     * Get a single chapter
     */
    public function getChapter($id)
    {
        $chapter = AsCourseChapter::find($id);

        if (!$chapter) {
            return response()->json(['success' => false, 'message' => 'Chapter not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $chapter]);
    }

    /**
     * Store a new chapter
     */
    public function storeChapter(Request $request)
    {
        $request->validate([
            'courseId' => 'required|integer',
            'chapterTitle' => 'required|string|max:255'
        ]);

        $maxOrder = AsCourseChapter::where('asCoursesId', $request->courseId)
            ->where('deleteStatus', true)
            ->max('chapterOrder') ?? 0;

        $chapter = AsCourseChapter::create([
            'asCoursesId' => $request->courseId,
            'chapterTitle' => $request->chapterTitle,
            'chapterDescription' => $request->chapterDescription,
            'chapterCoverPhoto' => $request->chapterCoverPhoto ?? '',
            'chapterOrder' => $maxOrder + 1,
            'deleteStatus' => true
        ]);

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

        return response()->json([
            'success' => true,
            'message' => 'Chapter created successfully',
            'data' => $chapter
        ]);
    }

    /**
     * Update a chapter
     */
    public function updateChapter(Request $request, $id)
    {
        $request->validate([
            'chapterTitle' => 'required|string|max:255'
        ]);

        $chapter = AsCourseChapter::find($id);

        if (!$chapter) {
            return response()->json(['success' => false, 'message' => 'Chapter not found'], 404);
        }

        // Store previous values for audit
        $previousTitle = $chapter->chapterTitle;

        $chapter->update([
            'chapterTitle' => $request->chapterTitle,
            'chapterDescription' => $request->chapterDescription,
            'chapterCoverPhoto' => $request->chapterCoverPhoto ?? $chapter->chapterCoverPhoto
        ]);

        // Log audit
        AsCourseAuditLog::logAction(
            $chapter->asCoursesId,
            'chapter_updated',
            'chapter',
            $chapter->id,
            $chapter->chapterTitle,
            'chapterTitle',
            $previousTitle,
            $chapter->chapterTitle,
            "Chapter '{$chapter->chapterTitle}' updated"
        );

        return response()->json([
            'success' => true,
            'message' => 'Chapter updated successfully',
            'data' => $chapter
        ]);
    }

    /**
     * Delete a chapter (soft delete)
     */
    public function deleteChapter($id)
    {
        $chapter = AsCourseChapter::find($id);

        if (!$chapter) {
            return response()->json(['success' => false, 'message' => 'Chapter not found'], 404);
        }

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

        // Soft delete chapter
        $chapter->update(['deleteStatus' => false]);

        // Soft delete all topics in this chapter
        AsTopic::where('chapterId', $id)->update(['deleteStatus' => false]);

        // Soft delete all contents in topics of this chapter
        $topicIds = AsTopic::where('chapterId', $id)->pluck('id');
        AsTopicContent::whereIn('topicId', $topicIds)->update(['deleteStatus' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Chapter deleted successfully'
        ]);
    }

    /**
     * Update chapter order
     */
    public function updateChapterOrder(Request $request)
    {
        foreach ($request->items as $item) {
            AsCourseChapter::where('id', $item['id'])->update(['chapterOrder' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated']);
    }

    // ==================== TOPICS ====================

    /**
     * Get a single topic
     */
    public function getTopic($id)
    {
        $topic = AsTopic::find($id);

        if (!$topic) {
            return response()->json(['success' => false, 'message' => 'Topic not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $topic]);
    }

    /**
     * Store a new topic
     */
    public function storeTopic(Request $request)
    {
        $request->validate([
            'chapterId' => 'required|integer',
            'topicTitle' => 'required|string|max:255'
        ]);

        $maxOrder = AsTopic::where('chapterId', $request->chapterId)
            ->where('deleteStatus', true)
            ->max('topicsOrder') ?? 0;

        $topic = AsTopic::create([
            'chapterId' => $request->chapterId,
            'topicTitle' => $request->topicTitle,
            'topicDescription' => $request->topicDescription,
            'topicCoverPhoto' => $request->topicCoverPhoto,
            'topicContent' => $request->topicContent ?? '',
            'topicsOrder' => $maxOrder + 1,
            'deleteStatus' => true
        ]);

        // Log audit - get courseId from chapter
        $chapter = AsCourseChapter::find($request->chapterId);
        if ($chapter) {
            AsCourseAuditLog::logAction(
                $chapter->asCoursesId,
                'topic_created',
                'topic',
                $topic->id,
                $topic->topicTitle,
                null,
                null,
                null,
                "Topic '{$topic->topicTitle}' created"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Topic created successfully',
            'data' => $topic
        ]);
    }

    /**
     * Update a topic
     */
    public function updateTopic(Request $request, $id)
    {
        $request->validate([
            'topicTitle' => 'required|string|max:255'
        ]);

        $topic = AsTopic::with('chapter')->find($id);

        if (!$topic) {
            return response()->json(['success' => false, 'message' => 'Topic not found'], 404);
        }

        // Store previous values for audit
        $previousTitle = $topic->topicTitle;

        $topic->update([
            'topicTitle' => $request->topicTitle,
            'topicDescription' => $request->topicDescription,
            'topicCoverPhoto' => $request->topicCoverPhoto
        ]);

        // Log audit
        if ($topic->chapter) {
            AsCourseAuditLog::logAction(
                $topic->chapter->asCoursesId,
                'topic_updated',
                'topic',
                $topic->id,
                $topic->topicTitle,
                'topicTitle',
                $previousTitle,
                $topic->topicTitle,
                "Topic '{$topic->topicTitle}' updated"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Topic updated successfully',
            'data' => $topic
        ]);
    }

    /**
     * Delete a topic (soft delete)
     */
    public function deleteTopic($id)
    {
        $topic = AsTopic::with('chapter')->find($id);

        if (!$topic) {
            return response()->json(['success' => false, 'message' => 'Topic not found'], 404);
        }

        $topicTitle = $topic->topicTitle;

        // Log audit before deletion
        if ($topic->chapter) {
            AsCourseAuditLog::logAction(
                $topic->chapter->asCoursesId,
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

        // Soft delete topic
        $topic->update(['deleteStatus' => false]);

        // Soft delete all contents in this topic
        AsTopicContent::where('topicId', $id)->update(['deleteStatus' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Topic deleted successfully'
        ]);
    }

    /**
     * Update topic order
     */
    public function updateTopicOrder(Request $request)
    {
        foreach ($request->items as $item) {
            AsTopic::where('id', $item['id'])->update(['topicsOrder' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated']);
    }

    // ==================== CONTENTS ====================

    /**
     * Get a single content with its resources
     */
    public function getContent($id)
    {
        $content = AsTopicContent::with('resources')->find($id);

        if (!$content) {
            return response()->json(['success' => false, 'message' => 'Content not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $content]);
    }

    /**
     * Store a new content
     */
    public function storeContent(Request $request)
    {
        $request->validate([
            'topicId' => 'required|integer',
            'contentTitle' => 'required|string|max:255'
        ]);

        $maxOrder = AsTopicContent::where('topicId', $request->topicId)
            ->where('deleteStatus', true)
            ->max('contentOrder') ?? 0;

        // Handle content photos
        $contentPhotos = [];
        if ($request->contentPhotos) {
            $contentPhotos = json_decode($request->contentPhotos, true) ?? [];
        }

        // Upload new photos
        if ($request->hasFile('newPhotos')) {
            foreach ($request->file('newPhotos') as $photo) {
                $fileName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('images/anisenso/content-photos'), $fileName);
                $contentPhotos[] = '/images/anisenso/content-photos/' . $fileName;
            }
        }

        $content = AsTopicContent::create([
            'topicId' => $request->topicId,
            'contentTitle' => $request->contentTitle,
            'contentBody' => $request->contentBody,
            'youtubeUrl' => $request->youtubeUrl,
            'contentPhotos' => $contentPhotos,
            'takeaways' => $request->takeaways,
            'contentOrder' => $maxOrder + 1,
            'deleteStatus' => true
        ]);

        // Handle new resource uploads
        if ($request->hasFile('newResources')) {
            $resourceOrder = 1;
            foreach ($request->file('newResources') as $file) {
                $fileName = $file->getClientOriginalName();
                $uniqueName = time() . '_' . uniqid() . '_' . $fileName;
                $file->move(public_path('images/anisenso/resources'), $uniqueName);

                AsContentResource::create([
                    'contentId' => $content->id,
                    'fileName' => $fileName,
                    'fileUrl' => '/images/anisenso/resources/' . $uniqueName,
                    'resourceOrder' => $resourceOrder++,
                    'deleteStatus' => true
                ]);
            }
        }

        // Log audit - get course ID through topic->chapter chain
        $topic = AsTopic::with('chapter')->find($request->topicId);
        if ($topic && $topic->chapter) {
            AsCourseAuditLog::logAction(
                $topic->chapter->asCoursesId,
                'content_created',
                'content',
                $content->id,
                $content->contentTitle,
                null,
                null,
                null,
                "Content '{$content->contentTitle}' created"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Content created successfully',
            'data' => $content->load('resources')
        ]);
    }

    /**
     * Update a content
     */
    public function updateContent(Request $request, $id)
    {
        $request->validate([
            'contentTitle' => 'required|string|max:255'
        ]);

        $content = AsTopicContent::find($id);

        if (!$content) {
            return response()->json(['success' => false, 'message' => 'Content not found'], 404);
        }

        // Handle content photos
        $contentPhotos = [];
        if ($request->contentPhotos) {
            $contentPhotos = json_decode($request->contentPhotos, true) ?? [];
        }

        // Upload new photos
        if ($request->hasFile('newPhotos')) {
            foreach ($request->file('newPhotos') as $photo) {
                $fileName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                $photo->move(public_path('images/anisenso/content-photos'), $fileName);
                $contentPhotos[] = '/images/anisenso/content-photos/' . $fileName;
            }
        }

        $content->update([
            'contentTitle' => $request->contentTitle,
            'contentBody' => $request->contentBody,
            'youtubeUrl' => $request->youtubeUrl,
            'contentPhotos' => $contentPhotos,
            'takeaways' => $request->takeaways
        ]);

        // Handle existing resources - keep only the ones in the list
        if ($request->existingResources) {
            $keepIds = json_decode($request->existingResources, true) ?? [];
            AsContentResource::where('contentId', $id)
                ->whereNotIn('id', $keepIds)
                ->update(['deleteStatus' => false]);
        }

        // Handle new resource uploads
        if ($request->hasFile('newResources')) {
            $maxOrder = AsContentResource::where('contentId', $id)
                ->where('deleteStatus', true)
                ->max('resourceOrder') ?? 0;

            foreach ($request->file('newResources') as $file) {
                $fileName = $file->getClientOriginalName();
                $uniqueName = time() . '_' . uniqid() . '_' . $fileName;
                $file->move(public_path('images/anisenso/resources'), $uniqueName);

                AsContentResource::create([
                    'contentId' => $id,
                    'fileName' => $fileName,
                    'fileUrl' => '/images/anisenso/resources/' . $uniqueName,
                    'resourceOrder' => ++$maxOrder,
                    'deleteStatus' => true
                ]);
            }
        }

        // Log audit - get course ID through topic->chapter chain
        $topic = AsTopic::with('chapter')->find($content->topicId);
        if ($topic && $topic->chapter) {
            AsCourseAuditLog::logAction(
                $topic->chapter->asCoursesId,
                'content_updated',
                'content',
                $content->id,
                $content->contentTitle,
                null,
                null,
                null,
                "Content '{$content->contentTitle}' updated"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Content updated successfully',
            'data' => $content->load('resources')
        ]);
    }

    /**
     * Delete a content (soft delete)
     */
    public function deleteContent($id)
    {
        $content = AsTopicContent::find($id);

        if (!$content) {
            return response()->json(['success' => false, 'message' => 'Content not found'], 404);
        }

        $contentTitle = $content->contentTitle;

        // Log audit before deletion - get course ID through topic->chapter chain
        $topic = AsTopic::with('chapter')->find($content->topicId);
        if ($topic && $topic->chapter) {
            AsCourseAuditLog::logAction(
                $topic->chapter->asCoursesId,
                'content_deleted',
                'content',
                $content->id,
                $contentTitle,
                null,
                null,
                null,
                "Content '{$contentTitle}' deleted"
            );
        }

        // Soft delete content
        $content->update(['deleteStatus' => false]);

        // Soft delete all resources
        AsContentResource::where('contentId', $id)->update(['deleteStatus' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Content deleted successfully'
        ]);
    }

    /**
     * Update content order
     */
    public function updateContentOrder(Request $request)
    {
        foreach ($request->items as $item) {
            AsTopicContent::where('id', $item['id'])->update(['contentOrder' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated']);
    }

    // ==================== QUESTIONNAIRES ====================

    /**
     * Get a single questionnaire with questions and answers
     */
    public function getQuestionnaire($id)
    {
        $questionnaire = AsQuestionnaire::with(['questions.answers'])->find($id);

        if (!$questionnaire) {
            return response()->json(['success' => false, 'message' => 'Questionnaire not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $questionnaire]);
    }

    /**
     * Store a new questionnaire
     */
    public function storeQuestionnaire(Request $request)
    {
        $request->validate([
            'courseId' => 'required|integer',
            'title' => 'required|string|max:255'
        ]);

        // Get the max order from both chapters and questionnaires
        $maxChapterOrder = AsCourseChapter::where('asCoursesId', $request->courseId)
            ->where('deleteStatus', true)
            ->max('chapterOrder') ?? 0;

        $maxQuestionnaireOrder = AsQuestionnaire::where('asCoursesId', $request->courseId)
            ->where('deleteStatus', true)
            ->max('itemOrder') ?? 0;

        $maxOrder = max($maxChapterOrder, $maxQuestionnaireOrder);

        $questionnaire = AsQuestionnaire::create([
            'asCoursesId' => $request->courseId,
            'title' => $request->title,
            'description' => $request->description,
            'itemOrder' => $maxOrder + 1,
            'deleteStatus' => true
        ]);

        // Log audit
        AsCourseAuditLog::logAction(
            $request->courseId,
            'questionnaire_created',
            'questionnaire',
            $questionnaire->id,
            $questionnaire->title,
            null,
            null,
            null,
            "Questionnaire '{$questionnaire->title}' created"
        );

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire created successfully',
            'data' => $questionnaire
        ]);
    }

    /**
     * Update a questionnaire
     */
    public function updateQuestionnaire(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255'
        ]);

        $questionnaire = AsQuestionnaire::find($id);

        if (!$questionnaire) {
            return response()->json(['success' => false, 'message' => 'Questionnaire not found'], 404);
        }

        $questionnaire->update([
            'title' => $request->title,
            'description' => $request->description
        ]);

        // Log audit
        AsCourseAuditLog::logAction(
            $questionnaire->asCoursesId,
            'questionnaire_updated',
            'questionnaire',
            $questionnaire->id,
            $questionnaire->title,
            null,
            null,
            null,
            "Questionnaire '{$questionnaire->title}' updated"
        );

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire updated successfully',
            'data' => $questionnaire
        ]);
    }

    /**
     * Delete a questionnaire (soft delete)
     */
    public function deleteQuestionnaire($id)
    {
        $questionnaire = AsQuestionnaire::find($id);

        if (!$questionnaire) {
            return response()->json(['success' => false, 'message' => 'Questionnaire not found'], 404);
        }

        $questionnaireTitle = $questionnaire->title;
        $courseId = $questionnaire->asCoursesId;

        // Log audit before deletion
        AsCourseAuditLog::logAction(
            $courseId,
            'questionnaire_deleted',
            'questionnaire',
            $questionnaire->id,
            $questionnaireTitle,
            null,
            null,
            null,
            "Questionnaire '{$questionnaireTitle}' deleted"
        );

        // Soft delete questionnaire
        $questionnaire->update(['deleteStatus' => false]);

        // Soft delete all questions
        AsQuestionnaireQuestion::where('questionnaireId', $id)->update(['deleteStatus' => false]);

        // Soft delete all answers
        $questionIds = AsQuestionnaireQuestion::where('questionnaireId', $id)->pluck('id');
        AsQuestionnaireAnswer::whereIn('questionId', $questionIds)->update(['deleteStatus' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Questionnaire deleted successfully'
        ]);
    }

    /**
     * Update unified order for chapters and questionnaires
     */
    public function updateCourseItemsOrder(Request $request)
    {
        foreach ($request->items as $item) {
            if ($item['type'] === 'chapter') {
                AsCourseChapter::where('id', $item['id'])->update(['chapterOrder' => $item['order']]);
            } else if ($item['type'] === 'questionnaire') {
                AsQuestionnaire::where('id', $item['id'])->update(['itemOrder' => $item['order']]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Order updated']);
    }

    // ==================== QUESTIONS ====================

    /**
     * Get a single question with answers
     */
    public function getQuestion($id)
    {
        $question = AsQuestionnaireQuestion::with('answers')->find($id);

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $question]);
    }

    /**
     * Store a new question with answers
     */
    public function storeQuestion(Request $request)
    {
        $request->validate([
            'questionnaireId' => 'required|integer',
            'questionTitle' => 'required|string|max:255',
            'questionText' => 'required|string',
            'questionType' => 'required|in:single,multiple',
            'answers' => 'required|string' // JSON string from FormData
        ]);

        // Decode answers from JSON string
        $answers = json_decode($request->answers, true);
        if (!is_array($answers) || count($answers) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'At least 2 answer options are required'
            ], 422);
        }

        // Check for at least 1 correct answer
        $hasCorrect = collect($answers)->contains(fn($a) => !empty($a['isCorrect']));
        if (!$hasCorrect) {
            return response()->json([
                'success' => false,
                'message' => 'At least 1 correct answer is required'
            ], 422);
        }

        $maxOrder = AsQuestionnaireQuestion::where('questionnaireId', $request->questionnaireId)
            ->where('deleteStatus', true)
            ->max('questionOrder') ?? 0;

        // Handle photo upload
        $photoPath = null;
        if ($request->hasFile('questionPhoto')) {
            $photo = $request->file('questionPhoto');
            $fileName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images/anisenso/questions'), $fileName);
            $photoPath = '/images/anisenso/questions/' . $fileName;
        }

        $question = AsQuestionnaireQuestion::create([
            'questionnaireId' => $request->questionnaireId,
            'questionTitle' => $request->questionTitle,
            'questionText' => $request->questionText,
            'questionPhoto' => $photoPath,
            'questionVideo' => $request->questionVideo,
            'questionType' => $request->questionType,
            'questionOrder' => $maxOrder + 1,
            'deleteStatus' => true
        ]);

        // Create answers
        $answerOrder = 1;
        foreach ($answers as $answer) {
            AsQuestionnaireAnswer::create([
                'questionId' => $question->id,
                'answerText' => $answer['text'],
                'isCorrect' => $answer['isCorrect'] ?? false,
                'answerOrder' => $answerOrder++,
                'deleteStatus' => true
            ]);
        }

        // Log audit - get course ID through questionnaire
        $questionnaire = AsQuestionnaire::find($request->questionnaireId);
        if ($questionnaire) {
            AsCourseAuditLog::logAction(
                $questionnaire->asCoursesId,
                'question_created',
                'question',
                $question->id,
                $question->questionTitle,
                null,
                null,
                null,
                "Question '{$question->questionTitle}' created"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully',
            'data' => $question->load('answers')
        ]);
    }

    /**
     * Update a question with answers
     */
    public function updateQuestion(Request $request, $id)
    {
        $request->validate([
            'questionTitle' => 'required|string|max:255',
            'questionText' => 'required|string',
            'questionType' => 'required|in:single,multiple',
            'answers' => 'required|string' // JSON string from FormData
        ]);

        // Decode answers from JSON string
        $answers = json_decode($request->answers, true);
        if (!is_array($answers) || count($answers) < 2) {
            return response()->json([
                'success' => false,
                'message' => 'At least 2 answer options are required'
            ], 422);
        }

        // Check for at least 1 correct answer
        $hasCorrect = collect($answers)->contains(fn($a) => !empty($a['isCorrect']));
        if (!$hasCorrect) {
            return response()->json([
                'success' => false,
                'message' => 'At least 1 correct answer is required'
            ], 422);
        }

        $question = AsQuestionnaireQuestion::find($id);

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found'], 404);
        }

        // Handle photo upload
        $photoPath = $question->questionPhoto;
        if ($request->hasFile('questionPhoto')) {
            $photo = $request->file('questionPhoto');
            $fileName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images/anisenso/questions'), $fileName);
            $photoPath = '/images/anisenso/questions/' . $fileName;
        } elseif ($request->has('removePhoto') && $request->removePhoto) {
            $photoPath = null;
        }

        $question->update([
            'questionTitle' => $request->questionTitle,
            'questionText' => $request->questionText,
            'questionPhoto' => $photoPath,
            'questionVideo' => $request->questionVideo,
            'questionType' => $request->questionType
        ]);

        // Update answers - soft delete all existing, create new ones
        AsQuestionnaireAnswer::where('questionId', $id)->update(['deleteStatus' => false]);

        $answerOrder = 1;
        foreach ($answers as $answer) {
            AsQuestionnaireAnswer::create([
                'questionId' => $id,
                'answerText' => $answer['text'],
                'isCorrect' => $answer['isCorrect'] ?? false,
                'answerOrder' => $answerOrder++,
                'deleteStatus' => true
            ]);
        }

        // Log audit - get course ID through questionnaire
        $questionnaire = AsQuestionnaire::find($question->questionnaireId);
        if ($questionnaire) {
            AsCourseAuditLog::logAction(
                $questionnaire->asCoursesId,
                'question_updated',
                'question',
                $question->id,
                $question->questionTitle,
                null,
                null,
                null,
                "Question '{$question->questionTitle}' updated"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully',
            'data' => $question->load('answers')
        ]);
    }

    /**
     * Delete a question (soft delete)
     */
    public function deleteQuestion($id)
    {
        $question = AsQuestionnaireQuestion::find($id);

        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Question not found'], 404);
        }

        $questionTitle = $question->questionTitle;

        // Log audit before deletion - get course ID through questionnaire
        $questionnaire = AsQuestionnaire::find($question->questionnaireId);
        if ($questionnaire) {
            AsCourseAuditLog::logAction(
                $questionnaire->asCoursesId,
                'question_deleted',
                'question',
                $question->id,
                $questionTitle,
                null,
                null,
                null,
                "Question '{$questionTitle}' deleted"
            );
        }

        // Soft delete question
        $question->update(['deleteStatus' => false]);

        // Soft delete all answers
        AsQuestionnaireAnswer::where('questionId', $id)->update(['deleteStatus' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully'
        ]);
    }

    /**
     * Update question order
     */
    public function updateQuestionOrder(Request $request)
    {
        foreach ($request->items as $item) {
            AsQuestionnaireQuestion::where('id', $item['id'])->update(['questionOrder' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated']);
    }

    // ==================== COMMENTS ====================

    /**
     * Get all comments for a course with filters and pagination
     */
    public function getComments(Request $request, $courseId)
    {
        $query = AsContentComment::where('asCoursesId', $courseId)
            ->where('deleteStatus', true)
            ->rootComments()
            ->with(['content.topic.chapter']);

        // Filter by content
        if ($request->filled('contentId')) {
            $query->where('contentId', $request->contentId);
        }

        // Filter by answered status
        if ($request->filled('status')) {
            if ($request->status === 'unanswered') {
                $query->where('isAnswered', false);
            } elseif ($request->status === 'answered') {
                $query->where('isAnswered', true);
            }
        }

        // Search in comment text or author name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('commentText', 'like', "%{$search}%")
                  ->orWhere('authorName', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sortBy', 'created_at');
        $sortOrder = $request->get('sortOrder', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('perPage', 10);
        $comments = $query->paginate($perPage);

        // Load nested replies recursively for paginated results
        $this->loadRepliesRecursively($comments->getCollection());

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Get comments for a specific content
     */
    public function getContentComments($contentId)
    {
        // Get root comments for this content
        $comments = AsContentComment::where('contentId', $contentId)
            ->where('deleteStatus', true)
            ->rootComments()
            ->orderBy('isPinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Load nested replies recursively for each comment
        $this->loadRepliesRecursively($comments);

        return response()->json([
            'success' => true,
            'data' => $comments
        ]);
    }

    /**
     * Recursively load all replies for a collection of comments
     */
    private function loadRepliesRecursively($comments)
    {
        foreach ($comments as $comment) {
            $replies = AsContentComment::where('parentCommentId', $comment->id)
                ->where('deleteStatus', true)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($replies->count() > 0) {
                // Recursively load nested replies
                $this->loadRepliesRecursively($replies);
            }

            // Set the replies as a relationship attribute
            $comment->setRelation('all_replies', $replies);
        }
    }

    /**
     * Get unanswered comments count for a course
     */
    public function getUnansweredCount($courseId)
    {
        // Count root comments from students that have no replies (unreplied comments)
        // Only count comments that have a contentId (not general course comments with NULL contentId)
        // This ensures the total matches the sum of content-specific bubble counts
        $count = AsContentComment::where('asCoursesId', $courseId)
            ->where('deleteStatus', true)
            ->where('authorType', '!=', 'admin') // Only count non-admin comments
            ->whereNotNull('contentId') // Exclude general course comments
            ->rootComments()
            ->whereDoesntHave('replies', function ($query) {
                $query->where('deleteStatus', true);
            })
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Store a new comment
     */
    public function storeComment(Request $request)
    {
        $request->validate([
            'courseId' => 'required|integer',
            'commentText' => 'required|string|max:5000',
            'authorName' => 'required|string|max:100'
        ]);

        $comment = AsContentComment::create([
            'asCoursesId' => $request->courseId,
            'contentId' => $request->contentId,
            'parentCommentId' => $request->parentCommentId,
            'authorType' => Auth::check() ? 'admin' : ($request->authorType ?? 'guest'),
            'authorId' => Auth::id(),
            'authorName' => $request->authorName,
            'authorEmail' => $request->authorEmail,
            'authorAvatar' => $request->authorAvatar,
            'commentText' => $request->commentText,
            'isAnswered' => false,
            'isApproved' => true,
            'deleteStatus' => true
        ]);

        // If this is an admin reply, mark the parent as answered
        if ($request->parentCommentId && Auth::check()) {
            AsContentComment::where('id', $request->parentCommentId)
                ->update(['isAnswered' => true]);
        }

        // Log audit
        AsCourseAuditLog::logAction(
            $request->courseId,
            'comment_created',
            'comment',
            $comment->id,
            substr($comment->commentText, 0, 50) . '...',
            null,
            null,
            null,
            "Comment added by {$comment->authorName}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('replies')
        ]);
    }

    /**
     * Reply to a comment (admin)
     */
    public function replyToComment(Request $request, $id)
    {
        try {
            $request->validate([
                'commentText' => 'required|string|max:5000'
            ]);

            $parentComment = AsContentComment::find($id);

            if (!$parentComment) {
                return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
            }

            // Add @mention tag to the reply if replying to someone else
            $replyText = $request->commentText;
            $taggedUser = null;

            // If replying to a student's comment, add the @tag with full name in brackets
            if ($parentComment->authorType === 'student' && $parentComment->authorName) {
                $taggedUser = $parentComment;
                // Use bracket format @[Full Name] for unambiguous multi-word names
                $tagPattern = '@[' . $parentComment->authorName . ']';
                if (strpos($replyText, $tagPattern) !== 0) {
                    $replyText = $tagPattern . ' ' . $replyText;
                }
            }

            // Create the reply
            $reply = AsContentComment::create([
                'asCoursesId' => $parentComment->asCoursesId,
                'contentId' => $parentComment->contentId,
                'parentCommentId' => $id,
                'authorType' => 'admin',
                'authorId' => Auth::id(),
                'authorName' => Auth::user()->name ?? 'Admin',
                'authorEmail' => Auth::user()->email ?? null,
                'commentText' => $replyText,
                'isAnswered' => true,
                'isApproved' => true,
                'deleteStatus' => true
            ]);

            // Verify the reply was saved
            if (!$reply || !$reply->id) {
                Log::error('Reply creation failed - no ID returned', [
                    'parentId' => $id,
                    'text' => $replyText
                ]);
                return response()->json(['success' => false, 'message' => 'Failed to save reply'], 500);
            }

            Log::info('Reply created successfully', [
                'replyId' => $reply->id,
                'parentId' => $id,
                'contentId' => $parentComment->contentId
            ]);

            // Create mention record for notification
            if ($taggedUser) {
                AsCommentMention::create([
                    'commentId' => $reply->id,
                    'asCoursesId' => $parentComment->asCoursesId,
                    'mentionType' => 'reply',
                    'mentionedUserId' => $taggedUser->authorId,
                    'mentionedAuthorName' => $taggedUser->authorName,
                    'mentionedAuthorEmail' => $taggedUser->authorEmail,
                    'mentionerUserId' => Auth::id(),
                    'mentionerAuthorName' => Auth::user()->name ?? 'Admin',
                    'mentionerType' => 'admin',
                    'isRead' => false,
                    'isNotified' => false,
                    'commentPreview' => substr($replyText, 0, 200),
                    'contextType' => $parentComment->contentId ? 'content' : 'questionnaire',
                    'contextId' => $parentComment->contentId,
                    'delete_status' => 'active'
                ]);
            }

            // Mark the parent comment as answered
            $parentComment->update(['isAnswered' => true]);

            // Log audit
            AsCourseAuditLog::logAction(
                $parentComment->asCoursesId,
                'comment_replied',
                'comment',
                $reply->id,
                substr($reply->commentText, 0, 50) . '...',
                null,
                null,
                null,
                "Admin replied to comment by {$parentComment->authorName}"
            );

            return response()->json([
                'success' => true,
                'message' => 'Reply added successfully',
                'data' => $reply
            ]);
        } catch (\Exception $e) {
            Log::error('Reply creation exception', [
                'parentId' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating reply: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, $id)
    {
        $comment = AsContentComment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        $updateData = [];

        if ($request->has('commentText')) {
            $updateData['commentText'] = $request->commentText;
        }

        if ($request->has('isAnswered')) {
            // Convert string 'true'/'false' to boolean
            $updateData['isAnswered'] = filter_var($request->isAnswered, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('isPinned')) {
            $updateData['isPinned'] = filter_var($request->isPinned, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('isApproved')) {
            $updateData['isApproved'] = filter_var($request->isApproved, FILTER_VALIDATE_BOOLEAN);
        }

        if (!empty($updateData)) {
            $comment->update($updateData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    }

    /**
     * Delete a comment (soft delete)
     */
    public function deleteComment($id)
    {
        $comment = AsContentComment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        $courseId = $comment->asCoursesId;
        $authorName = $comment->authorName;

        // Log audit before deletion
        AsCourseAuditLog::logAction(
            $courseId,
            'comment_deleted',
            'comment',
            $comment->id,
            substr($comment->commentText, 0, 50) . '...',
            null,
            null,
            null,
            "Comment by {$authorName} deleted"
        );

        // Recursively soft delete the comment and all nested replies
        $this->softDeleteCommentAndReplies($id);

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    /**
     * Recursively soft delete a comment and all its nested replies
     */
    private function softDeleteCommentAndReplies($commentId)
    {
        // Soft delete the comment itself
        AsContentComment::where('id', $commentId)->update(['deleteStatus' => false]);

        // Get all direct replies
        $replies = AsContentComment::where('parentCommentId', $commentId)->get();

        // Recursively delete each reply and its children
        foreach ($replies as $reply) {
            $this->softDeleteCommentAndReplies($reply->id);
        }
    }

    /**
     * Toggle reaction on a comment (like or heart)
     * Supports both adding and removing reactions
     */
    public function addReaction(Request $request, $id)
    {
        $comment = AsContentComment::find($id);

        if (!$comment) {
            return response()->json(['success' => false, 'message' => 'Comment not found'], 404);
        }

        $type = $request->input('type', 'like'); // 'like' or 'heart'
        $action = $request->input('action', 'add'); // 'add' or 'remove'

        if ($type === 'like') {
            if ($action === 'remove') {
                $comment->decrement('likesCount');
                // Ensure count doesn't go below 0
                if ($comment->likesCount < 0) {
                    $comment->update(['likesCount' => 0]);
                }
            } else {
                $comment->increment('likesCount');
            }
        } elseif ($type === 'heart') {
            if ($action === 'remove') {
                $comment->decrement('heartsCount');
                // Ensure count doesn't go below 0
                if ($comment->heartsCount < 0) {
                    $comment->update(['heartsCount' => 0]);
                }
            } else {
                $comment->increment('heartsCount');
            }
        }

        $comment->refresh();

        return response()->json([
            'success' => true,
            'message' => $action === 'remove' ? 'Reaction removed' : 'Reaction added',
            'action' => $action,
            'data' => [
                'likesCount' => $comment->likesCount,
                'heartsCount' => $comment->heartsCount
            ]
        ]);
    }

    /**
     * Get content list for a course (for filter dropdown)
     */
    public function getCourseContents($courseId)
    {
        $chapters = AsCourseChapter::where('asCoursesId', $courseId)
            ->where('deleteStatus', true)
            ->orderBy('chapterOrder')
            ->with(['topics' => function($q) {
                $q->where('deleteStatus', true)
                  ->orderBy('topicsOrder')
                  ->with(['contents' => function($q2) {
                      $q2->where('deleteStatus', true)
                        ->orderBy('contentOrder')
                        ->select('id', 'topicId', 'contentTitle');
                  }]);
            }])
            ->get(['id', 'chapterTitle', 'chapterOrder']);

        return response()->json([
            'success' => true,
            'data' => $chapters
        ]);
    }

    /**
     * Search GIFs using Tenor API
     */
    public function searchGifs(Request $request)
    {
        $query = $request->get('q', 'funny');
        $limit = $request->get('limit', 20);

        // Using Tenor API (free tier)
        $apiKey = 'AIzaSyAyimkuYQYF_FXVALexPuGQctUWRURdCYQ'; // Google/Tenor API key

        $url = "https://tenor.googleapis.com/v2/search?" . http_build_query([
            'q' => $query,
            'key' => $apiKey,
            'limit' => $limit,
            'media_filter' => 'gif,tinygif'
        ]);

        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            $gifs = [];
            if (isset($data['results'])) {
                foreach ($data['results'] as $result) {
                    $gifs[] = [
                        'id' => $result['id'],
                        'url' => $result['media_formats']['gif']['url'] ?? $result['media_formats']['tinygif']['url'],
                        'preview' => $result['media_formats']['tinygif']['url'] ?? $result['media_formats']['gif']['url'],
                        'width' => $result['media_formats']['gif']['dims'][0] ?? 200,
                        'height' => $result['media_formats']['gif']['dims'][1] ?? 200
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $gifs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GIFs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get trending GIFs
     */
    public function getTrendingGifs(Request $request)
    {
        $limit = $request->get('limit', 20);

        $apiKey = 'AIzaSyAyimkuYQYF_FXVALexPuGQctUWRURdCYQ';

        $url = "https://tenor.googleapis.com/v2/featured?" . http_build_query([
            'key' => $apiKey,
            'limit' => $limit,
            'media_filter' => 'gif,tinygif'
        ]);

        try {
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            $gifs = [];
            if (isset($data['results'])) {
                foreach ($data['results'] as $result) {
                    $gifs[] = [
                        'id' => $result['id'],
                        'url' => $result['media_formats']['gif']['url'] ?? $result['media_formats']['tinygif']['url'],
                        'preview' => $result['media_formats']['tinygif']['url'] ?? $result['media_formats']['gif']['url'],
                        'width' => $result['media_formats']['gif']['dims'][0] ?? 200,
                        'height' => $result['media_formats']['gif']['dims'][1] ?? 200
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $gifs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GIFs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
