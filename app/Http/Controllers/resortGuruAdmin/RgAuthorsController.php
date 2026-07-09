<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use App\Models\RgAuthor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RgAuthorsController extends Controller
{
    private array $clusterOptions = [
        'rizal' => 'Rizal', 'cavite' => 'Cavite', 'bulacan' => 'Bulacan',
        'pampanga' => 'Pampanga', 'batangas' => 'Batangas', 'laguna' => 'Laguna',
        'quezon' => 'Quezon', 'bicol' => 'Bicol', 'north-luzon' => 'North Luzon',
        'metro-manila' => 'Metro Manila', 'mindanao' => 'Mindanao',
        'visayas' => 'Visayas', 'palawan' => 'Palawan & Mindoro',
    ];

    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_authors')) {
            return view('resortGuruAdmin.pending', ['title' => 'Authors']);
        }

        if ($request->ajax()) {
            $authors = DB::table('rg_authors')->orderBy('sort_order')->orderBy('id');
            return DataTables::of($authors)
                ->addColumn('avatar', function ($a) {
                    $url = $a->avatar_path ?: 'https://api.dicebear.com/7.x/notionists/svg?seed=' . urlencode($a->name);
                    if (!preg_match('#^https?://#i', $url) && !empty($a->avatar_path)) {
                        $url = \App\Support\RgFrontend::urlFor('storage/' . ltrim($a->avatar_path, '/'));
                    }
                    return '<img src="' . e($url) . '" alt="" style="width:42px;height:42px;border-radius:50%;background:#f1f5f9">';
                })
                ->addColumn('pages_count', fn($a) => DB::table('rg_seo_pages')->where('author_id', $a->id)->count())
                ->addColumn('status_pill', function ($a) {
                    $cls = $a->status === 'active' ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($a->status) . '</span>';
                })
                ->addColumn('actions', function ($a) {
                    return '<div class="d-flex gap-1">'
                        . '<a href="/resort-guru-authors-edit?id=' . $a->id . '" class="btn btn-sm btn-info"><i class="bx bx-edit"></i></a>'
                        . '<button onclick="deleteAuthor(' . $a->id . ')" class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['avatar', 'status_pill', 'actions'])
                ->make(true);
        }

        return view('resortGuruAdmin.authors-index');
    }

    public function create()
    {
        return view('resortGuruAdmin.authors-form', [
            'author' => null,
            'clusterOptions' => $this->clusterOptions,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->ensureUniqueSlug($data['name']);
        $data['avatar_path'] = $this->resolveAvatarPath($request, null, $data['name']);
        $data['covers_clusters'] = implode(',', $request->input('covers_clusters', []));
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('rg_authors')->insert($data);
        return redirect('/resort-guru-authors')->with('success', 'Author created.');
    }

    public function edit(Request $request)
    {
        $author = DB::table('rg_authors')->where('id', (int) $request->input('id'))->first();
        if (!$author) abort(404);
        return view('resortGuruAdmin.authors-form', [
            'author' => $author,
            'clusterOptions' => $this->clusterOptions,
        ]);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $author = DB::table('rg_authors')->where('id', $id)->first();
        if (!$author) abort(404);
        $data = $this->validateData($request, $id);
        $data['slug'] = $author->slug;  // keep slug stable
        $data['avatar_path'] = $this->resolveAvatarPath($request, $author->avatar_path, $data['name']);
        $data['covers_clusters'] = implode(',', $request->input('covers_clusters', []));
        $data['updated_at'] = now();
        DB::table('rg_authors')->where('id', $id)->update($data);
        return redirect('/resort-guru-authors')->with('success', 'Author updated.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_seo_pages')->where('author_id', $id)->update(['author_id' => null]);
        DB::table('rg_authors')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function validateData(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:200',
            'role' => 'nullable|string|max:200',
            'bio' => 'nullable|string|max:2000',
            'email' => 'nullable|email|max:200',
            'instagram' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'twitter' => 'nullable|string|max:100',
            'home_base' => 'nullable|string|max:200',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
        ]);
    }

    private function ensureUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (DB::table('rg_authors')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    private function resolveAvatarPath(Request $request, ?string $current, string $name): ?string
    {
        if ($request->hasFile('avatar')) {
            // Note: file upload writes to mother-app storage which is not accessible to the
            // frontend Apache. Recommended workflow is to paste a DiceBear or external URL.
            // We accept upload for completeness but the URL approach is preferred.
            $file = $request->file('avatar');
            $path = $file->store('rg-media/authors', 'public');
            return $path;
        }
        if ($request->input('avatar_url')) {
            return $request->input('avatar_url');
        }
        if ($current) return $current;
        // Auto-generate DiceBear URL from name
        return 'https://api.dicebear.com/7.x/notionists/svg?seed=' . urlencode($name);
    }
}
