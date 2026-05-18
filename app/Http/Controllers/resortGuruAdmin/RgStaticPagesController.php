<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgStaticPagesController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('rg_static_pages')) {
            return view('resortGuruAdmin.pending', ['title' => 'Static Pages']);
        }
        $pages = DB::table('rg_static_pages')->orderBy('slug')->get();
        return view('resortGuruAdmin.static-pages', compact('pages'));
    }

    public function edit(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_static_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        return view('resortGuruAdmin.static-pages-edit', compact('page'));
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'content_html' => 'nullable|string',
            'is_published' => 'nullable',
        ]);
        $data['is_published'] = $request->has('is_published') ? 1 : 0;
        $data['updated_at'] = now();
        DB::table('rg_static_pages')->where('id', $id)->update($data);
        return redirect()->route('resort-guru-static.edit', ['id' => $id])->with('success', 'Page saved.');
    }
}
