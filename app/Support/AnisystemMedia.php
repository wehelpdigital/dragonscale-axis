<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * The public address of a file AniSystem stored, from this side.
 *
 * AniSystem writes uploads here when the mother app is configured -- its
 * container loses its disk on every deploy -- and marks those paths with an
 * `mm:` prefix so it knows whose /storage to build a URL against. From here
 * the prefix means the opposite: a marked path is ours to serve, and an
 * unmarked one belongs to AniSystem's own disk.
 *
 * One place that knows the difference, so no screen has to guess.
 */
class AnisystemMedia
{
    public const REMOTE_PREFIX = 'mm:';

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, self::REMOTE_PREFIX)) {
            $kept = ltrim(substr($path, strlen(self::REMOTE_PREFIX)), '/');
            // A copy of this app that shares the database but not the disk
            // can be told whose /storage to read. See config/anisystem.php.
            $base = (string) config('anisystem.media_base');

            return $base !== ''
                ? $base . '/storage/' . $kept
                : Storage::disk('public')->url($kept);
        }

        // Where the client app's own files are served from, which is not
        // necessarily where the client app is. See config/anisystem.php.
        $base = (string) config('anisystem.client_media_base')
            ?: rtrim((string) config('anisystem.url'), '/');

        return $base . '/storage/' . ltrim($path, '/');
    }

    /** The file's own name, for a caption when nothing else was written. */
    public static function basename(?string $path): string
    {
        if (blank($path)) {
            return '';
        }

        return basename(str_replace('\\', '/', str_replace(self::REMOTE_PREFIX, '', $path)));
    }

    /** Images are shown; a clip has to be played. */
    public static function isVideo(?string $path): bool
    {
        $ext = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

        return in_array($ext, ['mp4', 'webm', 'mov', 'm4v', 'ogg'], true);
    }

    /** True when the file is one this app is holding. */
    public static function isOurs(?string $path): bool
    {
        return $path !== null && str_starts_with($path, self::REMOTE_PREFIX);
    }
}
