<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsWebsitePage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AniSensoWebsitePagesController extends Controller
{
    /**
     * Display a listing of website pages
     */
    public function index()
    {
        $pages = AsWebsitePage::active()
            ->orderBy('pageOrder', 'asc')
            ->orderBy('pageName', 'asc')
            ->get();

        return view('aniSensoAdmin.website-pages.index', compact('pages'));
    }

    /**
     * Show form for creating a new page
     */
    public function create()
    {
        return view('aniSensoAdmin.website-pages.create');
    }

    /**
     * Store a newly created page
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pageName' => 'required|string|max:100',
            'pageSlug' => 'nullable|string|max:100|unique:as_website_pages,pageSlug',
            'pageIcon' => 'nullable|string|max:50',
            'pageContent' => 'nullable|string',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'metaKeywords' => 'nullable|string|max:500',
            'pageStatus' => 'required|in:draft,published',
        ], [
            'pageName.required' => 'Page name is required.',
            'pageSlug.unique' => 'This page slug is already in use.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Generate slug if not provided
            $slug = $request->pageSlug ?: Str::slug($request->pageName);

            // Get max order
            $maxOrder = AsWebsitePage::active()->max('pageOrder') ?? 0;

            AsWebsitePage::create([
                'pageName' => $request->pageName,
                'pageSlug' => $slug,
                'pageIcon' => $request->pageIcon ?: 'bx-file',
                'pageContent' => $request->pageContent,
                'metaTitle' => $request->metaTitle,
                'metaDescription' => $request->metaDescription,
                'metaKeywords' => $request->metaKeywords,
                'pageStatus' => $request->pageStatus,
                'pageOrder' => $maxOrder + 1,
                'isSystemPage' => false,
                'deleteStatus' => 'active',
            ]);

            return redirect()->route('anisenso-website-pages')
                ->with('success', 'Page created successfully!');

        } catch (\Exception $e) {
            Log::error('Error creating website page: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to create page. Please try again.')
                ->withInput();
        }
    }

    /**
     * Show form for editing a page
     */
    public function edit(Request $request)
    {
        $pageId = $request->query('id');

        if (!$pageId) {
            return redirect()->route('anisenso-website-pages')
                ->with('error', 'Page ID is required.');
        }

        $page = AsWebsitePage::active()->find($pageId);

        if (!$page) {
            return redirect()->route('anisenso-website-pages')
                ->with('error', 'Page not found.');
        }

        return view('aniSensoAdmin.website-pages.edit', compact('page'));
    }

    /**
     * Update a page
     */
    public function update(Request $request, $id)
    {
        $page = AsWebsitePage::active()->find($id);

        if (!$page) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pageName' => 'required|string|max:100',
            'pageSlug' => 'nullable|string|max:100|unique:as_website_pages,pageSlug,' . $id,
            'pageIcon' => 'nullable|string|max:50',
            'pageContent' => 'nullable|string',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'metaKeywords' => 'nullable|string|max:500',
            'pageStatus' => 'required|in:draft,published',
        ], [
            'pageName.required' => 'Page name is required.',
            'pageSlug.unique' => 'This page slug is already in use.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Don't allow changing slug for system pages
            $slug = $page->isSystemPage ? $page->pageSlug : ($request->pageSlug ?: Str::slug($request->pageName));

            $page->update([
                'pageName' => $request->pageName,
                'pageSlug' => $slug,
                'pageIcon' => $request->pageIcon ?: $page->pageIcon,
                'pageContent' => $request->pageContent,
                'metaTitle' => $request->metaTitle,
                'metaDescription' => $request->metaDescription,
                'metaKeywords' => $request->metaKeywords,
                'pageStatus' => $request->pageStatus,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Page updated successfully!'
                ]);
            }

            return redirect()->route('anisenso-website-pages')
                ->with('success', 'Page updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating website page: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update page. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to update page. Please try again.')
                ->withInput();
        }
    }

    /**
     * Toggle page status
     */
    public function toggleStatus($id)
    {
        try {
            $page = AsWebsitePage::active()->find($id);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found.'
                ], 404);
            }

            $newStatus = $page->pageStatus === 'published' ? 'draft' : 'published';
            $page->update(['pageStatus' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Page status updated to ' . ucfirst($newStatus) . '!',
                'newStatus' => $newStatus,
                'statusLabel' => $page->status_label,
                'statusBadge' => $page->status_badge
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling page status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update page status.'
            ], 500);
        }
    }

    /**
     * Delete a page (soft delete)
     */
    public function destroy($id)
    {
        try {
            $page = AsWebsitePage::active()->find($id);

            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found.'
                ], 404);
            }

            // Prevent deletion of system pages
            if ($page->isSystemPage) {
                return response()->json([
                    'success' => false,
                    'message' => 'System pages cannot be deleted.'
                ], 403);
            }

            $page->update(['deleteStatus' => 'deleted']);

            return response()->json([
                'success' => true,
                'message' => 'Page deleted successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting website page: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete page.'
            ], 500);
        }
    }

    /**
     * Update page order
     */
    public function updateOrder(Request $request)
    {
        try {
            $order = $request->input('order', []);

            foreach ($order as $index => $pageId) {
                AsWebsitePage::where('id', $pageId)->update(['pageOrder' => $index + 1]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Page order updated successfully!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating page order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update page order.'
            ], 500);
        }
    }
}
