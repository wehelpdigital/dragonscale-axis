<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsTestimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TestimonialsController extends Controller
{
    public function index()
    {
        /* Paginated, because this list only ever grows.
         *
         * The order matters more than usual here — testimonialOrder is what
         * decides which quotes appear on the website — so the page size is
         * generous enough that reordering rarely spans two pages. */
        $testimonials = AsTestimonial::active()
            ->orderBy('testimonialOrder')
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

        return view('aniSensoAdmin.testimonials.index', compact('testimonials'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'location' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'testimonial' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = [
            'usersId' => Auth::id(),
            'name' => $request->name,
            'location' => $request->location,
            'role' => $request->role,
            'testimonial' => $request->testimonial,
            'rating' => $request->rating,
            'isActive' => $request->boolean('isActive', true),
            'deleteStatus' => 'active',
        ];

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/testimonials'), $filename);
            $data['image'] = 'images/testimonials/' . $filename;
        }

        AsTestimonial::create($data);

        return response()->json(['success' => true, 'message' => 'Testimonial added successfully.']);
    }

    public function update(Request $request, $id)
    {
        $testimonial = AsTestimonial::active()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'location' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:100',
            'testimonial' => 'required|string|max:2000',
            'rating' => 'required|integer|min:1|max:5',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $testimonial->name = $request->name;
        $testimonial->location = $request->location;
        $testimonial->role = $request->role;
        $testimonial->testimonial = $request->testimonial;
        $testimonial->rating = $request->rating;
        $testimonial->isActive = $request->boolean('isActive', true);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/testimonials'), $filename);
            $testimonial->image = 'images/testimonials/' . $filename;
        }

        $testimonial->save();

        // Sync to homepage items that reference this testimonial
        $this->syncToHomepageItems($testimonial);

        return response()->json(['success' => true, 'message' => 'Testimonial updated successfully.']);
    }

    public function destroy($id)
    {
        $testimonial = AsTestimonial::active()->findOrFail($id);
        $testimonial->update(['deleteStatus' => 'deleted']);

        // Also remove from homepage items
        $this->removeFromHomepageItems($testimonial->id);

        return response()->json(['success' => true, 'message' => 'Testimonial deleted successfully.']);
    }

    public function listForPicker()
    {
        $testimonials = AsTestimonial::active()
            ->enabled()
            ->orderBy('testimonialOrder')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'name' => $t->name,
                    'location' => $t->location,
                    'role' => $t->role,
                    'testimonial' => $t->testimonial,
                    'rating' => $t->rating,
                    'image' => $t->image ? asset($t->image) : null,
                ];
            });

        return response()->json(['success' => true, 'testimonials' => $testimonials]);
    }

    public function addToHomepage(Request $request)
    {
        $testimonial = AsTestimonial::active()->findOrFail($request->testimonial_id);

        $sectionId = DB::table('as_homepage_sections')
            ->where('sectionKey', 'testimonials')
            ->value('id');

        if (!$sectionId) {
            return response()->json(['success' => false, 'message' => 'Testimonials section not found'], 404);
        }

        // Check if already added
        $existing = DB::table('as_homepage_items')
            ->where('sectionId', $sectionId)
            ->where('deleteStatus', 'active')
            ->get();

        foreach ($existing as $item) {
            $extra = json_decode($item->extraData, true) ?? [];
            if (isset($extra['testimonialId']) && $extra['testimonialId'] == $testimonial->id) {
                return response()->json(['success' => false, 'message' => 'Already added to homepage'], 422);
            }
        }

        $maxOrder = DB::table('as_homepage_items')
            ->where('sectionId', $sectionId)
            ->max('itemOrder') ?? 0;

        $itemId = DB::table('as_homepage_items')->insertGetId([
            'sectionId' => $sectionId,
            'itemType' => 'testimonial',
            'title' => $testimonial->name,
            'subtitle' => $testimonial->location,
            'description' => $testimonial->testimonial,
            'image' => $testimonial->image,
            'extraData' => json_encode(['rating' => $testimonial->rating, 'testimonialId' => $testimonial->id]),
            'itemOrder' => $maxOrder + 1,
            'isActive' => true,
            'deleteStatus' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Testimonial added to homepage.', 'itemId' => $itemId]);
    }

    public function toggleActive($id)
    {
        $testimonial = AsTestimonial::active()->findOrFail($id);
        $testimonial->update(['isActive' => !$testimonial->isActive]);

        return response()->json([
            'success' => true,
            'message' => $testimonial->isActive ? 'Testimonial enabled.' : 'Testimonial disabled.',
            'isActive' => $testimonial->isActive,
        ]);
    }

    /**
     * Sync testimonial data to matching homepage items.
     */
    private function syncToHomepageItems(AsTestimonial $testimonial)
    {
        $testimonialSection = DB::table('as_homepage_sections')
            ->where('sectionKey', 'testimonials')
            ->value('id');

        if (!$testimonialSection) return;

        // Find homepage items that reference this testimonial by ID in extraData or by name match
        $items = DB::table('as_homepage_items')
            ->where('sectionId', $testimonialSection)
            ->where('deleteStatus', 'active')
            ->get();

        foreach ($items as $item) {
            $extra = json_decode($item->extraData, true) ?? [];
            $matchById = isset($extra['testimonialId']) && $extra['testimonialId'] == $testimonial->id;
            $matchByName = $item->title === $testimonial->getOriginal('name') || $item->title === $testimonial->name;

            if ($matchById || $matchByName) {
                $extra['rating'] = $testimonial->rating;
                $extra['testimonialId'] = $testimonial->id;

                DB::table('as_homepage_items')
                    ->where('id', $item->id)
                    ->update([
                        'title' => $testimonial->name,
                        'subtitle' => $testimonial->location,
                        'description' => $testimonial->testimonial,
                        'image' => $testimonial->image,
                        'extraData' => json_encode($extra),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Remove homepage items that reference a deleted testimonial.
     */
    private function removeFromHomepageItems($testimonialId)
    {
        $testimonialSection = DB::table('as_homepage_sections')
            ->where('sectionKey', 'testimonials')
            ->value('id');

        if (!$testimonialSection) return;

        $items = DB::table('as_homepage_items')
            ->where('sectionId', $testimonialSection)
            ->where('deleteStatus', 'active')
            ->get();

        foreach ($items as $item) {
            $extra = json_decode($item->extraData, true) ?? [];
            if (isset($extra['testimonialId']) && $extra['testimonialId'] == $testimonialId) {
                DB::table('as_homepage_items')
                    ->where('id', $item->id)
                    ->update(['deleteStatus' => 'deleted', 'updated_at' => now()]);
            }
        }
    }
}
