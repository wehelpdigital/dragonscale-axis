<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsHomepageSection;
use App\Models\AsHomepageItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AniSensoHomepageSettingsController extends Controller
{
    /**
     * Display the homepage settings with tabs
     */
    public function index(Request $request)
    {
        $sections = AsHomepageSection::orderBy('sectionOrder')->get();
        $activeTab = $request->get('tab', 'hero');

        return view('aniSensoAdmin.homepage-settings.index', compact('sections', 'activeTab'));
    }

    /**
     * Get section data for AJAX
     */
    public function getSectionData($sectionKey)
    {
        $section = AsHomepageSection::with(['items' => function ($query) {
            $query->active()->orderBy('itemOrder');
        }])->where('sectionKey', $sectionKey)->first();

        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Section not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $section]);
    }

    /**
     * Update section settings
     */
    public function updateSection(Request $request, $sectionKey)
    {
        $section = AsHomepageSection::where('sectionKey', $sectionKey)->first();

        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Section not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'sectionName' => 'sometimes|string|max:100',
            'isEnabled' => 'sometimes|boolean',
            'settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Handle settings merge
        if ($request->has('settings')) {
            $currentSettings = $section->settings ?? [];
            $newSettings = array_merge($currentSettings, $request->settings);
            $section->settings = $newSettings;
        }

        if ($request->has('sectionName')) {
            $section->sectionName = $request->sectionName;
        }

        if ($request->has('isEnabled')) {
            $section->isEnabled = $request->isEnabled;
        }

        $section->save();

        return response()->json(['success' => true, 'message' => 'Section updated successfully', 'data' => $section]);
    }

    /**
     * Upload image for section settings
     */
    public function uploadSectionImage(Request $request, $sectionKey)
    {
        $section = AsHomepageSection::where('sectionKey', $sectionKey)->first();

        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Section not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'settingKey' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('image');
        $filename = 'homepage_' . $sectionKey . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = 'images/anisenso/homepage';

        // Create directory if not exists
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }

        $file->move(public_path($path), $filename);
        $imageUrl = '/' . $path . '/' . $filename;

        // Update section settings
        $settings = $section->settings ?? [];
        $settings[$request->settingKey] = $imageUrl;
        $section->settings = $settings;
        $section->save();

        return response()->json(['success' => true, 'message' => 'Image uploaded successfully', 'imageUrl' => $imageUrl]);
    }

    /**
     * Store new item for a section
     */
    public function storeItem(Request $request, $sectionKey)
    {
        $section = AsHomepageSection::where('sectionKey', $sectionKey)->first();

        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Section not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'itemType' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:500',
            'iconFile' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:2048',
            'linkUrl' => 'nullable|string|max:500',
            'linkText' => 'nullable|string|max:100',
            'extraData' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Handle icon file upload
        $iconPath = $request->icon;
        if ($request->hasFile('iconFile')) {
            $file = $request->file('iconFile');
            $filename = 'icon_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = 'images/anisenso/homepage/icons';

            if (!file_exists(public_path($path))) {
                mkdir(public_path($path), 0755, true);
            }

            $file->move(public_path($path), $filename);
            $iconPath = '/' . $path . '/' . $filename;
        }

        // Get max order
        $maxOrder = AsHomepageItem::where('sectionId', $section->id)->max('itemOrder') ?? 0;

        $item = AsHomepageItem::create([
            'sectionId' => $section->id,
            'itemType' => $request->itemType,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'icon' => $iconPath,
            'linkUrl' => $request->linkUrl,
            'linkText' => $request->linkText,
            'extraData' => $request->extraData,
            'itemOrder' => $maxOrder + 1,
            'isActive' => true,
            'deleteStatus' => 'active',
        ]);

        return response()->json(['success' => true, 'message' => 'Item created successfully', 'data' => $item]);
    }

    /**
     * Update an item
     */
    public function updateItem(Request $request, $itemId)
    {
        $item = AsHomepageItem::active()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'linkUrl' => 'nullable|string|max:500',
            'linkText' => 'nullable|string|max:100',
            'extraData' => 'nullable|array',
            'isActive' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $item->fill($request->only([
            'title', 'subtitle', 'description', 'icon', 'linkUrl', 'linkText', 'isActive'
        ]));

        // Merge extra data
        if ($request->has('extraData')) {
            $currentExtra = $item->extraData ?? [];
            $item->extraData = array_merge($currentExtra, $request->extraData);
        }

        $item->save();

        return response()->json(['success' => true, 'message' => 'Item updated successfully', 'data' => $item]);
    }

    /**
     * Upload image for an item
     */
    public function uploadItemImage(Request $request, $itemId)
    {
        $item = AsHomepageItem::active()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'field' => 'required|in:image,image2,icon',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $file = $request->file('image');
        $filename = 'homepage_item_' . $itemId . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = 'images/anisenso/homepage';

        // Create directory if not exists
        if (!file_exists(public_path($path))) {
            mkdir(public_path($path), 0755, true);
        }

        $file->move(public_path($path), $filename);
        $imageUrl = '/' . $path . '/' . $filename;

        $field = $request->field;
        $item->$field = $imageUrl;
        $item->save();

        return response()->json(['success' => true, 'message' => 'Image uploaded successfully', 'imageUrl' => $imageUrl]);
    }

    /**
     * Update a single extra data key for an item
     */
    public function updateItemExtra(Request $request, $itemId)
    {
        $item = AsHomepageItem::active()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:50',
            'value' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Get current extra data or initialize empty array
        $extraData = $item->extraData ?? [];

        // Update the specific key
        $extraData[$request->key] = $request->value;

        // Save back to item
        $item->extraData = $extraData;
        $item->save();

        return response()->json(['success' => true, 'message' => 'Extra data updated successfully']);
    }

    /**
     * Reorder items
     */
    public function reorderItems(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.id' => 'required|integer',
            'items.*.order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        foreach ($request->items as $itemData) {
            AsHomepageItem::where('id', $itemData['id'])
                ->update(['itemOrder' => $itemData['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Items reordered successfully']);
    }

    /**
     * Delete an item (soft delete)
     */
    public function deleteItem($itemId)
    {
        $item = AsHomepageItem::active()->find($itemId);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Item not found'], 404);
        }

        $item->update(['deleteStatus' => 'deleted']);

        return response()->json(['success' => true, 'message' => 'Item deleted successfully']);
    }

    /**
     * Reorder sections
     */
    public function reorderSections(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sections' => 'required|array',
            'sections.*.id' => 'required|integer',
            'sections.*.order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        foreach ($request->sections as $sectionData) {
            AsHomepageSection::where('id', $sectionData['id'])
                ->update(['sectionOrder' => $sectionData['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Sections reordered successfully']);
    }

    /**
     * Toggle section enabled status
     */
    public function toggleSection($sectionKey)
    {
        $section = AsHomepageSection::where('sectionKey', $sectionKey)->first();

        if (!$section) {
            return response()->json(['success' => false, 'message' => 'Section not found'], 404);
        }

        $section->isEnabled = !$section->isEnabled;
        $section->save();

        return response()->json([
            'success' => true,
            'message' => 'Section ' . ($section->isEnabled ? 'enabled' : 'disabled') . ' successfully',
            'isEnabled' => $section->isEnabled
        ]);
    }

    /**
     * Upload slide image for hero section
     */
    public function uploadSlide(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'sectionId' => 'required|integer',
                'title' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $section = AsHomepageSection::find($request->sectionId);
            if (!$section) {
                return response()->json(['success' => false, 'message' => 'Section not found'], 404);
            }

            // Upload image
            $file = $request->file('image');
            $filename = 'hero_slide_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $path = 'images/anisenso/homepage';

            if (!file_exists(public_path($path))) {
                mkdir(public_path($path), 0755, true);
            }

            $file->move(public_path($path), $filename);
            $imageUrl = '/' . $path . '/' . $filename;

            // Get max order
            $maxOrder = AsHomepageItem::where('sectionId', $section->id)->max('itemOrder') ?? 0;

            // Create item
            $item = AsHomepageItem::create([
                'sectionId' => $section->id,
                'itemType' => 'slide',
                'title' => $request->title ?? 'Slide ' . ($maxOrder + 1),
                'image' => $imageUrl,
                'itemOrder' => $maxOrder + 1,
                'isActive' => true,
                'deleteStatus' => 'active',
            ]);

            // Add imageUrl for response
            $itemData = $item->toArray();
            $itemData['imageUrl'] = $imageUrl;

            return response()->json([
                'success' => true,
                'message' => 'Slide uploaded successfully',
                'item' => $itemData
            ]);
        } catch (\Exception $e) {
            \Log::error('Slide upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
