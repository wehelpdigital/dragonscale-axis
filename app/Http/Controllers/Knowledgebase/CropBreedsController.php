<?php

namespace App\Http\Controllers\Knowledgebase;

use App\Http\Controllers\Controller;
use App\Models\RecomCropBreed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CropBreedsController extends Controller
{
    /**
     * Display the crop breeds listing.
     * Note: Crop breeds are a shared library - all users see the same data.
     */
    public function index(Request $request)
    {
        $query = RecomCropBreed::active();

        // Filter by crop type
        if ($request->filled('crop_type')) {
            $query->forCrop($request->crop_type);
        }

        // Filter by breed type
        if ($request->filled('breed_type')) {
            $query->forBreedType($request->breed_type);
        }

        // Filter by corn type
        if ($request->filled('corn_type')) {
            $query->forCornType($request->corn_type);
        }

        // Search by name or manufacturer
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        // Pagination - default 15, options: 10, 15, 25, 50, 100
        $perPage = $request->filled('per_page') ? (int) $request->per_page : 15;
        $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;

        $breeds = $query->orderBy('cropType')->orderBy('name')->paginate($perPage)->withQueryString();

        $cropTypeLabels = RecomCropBreed::getCropTypeLabels();
        $breedTypeLabels = RecomCropBreed::getBreedTypeLabels();
        $cornTypeLabels = RecomCropBreed::getCornTypeLabels();

        return view('knowledgebase.crop-breeds.index', compact(
            'breeds',
            'cropTypeLabels',
            'breedTypeLabels',
            'cornTypeLabels',
            'perPage'
        ));
    }

    /**
     * Show the form for creating a new crop breed.
     */
    public function create()
    {
        $cropTypeLabels = RecomCropBreed::getCropTypeLabels();
        $breedTypeLabels = RecomCropBreed::getBreedTypeLabels();
        $cornTypeLabels = RecomCropBreed::getCornTypeLabels();

        return view('knowledgebase.crop-breeds.create', compact(
            'cropTypeLabels',
            'breedTypeLabels',
            'cornTypeLabels'
        ));
    }

    /**
     * Store a newly created crop breed.
     * usersId is kept for audit trail (tracking who created the record).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cropType' => 'required|in:corn,rice',
            'breedType' => 'nullable|in:hybrid,inbred,opv',
            'cornType' => 'nullable|in:yellow,white,special',
            'manufacturer' => 'nullable|string|max:255',
            'potentialYield' => 'nullable|string|max:255',
            'maturityDays' => 'nullable|string|max:100',
            'geneProtection' => 'nullable|string',
            'characteristics' => 'nullable|string',
            'relatedInformation' => 'nullable|string',
            'sourceUrl' => 'nullable|url|max:500',
            'breedImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'brochure' => 'nullable|mimes:pdf|max:10240',
        ], [
            'name.required' => 'Please enter the breed/variety name.',
            'cropType.required' => 'Please select a crop type.',
            'cropType.in' => 'Invalid crop type selected.',
            'breedImage.image' => 'The breed image must be an image file.',
            'breedImage.max' => 'The breed image must not exceed 5MB.',
            'brochure.mimes' => 'The brochure must be a PDF file.',
            'brochure.max' => 'The brochure must not exceed 10MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Process gene protection into array
            $geneProtection = null;
            if ($request->filled('geneProtection')) {
                $geneProtection = array_map('trim', explode(',', $request->geneProtection));
            }

            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('breedImage')) {
                $imagePath = $this->uploadFile($request->file('breedImage'), 'crop-breeds/images');
            }

            // Handle brochure upload
            $brochurePath = null;
            if ($request->hasFile('brochure')) {
                $brochurePath = $this->uploadFile($request->file('brochure'), 'crop-breeds/brochures');
            }

            RecomCropBreed::create([
                'usersId' => Auth::id(), // Kept for audit trail
                'name' => $request->name,
                'cropType' => $request->cropType,
                'breedType' => $request->breedType,
                'cornType' => $request->cropType === 'corn' ? $request->cornType : null,
                'manufacturer' => $request->manufacturer,
                'potentialYield' => $request->potentialYield,
                'maturityDays' => $request->maturityDays,
                'geneProtection' => $geneProtection,
                'characteristics' => $request->characteristics,
                'relatedInformation' => $request->relatedInformation,
                'imagePath' => $imagePath,
                'brochurePath' => $brochurePath,
                'sourceUrl' => $request->sourceUrl,
                'isActive' => true,
                'delete_status' => 'active',
            ]);

            return redirect()->route('knowledgebase.crop-breeds')
                ->with('success', 'Crop breed added successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to add crop breed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified crop breed.
     */
    public function show(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return redirect()->route('knowledgebase.crop-breeds')
                ->with('error', 'Crop breed ID is required.');
        }

        $breed = RecomCropBreed::active()
            ->where('id', $id)
            ->first();

        if (!$breed) {
            return redirect()->route('knowledgebase.crop-breeds')
                ->with('error', 'Crop breed not found.');
        }

        $cropTypeLabels = RecomCropBreed::getCropTypeLabels();
        $breedTypeLabels = RecomCropBreed::getBreedTypeLabels();
        $cornTypeLabels = RecomCropBreed::getCornTypeLabels();

        return view('knowledgebase.crop-breeds.show', compact(
            'breed',
            'cropTypeLabels',
            'breedTypeLabels',
            'cornTypeLabels'
        ));
    }

    /**
     * Show the form for editing a crop breed.
     */
    public function edit(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return redirect()->route('knowledgebase.crop-breeds')
                ->with('error', 'Crop breed ID is required.');
        }

        $breed = RecomCropBreed::active()
            ->where('id', $id)
            ->first();

        if (!$breed) {
            return redirect()->route('knowledgebase.crop-breeds')
                ->with('error', 'Crop breed not found.');
        }

        // Convert gene protection array to comma-separated string for editing
        $geneProtectionString = '';
        if (is_array($breed->geneProtection)) {
            $geneProtectionString = implode(', ', $breed->geneProtection);
        }

        $cropTypeLabels = RecomCropBreed::getCropTypeLabels();
        $breedTypeLabels = RecomCropBreed::getBreedTypeLabels();
        $cornTypeLabels = RecomCropBreed::getCornTypeLabels();

        return view('knowledgebase.crop-breeds.edit', compact(
            'breed',
            'geneProtectionString',
            'cropTypeLabels',
            'breedTypeLabels',
            'cornTypeLabels'
        ));
    }

    /**
     * Update the specified crop breed.
     */
    public function update(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return redirect()->route('knowledgebase.crop-breeds')
                ->with('error', 'Crop breed ID is required.');
        }

        $breed = RecomCropBreed::active()
            ->where('id', $id)
            ->first();

        if (!$breed) {
            return redirect()->route('knowledgebase.crop-breeds')
                ->with('error', 'Crop breed not found.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'cropType' => 'required|in:corn,rice',
            'breedType' => 'nullable|in:hybrid,inbred,opv',
            'cornType' => 'nullable|in:yellow,white,special',
            'manufacturer' => 'nullable|string|max:255',
            'potentialYield' => 'nullable|string|max:255',
            'maturityDays' => 'nullable|string|max:100',
            'geneProtection' => 'nullable|string',
            'characteristics' => 'nullable|string',
            'relatedInformation' => 'nullable|string',
            'sourceUrl' => 'nullable|url|max:500',
            'breedImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'brochure' => 'nullable|mimes:pdf|max:10240',
        ], [
            'name.required' => 'Please enter the breed/variety name.',
            'cropType.required' => 'Please select a crop type.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Process gene protection into array
            $geneProtection = null;
            if ($request->filled('geneProtection')) {
                $geneProtection = array_map('trim', explode(',', $request->geneProtection));
            }

            $updateData = [
                'name' => $request->name,
                'cropType' => $request->cropType,
                'breedType' => $request->breedType,
                'cornType' => $request->cropType === 'corn' ? $request->cornType : null,
                'manufacturer' => $request->manufacturer,
                'potentialYield' => $request->potentialYield,
                'maturityDays' => $request->maturityDays,
                'geneProtection' => $geneProtection,
                'characteristics' => $request->characteristics,
                'relatedInformation' => $request->relatedInformation,
                'sourceUrl' => $request->sourceUrl,
            ];

            // Handle image upload
            if ($request->hasFile('breedImage')) {
                // Delete old image if exists
                if ($breed->imagePath) {
                    $this->deleteFile($breed->imagePath);
                }
                $updateData['imagePath'] = $this->uploadFile($request->file('breedImage'), 'crop-breeds/images');
            }

            // Handle brochure upload
            if ($request->hasFile('brochure')) {
                // Delete old brochure if exists
                if ($breed->brochurePath) {
                    $this->deleteFile($breed->brochurePath);
                }
                $updateData['brochurePath'] = $this->uploadFile($request->file('brochure'), 'crop-breeds/brochures');
            }

            // Handle remove image checkbox
            if ($request->has('removeImage') && $breed->imagePath) {
                $this->deleteFile($breed->imagePath);
                $updateData['imagePath'] = null;
            }

            // Handle remove brochure checkbox
            if ($request->has('removeBrochure') && $breed->brochurePath) {
                $this->deleteFile($breed->brochurePath);
                $updateData['brochurePath'] = null;
            }

            $breed->update($updateData);

            return redirect()->route('knowledgebase.crop-breeds')
                ->with('success', 'Crop breed updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update crop breed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Soft delete the specified crop breed.
     */
    public function destroy(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Crop breed ID is required.',
            ], 400);
        }

        $breed = RecomCropBreed::active()
            ->where('id', $id)
            ->first();

        if (!$breed) {
            return response()->json([
                'success' => false,
                'message' => 'Crop breed not found.',
            ], 404);
        }

        try {
            $breed->update(['delete_status' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Crop breed deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete crop breed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle the active status of a crop breed.
     */
    public function toggleStatus(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Crop breed ID is required.',
            ], 400);
        }

        $breed = RecomCropBreed::active()
            ->where('id', $id)
            ->first();

        if (!$breed) {
            return response()->json([
                'success' => false,
                'message' => 'Crop breed not found.',
            ], 404);
        }

        try {
            $breed->update(['isActive' => !$breed->isActive]);

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully.',
                'isActive' => $breed->isActive,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status.',
            ], 500);
        }
    }

    /**
     * API endpoint to get breeds by crop type and breed type for wizard.
     * Returns shared library data accessible by all users.
     */
    public function getBreedsByCriteria(Request $request)
    {
        $query = RecomCropBreed::active()->enabled();

        if ($request->filled('crop_type')) {
            $query->forCrop($request->crop_type);
        }

        if ($request->filled('breed_type')) {
            $query->forBreedType($request->breed_type);
        }

        if ($request->filled('corn_type')) {
            $query->forCornType($request->corn_type);
        }

        $breeds = $query->orderBy('name')->get(['id', 'name', 'manufacturer', 'potentialYield', 'imagePath']);

        return response()->json([
            'success' => true,
            'breeds' => $breeds,
        ]);
    }

    /**
     * API endpoint to get a single breed's full details.
     * Returns all available information for the variety detail modal.
     */
    public function getBreedDetail(Request $request)
    {
        $id = $request->query('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Breed ID is required.',
            ], 400);
        }

        $breed = RecomCropBreed::active()
            ->where('id', $id)
            ->first();

        if (!$breed) {
            return response()->json([
                'success' => false,
                'message' => 'Breed not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'breed' => [
                'id' => $breed->id,
                'name' => $breed->name,
                'cropType' => $breed->cropType,
                'breedType' => $breed->breedType,
                'cornType' => $breed->cornType,
                'manufacturer' => $breed->manufacturer,
                'potentialYield' => $breed->potentialYield,
                'maturityDays' => $breed->maturityDays,
                'geneProtection' => $breed->geneProtection,
                'characteristics' => $breed->characteristics,
                'relatedInformation' => $breed->relatedInformation,
                'imagePath' => $breed->imagePath,
                'brochurePath' => $breed->brochurePath,
                'sourceUrl' => $breed->sourceUrl,
            ],
        ]);
    }

    /**
     * Upload file to public storage
     */
    private function uploadFile($file, $folder)
    {
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $destinationPath = public_path('images/' . $folder);

        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $filename);

        return 'images/' . $folder . '/' . $filename;
    }

    /**
     * Delete file from public storage
     */
    private function deleteFile($path)
    {
        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
