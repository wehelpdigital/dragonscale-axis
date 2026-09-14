<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Every `/storage/<path>` address this app or AniSystem ever handed out,
 * answered from wherever the file actually lives now.
 *
 * On a host with a mounted volume the public disk is a directory and the
 * file is streamed from it. On a bucket (MEDIA_DISK=s3) the browser is sent
 * on to the object itself -- a redirect costs one round trip and keeps every
 * old link, every asset('storage/...') in this app and every mm: path in
 * AniSystem's database working without a rewrite.
 */
class StorageFallbackController extends Controller
{
    public function __invoke(string $path)
    {
        if (config('filesystems.disks.public.driver') === 's3') {
            return $this->fromBucket($path);
        }

        $base = realpath(config('filesystems.disks.public.root', storage_path('app/public')));

        // realpath resolves any ../ tricks; anything that escapes the public
        // disk — or points at nothing — is a plain 404, same as a bad URL.
        $full = $base ? realpath($base . DIRECTORY_SEPARATOR . $path) : false;
        if ($full === false || ! str_starts_with($full, $base . DIRECTORY_SEPARATOR) || ! is_file($full)) {
            abort(404);
        }

        // Uploads get random names, so a URL's content never changes — cache hard.
        //
        // And they are readable cross-origin on purpose: AniSystem loads a
        // saved drawing into a canvas to edit it, and a canvas that drew an
        // image without CORS permission cannot be exported afterwards. These
        // are public files either way; the header only says so.
        return response()->file($full, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }

    /**
     * A public bucket has a plain address for every object and the redirect
     * can be remembered for a year. A private one gets a signed link that
     * lasts a couple of hours -- long enough for any page, short enough that
     * a copied URL is not a permanent door.
     */
    private function fromBucket(string $path)
    {
        $path = ltrim(str_replace(chr(92), '/', $path), '/');
        if ($path === '' || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (config('filesystems.disks.public.url')) {
            return redirect()->away($disk->url($path), 301, [
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        if (! $disk->exists($path)) {
            abort(404);
        }

        return redirect()->away($disk->temporaryUrl($path, now()->addHours(2)), 302, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
