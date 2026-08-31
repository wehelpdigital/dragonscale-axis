<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The client's Gallery module, from this side.
 *
 * Every picture the season produced, plus the albums a grower puts them into
 * on purpose. Same two tables the farmer app writes, so a caption fixed here
 * is the caption they see.
 *
 * A picture added from here is kept on THIS app's disk and marked with the
 * `mm:` prefix, which is the same arrangement the farmer app already uses for
 * its own uploads — its container loses the disk on every deploy, so the
 * durable copy has always lived over here.
 */
class GalleryController extends BaseScheduleController
{
    private const MAX_BYTES = 20_000_000;

    /** Albums and pictures for one season. */
    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $albums = DB::table('as_gallery_albums')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')->orderBy('id')
            ->get(['id', 'title', 'description'])
            ->map(fn ($a) => [
                'id' => (int) $a->id,
                'title' => (string) $a->title,
                'description' => (string) ($a->description ?? ''),
            ])->values();

        $images = DB::table('as_gallery_images')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderByDesc('id')
            ->limit(600)
            ->get()
            ->map(fn ($g) => [
                'id' => (int) $g->id,
                'albumId' => (int) ($g->albumId ?? 0),
                'caption' => (string) ($g->caption ?? ''),
                'description' => (string) ($g->description ?? ''),
                'isTeam' => (int) ($g->isTeam ?? 0),
                'url' => AnisystemMedia::url((string) $g->path),
                'name' => AnisystemMedia::basename((string) $g->path),
                // A farmer's gallery holds clips as well as photographs, and
                // a clip drawn as an <img> is a grey box for ever.
                'isVideo' => AnisystemMedia::isVideo((string) $g->path),
                'when' => (string) ($g->created_at ?? ''),
            ])->values();

        return $this->jsonOk('OK', ['albums' => $albums, 'images' => $images]);
    }

    // ------------------------------------------------------------ albums --

    public function albumSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $title = trim((string) $request->input('title', ''));
        if ($title === '') {
            return $this->jsonFail('An album needs a name.', 422);
        }

        $id = (int) $request->input('id', 0);
        $payload = [
            'title' => mb_substr($title, 0, 191),
            'description' => (string) $request->input('description', ''),
            'updated_at' => now(),
        ];

        if ($id) {
            $hit = DB::table('as_gallery_albums')
                ->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->update($payload);

            return $hit ? $this->jsonOk('Album saved.', ['id' => $id]) : $this->jsonFail('That album is gone.', 404);
        }

        $id = DB::table('as_gallery_albums')->insertGetId($payload + [
            'croppingScheduleId' => $schedule->id,
            'userId' => $schedule->anisystemUserId,
            'sortOrder' => 0,
            'deleteStatus' => 1,
            'created_at' => now(),
        ]);

        return $this->jsonOk('Album added.', ['id' => $id]);
    }

    /**
     * An album with pictures in it is not removed by accident: either say
     * where they go, or say to take them too. The farmer app asks the same
     * three questions in the same order.
     */
    public function albumDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $album = DB::table('as_gallery_albums')
            ->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->first();
        if (! $album) {
            return $this->jsonFail('That album is gone.', 404);
        }

        $moveTo = (int) $request->input('moveTo', 0);
        $target = $moveTo
            ? DB::table('as_gallery_albums')->where('id', $moveTo)
                ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->where('id', '!=', $id)->first()
            : null;

        $held = DB::table('as_gallery_images')->where('albumId', $id)->where('deleteStatus', 1);

        if ($target) {
            (clone $held)->update(['albumId' => $target->id, 'updated_at' => now()]);
        } elseif ($request->boolean('withImages')) {
            (clone $held)->update(['deleteStatus' => 0, 'updated_at' => now()]);
        } elseif ((clone $held)->exists()) {
            return $this->jsonFail('That album still has pictures. Move them somewhere, or say to delete them too.', 422);
        }

        DB::table('as_gallery_albums')->where('id', $id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $this->jsonOk('Album removed.');
    }

    // ----------------------------------------------------------- pictures --

    public function imageUpdate(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $payload = ['updated_at' => now()];

        if ($request->has('caption')) {
            $payload['caption'] = mb_substr(trim((string) $request->input('caption')), 0, 191);
        }
        if ($request->has('description')) {
            $payload['description'] = (string) $request->input('description');
        }
        if ($request->has('albumId')) {
            $albumId = (int) $request->input('albumId');
            // 0 is "loose in the gallery", which is what an album-less
            // picture already holds. Anything else has to be this season's.
            if ($albumId && ! DB::table('as_gallery_albums')->where('id', $albumId)
                ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->exists()) {
                return $this->jsonFail('That album is not on this season.', 422);
            }
            $payload['albumId'] = $albumId;
        }

        $hit = DB::table('as_gallery_images')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->update($payload);

        return $hit ? $this->jsonOk('Picture saved.') : $this->jsonFail('That picture is gone.', 404);
    }

    public function imageDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $hit = DB::table('as_gallery_images')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Picture removed.') : $this->jsonFail('That picture is gone.', 404);
    }

    /** Pictures added from the admin side, kept where the client's own are. */
    public function imageStore(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $files = $request->file('files', []);
        $files = is_array($files) ? $files : [$files];
        $files = array_filter($files);
        if (! $files) {
            return $this->jsonFail('No files in the request.', 422);
        }

        $albumId = (int) $request->input('albumId', 0);
        if ($albumId && ! DB::table('as_gallery_albums')->where('id', $albumId)
            ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->exists()) {
            return $this->jsonFail('That album is not on this season.', 422);
        }

        $made = 0;
        foreach ($files as $file) {
            if (! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }
            // The same shelf the intake API writes to, so one season's files
            // stay together whichever side put them there.
            $path = 'anisystem/schedule-notes/' . $schedule->id . '/' . Str::random(24) . '.' . $ext;
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

            DB::table('as_gallery_images')->insert([
                'albumId' => $albumId,
                'croppingScheduleId' => $schedule->id,
                'userId' => $schedule->anisystemUserId,
                'path' => AnisystemMedia::REMOTE_PREFIX . $path,
                'caption' => mb_substr((string) $request->input('caption', ''), 0, 191),
                'description' => '',
                'isTeam' => 0,
                'sortOrder' => 0,
                'deleteStatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $made++;
        }

        return $made
            ? $this->jsonOk($made === 1 ? 'Picture added.' : "{$made} pictures added.", ['added' => $made])
            : $this->jsonFail('Nothing in that upload could be kept.', 422);
    }
}
