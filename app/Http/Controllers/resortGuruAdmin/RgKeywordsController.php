<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RgKeywordsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_keywords')) {
            return view('resortGuruAdmin.pending', ['title' => 'Keywords']);
        }
        if ($request->ajax()) {
            $query = DB::table('rg_keywords as k')
                ->leftJoin(DB::raw('(SELECT keyword_id, COUNT(*) as pages_total, SUM(is_published) as pages_published FROM rg_seo_pages GROUP BY keyword_id) p'), 'p.keyword_id', '=', 'k.id')
                ->select([
                    'k.id', 'k.phrase', 'k.slug', 'k.search_volume_monthly', 'k.keyword_difficulty',
                    'k.status', 'k.updated_at',
                    DB::raw('COALESCE(p.pages_total, 0) as pages_total'),
                    DB::raw('COALESCE(p.pages_published, 0) as pages_published'),
                ]);
            return DataTables::of($query)
                ->editColumn('updated_at', fn($r) => $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d H:i') : '')
                ->editColumn('status', function ($r) {
                    $colors = ['active' => 'success', 'draft' => 'secondary', 'archived' => 'dark'];
                    $c = $colors[$r->status] ?? 'secondary';
                    return '<span class="badge bg-' . $c . '">' . ucfirst($r->status) . '</span>';
                })
                ->addColumn('pages_summary', function ($r) {
                    $total = (int) $r->pages_total;
                    $live = (int) $r->pages_published;
                    if ($total === 0) return '<span class="text-muted small">0 pages</span>';
                    $color = $live > 0 ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $color . '">' . $live . ' / ' . $total . '</span><br><small class="text-muted">live / total</small>';
                })
                ->addColumn('actions', function ($r) {
                    $managePagesUrl = route('resort-guru-keywords-pages.index', ['id' => $r->id]);
                    $editKwUrl = route('resort-guru-keywords.edit', ['id' => $r->id]);
                    return '<a href="' . $managePagesUrl . '" class="btn btn-sm btn-success me-1" title="Manage pages for this keyword"><i class="bx bx-file"></i> Pages</a>'
                        . '<a href="' . $editKwUrl . '" class="btn btn-sm btn-primary me-1" title="Edit keyword"><i class="bx bx-cog"></i></a>'
                        . '<button class="btn btn-sm btn-danger" onclick="confirmDelete(' . $r->id . ')" title="Delete"><i class="bx bx-trash"></i></button>';
                })
                ->rawColumns(['actions', 'status', 'pages_summary'])
                ->make(true);
        }
        return view('resortGuruAdmin.keywords');
    }

    public function create()
    {
        return view('resortGuruAdmin.keywords-add');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phrase' => 'required|string|max:255',
            'search_volume_monthly' => 'nullable|integer',
            'keyword_difficulty' => 'nullable|integer',
            'cluster_tag' => 'nullable|string|max:100',
            'intent' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,draft,archived',
        ]);

        $data['slug'] = $this->uniqueSlug($data['phrase']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::transaction(function () use ($data) {
            $id = DB::table('rg_keywords')->insertGetId($data);
            DB::table('rg_seo_pages')->insert([
                'keyword_id' => $id,
                'title' => $data['phrase'],
                'meta_title' => $data['phrase'],
                'meta_description' => '',
                'h1' => $data['phrase'],
                'is_published' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('resort-guru-keywords.index')->with('success', 'Keyword created. A linked SEO page was auto-created.');
    }

    public function edit(Request $request)
    {
        $id = (int) $request->input('id');
        $keyword = DB::table('rg_keywords')->where('id', $id)->first();
        if (!$keyword) abort(404);
        return view('resortGuruAdmin.keywords-edit', compact('keyword'));
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate([
            'phrase' => 'required|string|max:255',
            'search_volume_monthly' => 'nullable|integer',
            'keyword_difficulty' => 'nullable|integer',
            'cluster_tag' => 'nullable|string|max:100',
            'intent' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,draft,archived',
        ]);
        $data['updated_at'] = now();
        DB::table('rg_keywords')->where('id', $id)->update($data);
        return redirect()->route('resort-guru-keywords.index')->with('success', 'Keyword updated.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_keywords')->where('id', $id)->delete();
        DB::table('rg_seo_pages')->where('keyword_id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:10240']);
        $path = $request->file('file')->getRealPath();
        $h = fopen($path, 'r');
        if (!$h) {
            return back()->with('error', 'Could not read CSV.');
        }
        $headers = fgetcsv($h);
        if (!$headers) {
            fclose($h);
            return back()->with('error', 'CSV is empty.');
        }
        $headers = array_map(fn($s) => strtolower(trim($s)), $headers);
        $rowsCreated = 0;
        DB::transaction(function () use ($h, $headers, &$rowsCreated) {
            while (($row = fgetcsv($h)) !== false) {
                $assoc = @array_combine($headers, $row) ?: [];
                $phrase = trim($assoc['phrase'] ?? $assoc['keyword'] ?? $assoc['keyphrase'] ?? '');
                if ($phrase === '') continue;
                $slug = $this->uniqueSlug($phrase);
                $id = DB::table('rg_keywords')->insertGetId([
                    'phrase' => $phrase,
                    'slug' => $slug,
                    'search_volume_monthly' => (int) ($assoc['volume'] ?? $assoc['search_volume_monthly'] ?? 0),
                    'keyword_difficulty' => (int) ($assoc['kd'] ?? $assoc['keyword_difficulty'] ?? 0),
                    'cluster_tag' => $assoc['cluster_tag'] ?? null,
                    'intent' => $assoc['intent'] ?? null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('rg_seo_pages')->insert([
                    'keyword_id' => $id,
                    'title' => $phrase,
                    'meta_title' => $phrase,
                    'meta_description' => '',
                    'h1' => $phrase,
                    'is_published' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $rowsCreated++;
            }
        });
        fclose($h);
        return redirect()->route('resort-guru-keywords.index')->with('success', "Imported $rowsCreated keywords.");
    }

    private function uniqueSlug(string $phrase): string
    {
        $slug = Str::slug($phrase);
        $base = $slug;
        $i = 1;
        while (DB::table('rg_keywords')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
