<?php

namespace App\Http\Controllers;

/**
 * Serves runtime uploads where public/storage cannot.
 *
 * Uploads live on a mounted volume now — this app keeps AniSystem's media as
 * well as its own — and the symlink at public/storage cannot follow a mount
 * that appears at boot. The web server still serves anything that does exist
 * under public/storage without touching PHP; only the misses fall through
 * here, which streams the file from wherever the public disk actually is.
 */
class StorageFallbackController extends Controller
{
    public function __invoke(string $path)
    {
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
}
