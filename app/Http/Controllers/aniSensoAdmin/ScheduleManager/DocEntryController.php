<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The client's Documentation module, from this side.
 *
 * This page's Documentation tab was built around three tables — a protocol
 * document, reference attachments and critical rules — and the farmer app has
 * since replaced two of them with one: a doc ENTRY, typed as a protocol, an
 * introduction, a critical rule, something miscellaneous, or a custom kind
 * under a tag the grower named. Every entry carries rich text and any number
 * of files.
 *
 * Which is why this side saw nothing: the attachments and critical-rules
 * tables are empty across the whole database, and the six real documents on
 * it are entries. They are readable and writable here now.
 *
 * The three old drawers stay. They are empty everywhere, but they are what
 * the Worker Presentation and the Export Schedule render from, and those two
 * are this app's own screens — removing the only way to fill them would take
 * a working feature away to tidy a shelf.
 */
class DocEntryController extends BaseScheduleController
{
    /** Built-in type => label. A custom entry takes its label from its tag. */
    public const TYPES = [
        'protocol' => 'Protocol',
        'introduction' => 'Introduction',
        'critical_rule' => 'Critical Rule',
        'miscellaneous' => 'Miscellaneous',
    ];

    private const MAX_FILES = 12;
    private const MAX_BYTES = 10_000_000;
    private const EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt', 'xls', 'xlsx'];

    public function data(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $tags = DB::table('as_schedule_doc_tags')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn ($t) => ['id' => (int) $t->id, 'name' => (string) $t->name])
            ->values();

        $byId = $tags->keyBy('id');

        $entries = DB::table('as_schedule_doc_entries')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->orderBy('sortOrder')->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(function ($e) use ($byId) {
                $files = json_decode((string) $e->files, true);
                $files = is_array($files) ? $files : [];

                return [
                    'id' => (int) $e->id,
                    'type' => (string) $e->type,
                    'typeLabel' => $e->type === 'custom'
                        ? ($byId[(int) $e->tagId]['name'] ?? 'Custom')
                        : (self::TYPES[$e->type] ?? 'Document'),
                    'tagId' => $e->tagId ? (int) $e->tagId : 0,
                    'title' => (string) ($e->title ?? ''),
                    'content' => (string) ($e->content ?? ''),
                    'files' => array_values(array_map(fn ($f) => [
                        'path' => (string) ($f['path'] ?? ''),
                        'name' => (string) ($f['name'] ?? 'file'),
                        'size' => (int) ($f['size'] ?? 0),
                        'url' => AnisystemMedia::url((string) ($f['path'] ?? '')),
                        'isImage' => str_starts_with((string) ($f['mime'] ?? ''), 'image/'),
                    ], $files)),
                    'when' => (string) ($e->updated_at ?? ''),
                ];
            })->values();

