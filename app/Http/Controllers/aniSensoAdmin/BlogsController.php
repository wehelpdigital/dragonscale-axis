<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogsController extends Controller
{
    /**
     * Display a listing of blogs.
     */
    public function index(Request $request)
    {
        $query = AsBlog::active();

        // Filter by title
        if ($request->filled('title')) {
            $query->where('blogTitle', 'like', '%' . $request->title . '%');
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('blogCategory', $request->category);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('blogStatus', $request->status);
        }

        $blogs = $query->orderBy('created_at', 'desc')->paginate(15);
        $categories = AsBlog::getCategories();
        $statuses = AsBlog::getStatuses();

        return view('aniSensoAdmin.blogs.index', compact('blogs', 'categories', 'statuses'));
    }

    /**
     * Show the form for creating a new blog.
     */
    public function create()
    {
        $categories = AsBlog::getCategories();
        $statuses = AsBlog::getStatuses();
        $schemaTypes = AsBlog::getSchemaTypes();

        return view('aniSensoAdmin.blogs.create', compact('categories', 'statuses', 'schemaTypes'));
    }

    /**
     * Store a newly created blog.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blogTitle' => 'required|string|max:255',
            'blogCategory' => 'required|string|max:100',
            'blogExcerpt' => 'required|string|max:500',
            'blogContent' => 'nullable|string',
            'blogFeaturedImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'blogStatus' => 'required|in:draft,published,archived',
            'isFeatured' => 'nullable|boolean',
            // SEO Basic
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'metaKeywords' => 'nullable|string|max:255',
            'focusKeyword' => 'nullable|string|max:100',
            // Open Graph
            'ogTitle' => 'nullable|string|max:255',
            'ogDescription' => 'nullable|string|max:500',
            'ogImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            // Twitter
            'twitterTitle' => 'nullable|string|max:255',
            'twitterDescription' => 'nullable|string|max:500',
            'twitterImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            // Advanced
            'canonicalUrl' => 'nullable|url|max:255',
            'schemaType' => 'nullable|string|max:50',
            'authorName' => 'nullable|string|max:255',
            // Builder
            'useBuilder' => 'nullable|boolean',
            'builderContent' => 'nullable|string',
        ], [
            'blogTitle.required' => 'Blog title is required.',
            'blogCategory.required' => 'Please select a category.',
            'blogExcerpt.required' => 'Blog excerpt is required.',
            'blogFeaturedImage.image' => 'The featured image must be an image file.',
            'blogFeaturedImage.max' => 'The featured image must not exceed 5MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Generate slug
        $slug = AsBlog::generateSlug($request->blogTitle);

        // Get category color
        $categories = AsBlog::getCategories();
        $categoryColor = $categories[$request->blogCategory] ?? 'brand-green';

        // Handle image uploads
        $featuredImagePath = $this->handleImageUpload($request, 'blogFeaturedImage', 'images/blogs');
        $ogImagePath = $this->handleImageUpload($request, 'ogImage', 'images/blogs/og');
        $twitterImagePath = $this->handleImageUpload($request, 'twitterImage', 'images/blogs/twitter');

        // Parse builder content
        $useBuilder = $request->input('useBuilder', '0') === '1';
        $builderContent = null;
        $blogContent = $request->blogContent;

        if ($useBuilder && $request->filled('builderContent')) {
            $builderContent = json_decode($request->builderContent, true);
            $blogContent = $builderContent['html'] ?? $request->blogContent;
        }

        // Create blog
        $blog = AsBlog::create([
            'usersId' => Auth::id(),
            'blogTitle' => $request->blogTitle,
            'blogSlug' => $slug,
            'blogCategory' => $request->blogCategory,
            'blogCategoryColor' => $categoryColor,
            'blogFeaturedImage' => $featuredImagePath,
            'blogExcerpt' => $request->blogExcerpt,
            'blogContent' => $blogContent,
            'builderContent' => $builderContent,
            'useBuilder' => $useBuilder,
            // SEO Basic
            'metaTitle' => $request->metaTitle,
            'metaDescription' => $request->metaDescription,
            'metaKeywords' => $request->metaKeywords,
            'focusKeyword' => $request->focusKeyword,
            // Open Graph
            'ogTitle' => $request->ogTitle,
            'ogDescription' => $request->ogDescription,
            'ogImage' => $ogImagePath,
            // Twitter
            'twitterTitle' => $request->twitterTitle,
            'twitterDescription' => $request->twitterDescription,
            'twitterImage' => $twitterImagePath,
            // Advanced
            'canonicalUrl' => $request->canonicalUrl,
            'schemaType' => $request->schemaType ?? 'BlogPosting',
            // Publishing
            'blogStatus' => $request->blogStatus,
            'publishedAt' => $request->blogStatus === 'published' ? now() : null,
            'isFeatured' => $request->has('isFeatured'),
            'authorName' => $request->authorName,
            'deleteStatus' => 'active',
        ]);

        // Calculate and save SEO score
        $seoResult = $blog->analyzeSeo();
        $blog->update([
            'seoScore' => $seoResult['score'],
            'seoAnalysis' => $seoResult['analysis'],
            'readingTime' => $blog->calculateReadingTime(),
        ]);

        return redirect()->route('anisenso-blogs')->with('success', 'Blog post created successfully!');
    }

    /**
     * Show the form for editing a blog.
     */
    public function edit(Request $request)
    {
        $id = $request->query('id');

        $blog = AsBlog::active()->findOrFail($id);
        $categories = AsBlog::getCategories();
        $statuses = AsBlog::getStatuses();
        $schemaTypes = AsBlog::getSchemaTypes();

        return view('aniSensoAdmin.blogs.edit', compact('blog', 'categories', 'statuses', 'schemaTypes'));
    }

    /**
     * Update the specified blog.
     */
    public function update(Request $request, $id)
    {
        $blog = AsBlog::active()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'blogTitle' => 'required|string|max:255',
            'blogCategory' => 'required|string|max:100',
            'blogExcerpt' => 'required|string|max:500',
            'blogContent' => 'nullable|string',
            'blogFeaturedImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'blogStatus' => 'required|in:draft,published,archived',
            'isFeatured' => 'nullable|boolean',
            // SEO Basic
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:500',
            'metaKeywords' => 'nullable|string|max:255',
            'focusKeyword' => 'nullable|string|max:100',
            // Open Graph
            'ogTitle' => 'nullable|string|max:255',
            'ogDescription' => 'nullable|string|max:500',
            'ogImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            // Twitter
            'twitterTitle' => 'nullable|string|max:255',
            'twitterDescription' => 'nullable|string|max:500',
            'twitterImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            // Advanced
            'canonicalUrl' => 'nullable|url|max:255',
            'schemaType' => 'nullable|string|max:50',
            'authorName' => 'nullable|string|max:255',
            // Builder
            'useBuilder' => 'nullable|boolean',
            'builderContent' => 'nullable|string',
        ], [
            'blogTitle.required' => 'Blog title is required.',
            'blogCategory.required' => 'Please select a category.',
            'blogExcerpt.required' => 'Blog excerpt is required.',
            'blogFeaturedImage.image' => 'The featured image must be an image file.',
            'blogFeaturedImage.max' => 'The featured image must not exceed 5MB.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Regenerate slug if title changed
        $slug = $blog->blogSlug;
        if ($request->blogTitle !== $blog->blogTitle) {
            $slug = AsBlog::generateSlug($request->blogTitle, $id);
        }

        // Get category color
        $categories = AsBlog::getCategories();
        $categoryColor = $categories[$request->blogCategory] ?? 'brand-green';

        // Handle image uploads
        $featuredImagePath = $this->handleImageUpload($request, 'blogFeaturedImage', 'images/blogs', $blog->blogFeaturedImage);
        $ogImagePath = $this->handleImageUpload($request, 'ogImage', 'images/blogs/og', $blog->ogImage);
        $twitterImagePath = $this->handleImageUpload($request, 'twitterImage', 'images/blogs/twitter', $blog->twitterImage);

        // Handle remove image request
        if ($request->input('removeImage') === '1') {
            if ($blog->blogFeaturedImage && file_exists(public_path($blog->blogFeaturedImage))) {
                unlink(public_path($blog->blogFeaturedImage));
            }
            $featuredImagePath = null;
        }

        // Parse builder content
        $useBuilder = $request->input('useBuilder', '0') === '1';
        $builderContent = $blog->builderContent;
        $blogContent = $request->blogContent;

        if ($useBuilder && $request->filled('builderContent')) {
            $builderContent = json_decode($request->builderContent, true);
            $blogContent = $builderContent['html'] ?? $request->blogContent;
        } elseif (!$useBuilder) {
            $builderContent = null;
        }

        // Handle published date
        $publishedAt = $blog->publishedAt;
        if ($request->blogStatus === 'published' && !$blog->publishedAt) {
            $publishedAt = now();
        }

        // Update blog
        $blog->update([
            'blogTitle' => $request->blogTitle,
            'blogSlug' => $slug,
            'blogCategory' => $request->blogCategory,
            'blogCategoryColor' => $categoryColor,
            'blogFeaturedImage' => $featuredImagePath,
            'blogExcerpt' => $request->blogExcerpt,
            'blogContent' => $blogContent,
            'builderContent' => $builderContent,
            'useBuilder' => $useBuilder,
            // SEO Basic
            'metaTitle' => $request->metaTitle,
            'metaDescription' => $request->metaDescription,
            'metaKeywords' => $request->metaKeywords,
            'focusKeyword' => $request->focusKeyword,
            // Open Graph
            'ogTitle' => $request->ogTitle,
            'ogDescription' => $request->ogDescription,
            'ogImage' => $ogImagePath,
            // Twitter
            'twitterTitle' => $request->twitterTitle,
            'twitterDescription' => $request->twitterDescription,
            'twitterImage' => $twitterImagePath,
            // Advanced
            'canonicalUrl' => $request->canonicalUrl,
            'schemaType' => $request->schemaType ?? 'BlogPosting',
            // Publishing
            'blogStatus' => $request->blogStatus,
            'publishedAt' => $publishedAt,
            'isFeatured' => $request->has('isFeatured'),
            'authorName' => $request->authorName,
        ]);

        // Recalculate SEO score
        $seoResult = $blog->analyzeSeo();
        $blog->update([
            'seoScore' => $seoResult['score'],
            'seoAnalysis' => $seoResult['analysis'],
            'readingTime' => $blog->calculateReadingTime(),
        ]);

        return redirect()->route('anisenso-blogs')->with('success', 'Blog post updated successfully!');
    }

    /**
     * Delete a blog (soft delete).
     */
    public function destroy($id)
    {
        $blog = AsBlog::active()->findOrFail($id);

        $blog->update(['deleteStatus' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Blog post deleted successfully!'
        ]);
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured($id)
    {
        $blog = AsBlog::active()->findOrFail($id);

        $blog->update(['isFeatured' => !$blog->isFeatured]);

        return response()->json([
            'success' => true,
            'message' => $blog->isFeatured ? 'Blog marked as featured!' : 'Blog removed from featured!',
            'isFeatured' => $blog->isFeatured
        ]);
    }

    /**
     * Update blog status via AJAX.
     */
    public function updateStatus(Request $request, $id)
    {
        $blog = AsBlog::active()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'blogStatus' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid status provided.'
            ], 422);
        }

        $publishedAt = $blog->publishedAt;
        if ($request->blogStatus === 'published' && !$blog->publishedAt) {
            $publishedAt = now();
        }

        $blog->update([
            'blogStatus' => $request->blogStatus,
            'publishedAt' => $publishedAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Blog status updated successfully!',
            'status' => $request->blogStatus
        ]);
    }

    /**
     * Remove featured image.
     */
    public function removeImage($id)
    {
        $blog = AsBlog::active()->findOrFail($id);

        if ($blog->blogFeaturedImage && file_exists(public_path($blog->blogFeaturedImage))) {
            unlink(public_path($blog->blogFeaturedImage));
        }

        $blog->update(['blogFeaturedImage' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Featured image removed successfully!'
        ]);
    }

    /**
     * Analyze SEO via AJAX.
     */
    public function analyzeSeo($id)
    {
        $blog = AsBlog::active()->findOrFail($id);

        $result = $blog->analyzeSeo();

        $blog->update([
            'seoScore' => $result['score'],
            'seoAnalysis' => $result['analysis'],
        ]);

        return response()->json([
            'success' => true,
            'score' => $result['score'],
            'analysis' => $result['analysis']
        ]);
    }

    /**
     * Handle image upload.
     */
    private function handleImageUpload(Request $request, $fieldName, $directory, $existingPath = null)
    {
        if ($request->hasFile($fieldName)) {
            // Delete old image if exists
            if ($existingPath && file_exists(public_path($existingPath))) {
                unlink(public_path($existingPath));
            }

            // Ensure directory exists
            $fullPath = public_path($directory);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            $image = $request->file($fieldName);
            $filename = Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move($fullPath, $filename);

            return $directory . '/' . $filename;
        }

        return $existingPath;
    }
}
