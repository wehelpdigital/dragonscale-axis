<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use App\Models\RgContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RgBlocksController extends Controller
{
    private const ALLOWED_OWNERS = ['seo_page', 'blog_post', 'static_page', 'homepage'];

    /**
     * Focused single-block editor used by the Live Editor modal.
     * Looks up the block, then renders the block builder partial
     * with focusBlockId set so the partial filters state.blocks to
     * just this one and hides the add-block toolbar. The minimal
     * layout (master-without-nav) strips the sidebar / breadcrumb
     * so the iframe inside the modal feels like a focused edit panel,
     * not a whole admin page.
     */
    public function editSingle(Request $request)
    {
        $blockId = (int) $request->input('id');
        $block = RgContentBlock::find($blockId);
        if (!$block) abort(404, 'Block not found');

        return view('resortGuruAdmin.blocks.edit-single', [
            'block' => $block,
            'ownerType' => $block->owner_type,
            'ownerId' => $block->owner_id,
            'focusBlockId' => $block->id,
        ]);
    }

    public function list(Request $request)
    {
        $ownerType = $this->validatedOwnerType($request);
        $ownerId = (int) $request->input('owner_id');
        $blocks = RgContentBlock::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->orderBy('sort_order')
            ->get(['id', 'block_type', 'payload_json', 'sort_order']);
        return response()->json([
            'ok' => true,
            'blocks' => $blocks->map(fn($b) => [
                'id' => $b->id,
                'type' => $b->block_type,
                'payload' => json_decode($b->payload_json, true) ?: [],
                'sort_order' => $b->sort_order,
            ])->values(),
        ]);
    }

    public function save(Request $request)
    {
        $ownerType = $this->validatedOwnerType($request);
        $ownerId = (int) $request->input('owner_id');
        $blockType = (string) $request->input('block_type');
        $payload = $request->input('payload');

        if (!in_array($blockType, RgContentBlock::ALLOWED_TYPES, true)) {
            return response()->json(['ok' => false, 'message' => 'Invalid block type'], 422);
        }
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($payload)) $payload = [];

        $id = (int) $request->input('id');
        if ($id > 0) {
            $block = RgContentBlock::find($id);
            if (!$block) return response()->json(['ok' => false, 'message' => 'Block not found'], 404);
            $block->payload_json = json_encode($payload);
            $block->save();
        } else {
            $maxOrder = (int) RgContentBlock::where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)->max('sort_order');
            $block = RgContentBlock::create([
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'sort_order' => $maxOrder + 1,
                'block_type' => $blockType,
                'payload_json' => json_encode($payload),
            ]);
        }
        return response()->json([
            'ok' => true,
            'block' => [
                'id' => $block->id,
                'type' => $block->block_type,
                'payload' => json_decode($block->payload_json, true) ?: [],
                'sort_order' => $block->sort_order,
            ],
        ]);
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        RgContentBlock::where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        DB::transaction(function () use ($ids) {
            foreach ($ids as $order => $id) {
                if (!is_numeric($id)) continue;
                RgContentBlock::where('id', (int) $id)->update(['sort_order' => $order + 1]);
            }
        });
        return response()->json(['ok' => true]);
    }

    /**
     * Accept a single uploaded file from the builder and:
     *   1. Persist it to the FRONTEND app's public storage path (NOT the
     *      mother's storage — the frontend serves /storage and that is
     *      what the block URLs point at). The target root is taken from
     *      services.resort_guru.frontend_storage_path config, with a
     *      sensible XAMPP default so the dev setup works without env.
     *   2. Register the file in rg_media so the media-library picker
     *      can find it later. Idempotent on path.
     */
    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mov',
        ]);
        $file = $request->file('file');
        $folder = 'rg-blocks/' . date('Y/m');
        $original = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = $this->safeFilename($original, $ext);
        $relPath = $folder . '/' . $filename;

        // Resolve the MIME type BEFORE moving the temp upload. After
        // move_uploaded_file() the temp file no longer exists, so
        // getMimeType() throws ("Unable to guess the MIME type") and the
        // whole upload 500s. Guard it and fall back to the extension.
        $mime = '';
        try {
            $mime = (string) ($file->getMimeType() ?: '');
        } catch (\Throwable $e) {
            $mime = '';
        }
        if ($mime === '') {
            $mime = in_array($ext, ['mp4', 'webm', 'mov'], true)
                ? 'video/' . $ext
                : 'image/' . ($ext === 'jpg' ? 'jpeg' : ($ext ?: 'jpeg'));
        }
        $kind = str_starts_with($mime, 'video/') ? 'video' : 'image';

        $rootDir = $this->frontendStorageRoot();
        $targetDir = rtrim($rootDir, "/\\") . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $folder);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
        if (!@move_uploaded_file($file->getRealPath(), $targetPath)) {
            // Fallback to copy + unlink for non-uploaded files (rare).
            copy($file->getRealPath(), $targetPath);
        }

        $size = filesize($targetPath) ?: 0;

        $dimensions = ['width' => null, 'height' => null];
        if ($kind === 'image' && function_exists('getimagesize')) {
            $info = @getimagesize($targetPath);
            if ($info) { $dimensions = ['width' => $info[0], 'height' => $info[1]]; }
        }

        $url = '/storage/' . $relPath;

        // Register in rg_media so the picker library lists it. Skip if a
        // row already exists for this path (idempotent re-uploads).
        if (Schema::hasTable('rg_media')) {
            $existing = DB::table('rg_media')->where('path', $relPath)->first();
            if (!$existing) {
                DB::table('rg_media')->insert([
                    'filename'   => $original,
                    'path'       => $relPath,
                    'mime'       => $mime,
                    'size_bytes' => $size,
                    'kind'       => $kind,
                    'width'      => $dimensions['width'],
                    'height'     => $dimensions['height'],
                    'alt'        => null,
                    'caption'    => null,
                    'source'     => 'builder-upload',
                    'credit'     => null,
                    'source_url' => null,
                    'meta_json'  => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json([
            'ok'   => true,
            'url'  => $url,
            'path' => $relPath,
            'kind' => $kind,
            'size' => $size,
            'name' => $original,
        ]);
    }

    /**
     * Update a single image slot inside a block's payload without
     * opening the editor. Used by the canvas-row "Change" overlay on
     * each thumbnail. The slot index identifies which image in a
     * multi-image block (hero_slider, gallery, attractions, etc.)
     * gets the new URL.
     */
    public function updateImage(Request $request)
    {
        $request->validate([
            'id'   => 'required|integer|exists:rg_content_blocks,id',
            'slot' => 'required|integer|min:0',
            'url'  => 'required|string|max:1024',
        ]);

        $block = RgContentBlock::find((int) $request->input('id'));
        if (!$block) {
            return response()->json(['ok' => false, 'message' => 'Block not found'], 404);
        }
        $payload = json_decode($block->payload_json, true) ?: [];
        $slot = (int) $request->input('slot');
        $url = (string) $request->input('url');

        $updated = $this->applyImageUpdate($block->block_type, $payload, $slot, $url);
        if (!$updated) {
            return response()->json([
                'ok' => false,
                'message' => 'Block type ' . $block->block_type . ' does not support inline image updates.',
            ], 422);
        }

        $block->payload_json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $block->save();

        return response()->json([
            'ok'    => true,
            'block' => [
                'id'         => $block->id,
                'type'       => $block->block_type,
                'payload'    => $payload,
                'sort_order' => $block->sort_order,
            ],
        ]);
    }

    /**
     * Returns true if it mutated $payload to set the image at $slot.
     * Per-block-type slot mapping mirrors the canvas thumbnail order
     * produced by builder.blade.php's blockThumbs() helper.
     */
    private function applyImageUpdate(string $blockType, array &$payload, int $slot, string $url): bool
    {
        switch ($blockType) {
            case 'image':
                $payload['src'] = $url;
                return true;

            case 'image_text_pair':
                $payload['image'] = $url;
                return true;

            case 'hero_slider':
                if (!isset($payload['images']) || !is_array($payload['images'])) $payload['images'] = [];
                if (!isset($payload['images'][$slot])) {
                    $payload['images'][$slot] = ['src' => '', 'alt' => '', 'caption' => ''];
                }
                $payload['images'][$slot]['src'] = $url;
                return true;

            case 'gallery':
                if (!isset($payload['images']) || !is_array($payload['images'])) $payload['images'] = [];
                if (!isset($payload['images'][$slot])) {
                    $payload['images'][$slot] = ['src' => '', 'alt' => ''];
                }
                $payload['images'][$slot]['src'] = $url;
                return true;

            case 'attractions':
                if (!isset($payload['items']) || !is_array($payload['items'])) $payload['items'] = [];
                if (!isset($payload['items'][$slot])) {
                    $payload['items'][$slot] = ['name' => '', 'image' => '', 'short' => '', 'blurb' => '', 'url' => ''];
                }
                $payload['items'][$slot]['image'] = $url;
                return true;

            case 'traveler_reviews':
                if (!isset($payload['items']) || !is_array($payload['items'])) $payload['items'] = [];
                if (!isset($payload['items'][$slot])) {
                    $payload['items'][$slot] = ['name' => '', 'avatar' => '', 'rating' => 5, 'quote' => '', 'date' => ''];
                }
                $payload['items'][$slot]['avatar'] = $url;
                return true;

            case 'custom_html':
                // Replace the Nth <img src="..."> in the HTML. Same order
                // as blockThumbs() finds them.
                $html = (string) ($payload['html'] ?? '');
                $i = 0;
                $payload['html'] = preg_replace_callback(
                    '~(<img\s[^>]*src=)(["\'])([^"\']+)\2~i',
                    function ($m) use (&$i, $slot, $url) {
                        if ($i++ === $slot) {
                            return $m[1] . $m[2] . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . $m[2];
                        }
                        return $m[0];
                    },
                    $html
                );
                return $html !== $payload['html'];

            default:
                return false;
        }
    }

    /**
     * Paginated media list for the picker. Filters: q (filename search),
     * kind (image|video). Returns small thumbnails sized for the grid.
     */
    public function mediaList(Request $request)
    {
        if (!Schema::hasTable('rg_media')) {
            return response()->json(['ok' => true, 'items' => [], 'pages' => 0, 'total' => 0]);
        }
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 24;
        $kind = $request->input('kind');
        $q = trim((string) $request->input('q', ''));

        $query = DB::table('rg_media');
        if ($kind && in_array($kind, ['image', 'video'], true)) {
            $query->where('kind', $kind);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('filename', 'like', '%' . $q . '%')
                  ->orWhere('alt', 'like', '%' . $q . '%')
                  ->orWhere('caption', 'like', '%' . $q . '%')
                  ->orWhere('source', 'like', '%' . $q . '%');
            });
        }
        $total = (clone $query)->count();
        $items = $query
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get(['id', 'filename', 'path', 'kind', 'mime', 'width', 'height', 'alt', 'source']);

        $pages = (int) ceil($total / $perPage);
        return response()->json([
            'ok'     => true,
            'items'  => $items->map(fn($m) => [
                'id'       => $m->id,
                'filename' => $m->filename,
                'url'      => '/storage/' . ltrim($m->path, '/'),
                'kind'     => $m->kind,
                'mime'     => $m->mime,
                'width'    => $m->width,
                'height'   => $m->height,
                'alt'      => $m->alt,
                'source'   => $m->source,
            ])->values(),
            'page'   => $page,
            'pages'  => $pages,
            'total'  => $total,
        ]);
    }

    /**
     * Where the frontend app expects files to live. Defaults to the
     * standard XAMPP-side install; override via .env if the frontend
     * is hosted elsewhere.
     */
    private function frontendStorageRoot(): string
    {
        return config(
            'services.resort_guru.frontend_storage_path',
            'C:\\xampp\\htdocs\\resortguruph\\storage\\app\\public'
        );
    }

    /**
     * Strip path separators + bad chars from a client filename and add a
     * short random suffix so concurrent uploads of the same name don't
     * collide. Keeps the original-ish name for human recognition in the
     * library grid.
     */
    private function safeFilename(string $original, string $extFallback): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME);
        $ext = pathinfo($original, PATHINFO_EXTENSION) ?: $extFallback;
        $base = preg_replace('~[^A-Za-z0-9._-]+~', '-', $base);
        $base = trim($base, '-_');
        if ($base === '') $base = 'upload';
        $base = substr($base, 0, 60);
        $suffix = bin2hex(random_bytes(3));
        return $base . '-' . $suffix . '.' . strtolower($ext);
    }

    private function validatedOwnerType(Request $request): string
    {
        $type = (string) $request->input('owner_type');
        if (!in_array($type, self::ALLOWED_OWNERS, true)) {
            abort(422, 'Invalid owner_type');
        }
        return $type;
    }
}