        return $this->jsonOk('OK', ['entries' => $entries, 'tags' => $tags, 'types' => self::TYPES]);
    }

    public function save(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $v = Validator::make($request->all(), [
            'type' => ['required', Rule::in(array_merge(array_keys(self::TYPES), ['custom']))],
            'tagId' => 'nullable|integer',
            'title' => 'nullable|string|max:191',
            'content' => 'nullable|string|max:60000',
            'files' => 'nullable|array|max:' . self::MAX_FILES,
            'files.*' => 'file|max:10240|mimes:' . implode(',', self::EXTS),
            'keepPaths' => 'nullable|array',
            'keepPaths.*' => 'string|max:500',
        ], [
            'files.*.mimes' => 'Allowed files: images, PDF, Word, Excel or TXT.',
            'files.*.max' => 'Each file must be 10 MB or smaller.',
        ]);
        if ($v->fails()) {
            return $this->jsonFail('Validation failed.', 422, ['errors' => $v->errors()]);
        }

        $type = (string) $request->input('type');
        $tagId = $type === 'custom' ? ((int) $request->input('tagId') ?: null) : null;
        if ($type === 'custom' && ! $tagId) {
            return $this->jsonFail('Pick a tag for this document.', 422);
        }
        if ($tagId && ! DB::table('as_schedule_doc_tags')->where('id', $tagId)
            ->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->exists()) {
            return $this->jsonFail('That tag is not on this season.', 422);
        }

        $id = (int) $request->input('id', 0);
        $existing = [];
        if ($id) {
            $row = DB::table('as_schedule_doc_entries')
                ->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)->first();
            if (! $row) {
                return $this->jsonFail('That document is gone.', 404);
            }
            // Keep the files that were not struck off, and let new ones ride
            // along on the same save.
            $keep = (array) $request->input('keepPaths', []);
            $existing = collect(json_decode((string) $row->files, true) ?: [])
                ->filter(fn ($f) => in_array((string) ($f['path'] ?? ''), $keep, true))
                ->values()->all();
        }

        $files = array_merge($existing, $this->storeUploads($request, $schedule->id));
        $title = trim((string) $request->input('title')) ?: null;
        $content = (string) $request->input('content', '');
        $content = trim(strip_tags($content)) !== '' ? $content : null;

        // An entry has to carry something — words, a title, or a file.
        if (! $title && ! $content && ! $files) {
            return $this->jsonFail('Add some text or attach a file.', 422);
        }

        $payload = [
            'type' => $type,
            'tagId' => $tagId,
            'title' => $title,
            'content' => $content,
            'files' => $files ? json_encode($files) : null,
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('as_schedule_doc_entries')->where('id', $id)->update($payload);

            return $this->jsonOk('Document saved.', ['id' => $id]);
        }

        $id = DB::table('as_schedule_doc_entries')->insertGetId($payload + [
            'croppingScheduleId' => $schedule->id,
            'sortOrder' => 0,
            'deleteStatus' => 1,
            'created_at' => now(),
        ]);

        return $this->jsonOk('Document added.', ['id' => $id]);
    }

    public function destroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $hit = DB::table('as_schedule_doc_entries')
            ->where('id', $this->queryId($request))
            ->where('croppingScheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Document removed.') : $this->jsonFail('That document is gone.', 404);
    }

    // ------------------------------------------------------------- tags --

    public function tagSave(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return $this->jsonFail('A tag needs a name.', 422);
        }

        $id = (int) $request->input('id', 0);
        if ($id) {
            $hit = DB::table('as_schedule_doc_tags')
                ->where('id', $id)->where('croppingScheduleId', $schedule->id)->where('deleteStatus', 1)
                ->update(['name' => mb_substr($name, 0, 191), 'updated_at' => now()]);

            return $hit ? $this->jsonOk('Tag saved.', ['id' => $id]) : $this->jsonFail('That tag is gone.', 404);
        }

        $id = DB::table('as_schedule_doc_tags')->insertGetId([
            'croppingScheduleId' => $schedule->id,
            'name' => mb_substr($name, 0, 191),
            'sortOrder' => 0,
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->jsonOk('Tag added.', ['id' => $id]);
    }

    /** A tag still on a document is not removed: the label would vanish. */
    public function tagDestroy(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);
        $id = $this->queryId($request);

        $inUse = DB::table('as_schedule_doc_entries')
            ->where('croppingScheduleId', $schedule->id)
            ->where('deleteStatus', 1)
            ->where('tagId', $id)
            ->exists();
        if ($inUse) {
            return $this->jsonFail('That tag is still on a document. Move those documents first.', 422);
        }

        $hit = DB::table('as_schedule_doc_tags')
            ->where('id', $id)->where('croppingScheduleId', $schedule->id)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return $hit ? $this->jsonOk('Tag removed.') : $this->jsonFail('That tag is gone.', 404);
    }

    /**
     * Keep any uploaded files and describe them.
     *
     * They go on THIS app's disk under the `mm:` prefix, which is where the
     * farmer app's own uploads already live: its container loses the disk on
     * every deploy, so the durable copy has always been over here.
     *
     * @return array<int, array<string, mixed>>
     */
    private function storeUploads(Request $request, int $scheduleId): array
    {
        if (! $request->hasFile('files')) {
            return [];
        }

        $out = [];
        foreach ((array) $request->file('files') as $file) {
            if (! $file || ! $file->isValid() || $file->getSize() > self::MAX_BYTES) {
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension() ?: '');
            if (! in_array($ext, self::EXTS, true)) {
                continue;
            }
            $path = 'anisystem/schedule-doc-entries/' . $scheduleId . '/' . Str::random(24) . '.' . $ext;
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

            $out[] = [
                'path' => AnisystemMedia::REMOTE_PREFIX . $path,
                'name' => $file->getClientOriginalName() ?: ('file.' . $ext),
                'size' => (int) $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        return $out;
    }
}
