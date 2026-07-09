<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use App\Models\RgMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RgMediaController extends Controller
{
    /**
     * Build a public URL for an rg_media row. Files live on the frontend app,
     * not this admin app, so we route through the configured frontend base URL.
     * Absolute http(s) paths in rg_media.path are returned as-is.
     */
    public static function mediaUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) return $path;
        return \App\Support\RgFrontend::urlFor('storage/' . ltrim($path, '/'));
    }

    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_media')) {
            return view('resortGuruAdmin.pending', ['title' => 'Media Library']);
        }
        $kind = $request->input('kind');
        $source = $request->input('source');
        $q = trim((string) $request->input('q', ''));

        $query = DB::table('rg_media')->orderByDesc('id');
        if ($kind) $query->where('kind', $kind);
        if ($source) $query->where('source', $source);
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('filename', 'LIKE', "%$q%")
                  ->orWhere('alt', 'LIKE', "%$q%")
                  ->orWhere('caption', 'LIKE', "%$q%")
                  ->orWhere('credit', 'LIKE', "%$q%");
            });
        }
        $media = $query->paginate(48)->withQueryString();

        $stats = [
            'total' => DB::table('rg_media')->count(),
            'images' => DB::table('rg_media')->where('kind', 'image')->count(),
            'videos' => DB::table('rg_media')->where('kind', 'video')->count(),
            'size_mb' => round((int) DB::table('rg_media')->sum('size_bytes') / 1024 / 1024, 2),
        ];
        $sources = DB::table('rg_media')->select('source', DB::raw('COUNT(*) as c'))
            ->groupBy('source')->orderByDesc('c')->get();

        return view('resortGuruAdmin.media-library', compact('media', 'stats', 'sources', 'kind', 'source', 'q'));
    }

    public function show(Request $request)
    {
        $id = (int) $request->input('id');
        $item = DB::table('rg_media')->where('id', $id)->first();
        if (!$item) abort(404);
        // Where this image is used
        $usedIn = DB::table('rg_content_blocks')
            ->where('block_type', 'image')
            ->where('payload_json', 'LIKE', '%' . addslashes($item->path) . '%')
            ->get(['id', 'owner_type', 'owner_id', 'sort_order']);
        return view('resortGuruAdmin.media-show', compact('item', 'usedIn'));
    }

    public function upload(Request $request)
    {
        $request->validate(['file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mov']);
        $file = $request->file('file');
        $folder = 'rg-media/uploads/' . date('Y/m');
        $path = $file->store($folder, 'public');
        $kind = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';

        $width = null; $height = null;
        if ($kind === 'image') {
            try {
                $info = @getimagesize($file->getRealPath());
                if ($info) { $width = $info[0]; $height = $info[1]; }
            } catch (\Throwable $e) {}
        }

        $id = DB::table('rg_media')->insertGetId([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'kind' => $kind,
            'width' => $width,
            'height' => $height,
            'alt' => (string) $request->input('alt', ''),
            'caption' => (string) $request->input('caption', ''),
            'source' => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'id' => $id,
            'path' => $path,
            'url' => Storage::url($path),
            'kind' => $kind,
        ]);
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        $item = DB::table('rg_media')->where('id', $id)->first();
        if (!$item) abort(404);
        try { Storage::disk('public')->delete($item->path); } catch (\Throwable $e) {}
        DB::table('rg_media')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function updateMeta(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate([
            'alt' => 'nullable|string|max:500',
            'caption' => 'nullable|string|max:500',
            'credit' => 'nullable|string|max:500',
        ]);
        $data['updated_at'] = now();
        DB::table('rg_media')->where('id', $id)->update($data);
        return response()->json(['ok' => true]);
    }
}
