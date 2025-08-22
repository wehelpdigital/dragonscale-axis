<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCourse;
use App\Models\AsCourseChapter;
use Illuminate\Http\Request;

class AniSensoCourseController extends Controller
{
    /**
     * Display the Ani-Senso Course page
     */
    public function index()
    {
        $courses = AsCourse::where('isActive', true)
                          ->where('deleteStatus', true)
                          ->get();
        return view('aniSensoAdmin.courses', compact('courses'));
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
            'coursePrice' => 'required|numeric|min:0',
            'courseImage' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120'
        ]);

        $course = new AsCourse();
        $course->courseName = $request->courseName;
        $course->courseSmallDescription = $request->courseSmallDescription;
        $course->courseBigDescription = $request->courseBigDescription;
        $course->coursePrice = $request->coursePrice;
        $course->isActive = true;
        $course->deleteStatus = true;

        if ($request->hasFile('courseImage')) {
            $image = $request->file('courseImage');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/courses'), $imageName);
            $course->courseImage = 'images/courses/' . $imageName;
        }

        $course->save();

        return redirect()->route('anisenso-courses')->with('success', 'Course saved successfully!');
    }

    /**
     * Update the specified course
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'courseName' => 'required|string|max:255',
            'coursePrice' => 'required|numeric|min:0',
            'courseImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'courseDescription' => 'nullable|string'
        ]);

        $course = AsCourse::findOrFail($id);
        $course->courseName = $request->courseName;
        $course->coursePrice = $request->coursePrice;
        $course->courseDescription = $request->courseDescription;

        if ($request->hasFile('courseImage')) {
            // Delete old image if exists
            if ($course->courseImage && file_exists(public_path($course->courseImage))) {
                unlink(public_path($course->courseImage));
            }

            $image = $request->file('courseImage');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/courses'), $imageName);
            $course->courseImage = 'images/courses/' . $imageName;
        }

        $course->save();

        return redirect()->route('anisenso-courses')->with('success', 'Course updated successfully!');
    }

    /**
     * Show the form for editing the specified course
     */
    public function edit($id)
    {
        $course = AsCourse::findOrFail($id);
        return response()->json($course);
    }

    /**
     * Remove the specified course
     */
    public function destroy($id)
    {
        $course = AsCourse::findOrFail($id);

        // Soft delete by setting deleteStatus to false
        $course->deleteStatus = false;
        $course->save();

        return redirect()->route('anisenso-courses')->with('success', 'Course deleted successfully!');
    }

    /**
     * Display the course contents page
     */
    public function contents(Request $request)
    {
        $courseId = $request->query('id');
        $course = AsCourse::findOrFail($courseId);

        // Get chapters for this course, ordered by chapterOrder
        $chapters = AsCourseChapter::where('asCoursesId', $courseId)
                                  ->where('deleteStatus', 1)
                                  ->orderBy('chapterOrder', 'ASC')
                                  ->get();

        return view('aniSensoAdmin.courses-contents', compact('course', 'chapters'));
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

        return redirect()->route('anisenso-courses.contents', ['id' => $request->courseId])
                        ->with('success', 'Chapter added successfully!');
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

        foreach ($request->chapters as $chapterData) {
            AsCourseChapter::where('id', $chapterData['id'])
                          ->update(['chapterOrder' => $chapterData['order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Chapter order updated successfully!'
        ]);
    }
}
