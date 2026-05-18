<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RgBlogController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('rg_blog_posts')) {
            return view('resortGuruAdmin.pending', ['title' => 'Blog']);
        }
        $posts = DB::table('rg_blog_posts')->orderByDesc('id')->paginate(20);
        return view('resortGuruAdmin.blog', compact('posts'));
    }

    public function create()
    {
        return view('resortGuruAdmin.blog-form', ['post' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePost($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['author_id'] = Auth::id();
        $data['created_at'] = now();
        $data['updated_at'] = now();
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }
        DB::table('rg_blog_posts')->insert($data);
        return redirect()->route('resort-guru-blog.index')->with('success', 'Post created.');
    }

    public function edit(Request $request)
    {
        $id = (int) $request->input('id');
        $post = DB::table('rg_blog_posts')->where('id', $id)->first();
        if (!$post) abort(404);
        return view('resortGuruAdmin.blog-form', compact('post'));
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $this->validatePost($request);
        $data['updated_at'] = now();
        DB::table('rg_blog_posts')->where('id', $id)->update($data);
        return redirect()->route('resort-guru-blog.index')->with('success', 'Post updated.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_blog_posts')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content_html' => 'nullable|string',
            'cover_path' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'status' => 'required|in:draft,published',
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $base = $slug;
        $i = 1;
        while (DB::table('rg_blog_posts')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
