<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsLegalPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Legal / info page CMS — the team edits the Privacy, Terms, Cookie and About
 * pages that AniSystem shows in its footer. Content lives in the shared
 * `as_legal_pages` table.
 */
class AniSensoLegalController extends Controller
{
    public function index()
    {
        $pages = AsLegalPage::active()
            ->orderBy('sortOrder')
            ->orderBy('id')
            ->get();

        return view('aniSensoAdmin.legal.index', compact('pages'));
    }

    public function edit($id)
    {
        $page = AsLegalPage::active()->where('id', $id)->firstOrFail();

        return view('aniSensoAdmin.legal.edit', compact('page'));
    }

    public function update(Request $request, $id)
    {
        $page = AsLegalPage::active()->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'body' => 'nullable|string',
            'isPublished' => 'nullable|boolean',
        ]);

        $page->title = $data['title'];
        $page->body = $data['body'] ?? '';
        $page->isPublished = $request->boolean('isPublished') ? 1 : 0;
        $page->save();

        return redirect()->route('anisenso-legal.index')->with('success', $page->title . ' updated.');
    }

    /** Create a new custom footer/legal page. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'slug' => 'nullable|string|max:40',
        ]);

        $slug = Str::slug($data['slug'] ?: $data['title']);
        if ($slug === '' || AsLegalPage::where('slug', $slug)->exists()) {
            return redirect()->route('anisenso-legal.index')->with('error', 'That slug is empty or already exists.');
        }

        $page = AsLegalPage::create([
            'slug' => $slug,
            'title' => $data['title'],
            'body' => '<p>Edit this page.</p>',
            'sortOrder' => (int) AsLegalPage::max('sortOrder') + 1,
            'isPublished' => 0,
            'deleteStatus' => 1,
        ]);

        return redirect()->route('anisenso-legal.edit', $page->id)->with('success', 'Page created — add your content.');
    }
}
