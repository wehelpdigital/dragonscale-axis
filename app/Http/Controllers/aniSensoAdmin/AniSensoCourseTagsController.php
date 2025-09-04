<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AniSensoCourseTagsController extends Controller
{
    /**
     * Display the course access tags page
     */
    public function index(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);

        // Get access tags for this course from axis_tags table
        $tags = \DB::table('axis_tags')
            ->where('tagType', 'course')
            ->where('targetId', $courseId)
            ->where('deleteStatus', 1)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('aniSensoAdmin.course-tags', compact('course', 'tags'));
    }

    /**
     * Show the form for creating a new access tag
     */
    public function create(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);

        return view('aniSensoAdmin.course-tags-add', compact('course'));
    }

    /**
     * Store a newly created access tag
     */
    public function store(Request $request)
    {
        $request->validate([
            'courseId' => 'required|exists:as_courses,id',
            'tagName' => 'required|string|max:255',
            'expirationLength' => 'required|integer|min:1'
        ]);

        try {
            // Insert into axis_tags table
            \DB::table('axis_tags')->insert([
                'tagName' => $request->tagName,
                'tagType' => 'course',
                'targetId' => $request->courseId,
                'expirationLength' => $request->expirationLength,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return redirect()->route('anisenso-courses-tags', ['id' => $request->courseId])
                            ->with('success', 'Access tag created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Failed to create access tag: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified access tag
     */
    public function edit(Request $request)
    {
        $tagId = $request->query('id');

        // Get the tag data
        $tag = \DB::table('axis_tags')->where('id', $tagId)->first();

        if (!$tag) {
            abort(404, 'Access tag not found');
        }

        // Get the course data
        $course = AsCourse::findOrFail($tag->targetId);

        return view('aniSensoAdmin.course-tags-edit', compact('tag', 'course'));
    }

    /**
     * Update the specified access tag
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'courseId' => 'required|exists:as_courses,id',
            'tagName' => 'required|string|max:255',
            'expirationLength' => 'required|integer|min:1'
        ]);

        try {
            // Update the access tag in axis_tags table
            \DB::table('axis_tags')
                ->where('id', $id)
                ->update([
                    'tagName' => $request->tagName,
                    'expirationLength' => $request->expirationLength,
                    'updated_at' => now()
                ]);

            return redirect()->route('anisenso-courses-tags', ['id' => $request->courseId])
                            ->with('success', 'Access tag updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['error' => 'Failed to update access tag: ' . $e->getMessage()]);
        }
    }

    /**
     * Soft delete the specified access tag
     */
    public function destroy($id)
    {
        try {
            // Get the tag to find the course ID for redirect
            $tag = \DB::table('axis_tags')->where('id', $id)->first();

            if (!$tag) {
                return response()->json(['success' => false, 'message' => 'Access tag not found'], 404);
            }

            // Update deleteStatus to 0 (soft delete)
            \DB::table('axis_tags')
                ->where('id', $id)
                ->update([
                    'deleteStatus' => 0,
                    'updated_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Access tag deleted successfully!',
                'courseId' => $tag->targetId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete access tag: ' . $e->getMessage()
            ], 500);
        }
    }
}
