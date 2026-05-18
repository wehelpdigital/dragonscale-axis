<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use App\Models\RgContentBlock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RgBlocksController extends Controller
{
    private const ALLOWED_OWNERS = ['seo_page', 'blog_post', 'static_page', 'homepage'];

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

    public function uploadMedia(Request $request)
    {
        $request->validate(['file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mov']);
        $file = $request->file('file');
        $folder = 'rg-blocks/' . date('Y/m');
        $path = $file->store($folder, 'public');
        $url = Storage::url($path);
        $kind = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
        return response()->json([
            'ok' => true,
            'url' => $url,
            'path' => $path,
            'kind' => $kind,
            'size' => $file->getSize(),
            'name' => $file->getClientOriginalName(),
        ]);
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
