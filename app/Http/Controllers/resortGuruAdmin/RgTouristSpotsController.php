<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RgTouristSpotsController extends Controller
{
    private array $clusterOptions = [
        'rizal' => 'Rizal', 'cavite' => 'Cavite', 'bulacan' => 'Bulacan',
        'pampanga' => 'Pampanga', 'batangas' => 'Batangas', 'laguna' => 'Laguna',
        'quezon' => 'Quezon', 'bicol' => 'Bicol', 'north-luzon' => 'North Luzon',
        'metro-manila' => 'Metro Manila', 'mindanao' => 'Mindanao',
        'visayas' => 'Visayas', 'palawan' => 'Palawan', 'other' => 'Other',
    ];

    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_tourist_spots')) {
            return view('resortGuruAdmin.pending', ['title' => 'Tourist Spots']);
        }

        if ($request->ajax()) {
            $rows = DB::table('rg_tourist_spots as s')
                ->leftJoin('rg_media as m', 'm.id', '=', 's.media_id')
                ->leftJoin('rg_keywords as k', 'k.id', '=', 's.keyword_id')
                ->select([
                    's.id', 's.name', 's.slug', 's.location', 's.region_label',
                    's.cluster_tag', 's.featured_order', 's.status', 's.updated_at',
                    'm.path as media_path',
                    'k.phrase as keyword_phrase', 'k.slug as keyword_slug',
                ]);

            return DataTables::of($rows)
                ->addColumn('thumb', function ($r) {
                    if (!$r->media_path) {
                        return '<div style="width:48px;height:48px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#94a3b8"><i class="bx bx-image"></i></div>';
                    }
                    $url = \App\Support\RgFrontend::urlFor('storage/' . ltrim($r->media_path, '/'));
                    return '<img src="' . e($url) . '" alt="" style="width:48px;height:48px;border-radius:8px;object-fit:cover;background:#f1f5f9" loading="lazy">';
                })
                ->addColumn('keyword_link', function ($r) {
                    if (!$r->keyword_phrase) {
                        return '<span class="badge bg-warning">Not linked</span>';
                    }
                    return '<span class="badge bg-light text-dark">' . e($r->keyword_phrase) . '</span>';
                })
                ->addColumn('featured_badge', function ($r) {
                    if ($r->featured_order === null) {
                        return '<span class="text-muted">—</span>';
                    }
                    return '<span class="badge bg-primary">#' . (int) $r->featured_order . '</span>';
                })
                ->addColumn('status_pill', function ($r) {
                    $cls = $r->status === 'published' ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($r->status) . '</span>';
                })
                ->editColumn('updated_at', fn($r) => $r->updated_at
                    ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d')
                    : '')
                ->addColumn('actions', function ($r) {
                    return '<div class="d-flex gap-1">'
                        . '<a href="/resort-guru-tourist-spots-edit?id=' . $r->id . '" class="btn btn-sm btn-info" title="Edit"><i class="bx bx-edit"></i></a>'
                        . '<button onclick="deleteSpot(' . $r->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="bx bx-trash"></i></button>'
                        . '</div>';
                })
                ->rawColumns(['thumb', 'keyword_link', 'featured_badge', 'status_pill', 'actions'])
                ->make(true);
        }

        return view('resortGuruAdmin.tourist-spots-index');
    }

    public function create()
    {
        return view('resortGuruAdmin.tourist-spots-form', [
            'spot' => null,
            'keywords' => $this->keywordOptions(),
            'clusterOptions' => $this->clusterOptions,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->ensureUniqueSlug($data['name'], $data['destination_key'] ?? null);
        $data['media_id'] = $this->resolveMediaId($request, null);
        $data['featured_order'] = $request->filled('featured_order') ? (int) $request->input('featured_order') : null;
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('rg_tourist_spots')->insert($data);
        return redirect('/resort-guru-tourist-spots')->with('success', 'Tourist spot created.');
    }

    public function edit(Request $request)
    {
        $spot = DB::table('rg_tourist_spots')->where('id', (int) $request->input('id'))->first();
        if (!$spot) abort(404);
        return view('resortGuruAdmin.tourist-spots-form', [
            'spot' => $spot,
            'keywords' => $this->keywordOptions(),
            'clusterOptions' => $this->clusterOptions,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $spot = DB::table('rg_tourist_spots')->where('id', $id)->first();
        if (!$spot) abort(404);
        $data = $this->validateData($request, $id);
        $data['slug'] = $spot->slug;  // keep slug stable so external links don't break
        $data['media_id'] = $this->resolveMediaId($request, $spot->media_id);
        $data['featured_order'] = $request->filled('featured_order') ? (int) $request->input('featured_order') : null;
        $data['updated_at'] = now();
        DB::table('rg_tourist_spots')->where('id', $id)->update($data);
        return redirect('/resort-guru-tourist-spots')->with('success', 'Tourist spot updated.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_tourist_spots')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function validateData(Request $request, ?int $editingId = null): array
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'location'        => 'nullable|string|max:255',
            'region_label'    => 'nullable|string|max:120',
            'cluster_tag'     => 'nullable|string|max:64',
            'destination_key' => 'nullable|string|max:80',
            'keyword_id'      => 'nullable|integer|exists:rg_keywords,id',
            'description'     => 'nullable|string',
            'featured_order'  => 'nullable|integer|min:0|max:9999',
            'status'          => 'required|in:draft,published',
            'media_id_pick'   => 'nullable|integer|exists:rg_media,id',
            'image_upload'    => 'nullable|image|max:8192',
        ]);

        return [
            'name'            => trim($request->input('name')),
            'location'        => $request->input('location'),
            'region_label'    => $request->input('region_label'),
            'cluster_tag'     => $request->input('cluster_tag'),
            'destination_key' => $request->input('destination_key'),
            'keyword_id'      => $request->filled('keyword_id') ? (int) $request->input('keyword_id') : null,
            'description'     => $request->input('description'),
            'status'          => $request->input('status'),
        ];
    }

    private function ensureUniqueSlug(string $name, ?string $destKey = null): string
    {
        $prefix = $destKey ? Str::slug($destKey) . '-' : '';
        $base = substr($prefix . Str::slug($name), 0, 180);
        $slug = $base;
        $n = 2;
        while (DB::table('rg_tourist_spots')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /**
     * Image handling — admin can either pick an existing rg_media row or
     * upload a new file (which creates a new rg_media row).
     */
    private function resolveMediaId(Request $request, $current)
    {
        if ($request->hasFile('image_upload')) {
            $file = $request->file('image_upload');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = 'spot-' . time() . '-' . Str::random(6) . '.' . $ext;
            $relativePath = 'rg-media/spots/' . $filename;
            $absolutePath = $this->frontendStoragePath($relativePath);

            $dir = dirname($absolutePath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $file->move($dir, $filename);

            $size = is_file($absolutePath) ? filesize($absolutePath) : $file->getSize();
            [$w, $h] = @getimagesize($absolutePath) ?: [null, null];

            return DB::table('rg_media')->insertGetId([
                'filename'   => $filename,
                'path'       => $relativePath,
                'mime'       => $file->getClientMimeType() ?: 'image/jpeg',
                'size_bytes' => $size,
                'kind'       => 'image',
                'width'      => $w,
                'height'     => $h,
                'alt'        => $request->input('name'),
                'caption'    => $request->input('name'),
                'source'     => 'admin-upload-tourist-spot',
                'credit'     => null,
                'source_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($request->filled('media_id_pick')) {
            return (int) $request->input('media_id_pick');
        }

        return $current;  // leave unchanged
    }

    /**
     * The frontend app owns the storage/app/public dir, but this controller
     * runs in the mother app. Compute the absolute path to the FRONTEND's
     * storage so uploads land where the frontend expects them. Falls back to
     * mother-app storage if the config isn't set.
     */
    private function frontendStoragePath(string $relative): string
    {
        $cfg = config('services.resort_guru.frontend_storage_path');
        if ($cfg) return rtrim($cfg, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        return storage_path('app/public/' . $relative);
    }

    private function keywordOptions()
    {
        return DB::table('rg_keywords')
            ->orderBy('phrase')
            ->get(['id', 'phrase', 'cluster_tag']);
    }

    private function mediaOptions()
    {
        return DB::table('rg_media')
            ->where('kind', 'image')
            ->where(function ($q) {
                $q->where('path', 'like', 'rg-media/spots/%')
                  ->orWhere('path', 'like', 'rg-media/destinations/%');
            })
            ->orderByDesc('id')
            ->limit(500)
            ->get(['id', 'path', 'filename', 'alt']);
    }
}
