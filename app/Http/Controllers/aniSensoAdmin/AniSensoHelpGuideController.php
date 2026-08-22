<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsTutorialPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The "How to use" page builder.
 *
 * Distinct from the Tutorials video library next door: those are YouTube
 * guides with a page of their own, these are the in-app help pages behind the
 * question mark in every AniSystem module header — one per module, per device.
 *
 * The editor drags blocks rather than editing HTML, so a page can never reach
 * the app in a shape the app cannot draw, and whoever writes one does not have
 * to know any markup.
 */
class AniSensoHelpGuideController extends Controller
{
    public function index(Request $request)
    {
        // ?module=&device= opens one guide; without them, the list.
        if ($request->filled('module') && $request->filled('device')) {
            return $this->edit($request, (string) $request->query('module'), (string) $request->query('device'));
        }
        $rows = AsTutorialPage::active()->get()->keyBy(fn ($p) => $p->moduleKey . '|' . $p->device);

        return view('aniSensoAdmin.help-guides.index', compact('rows'));
    }

    public function edit(Request $request, string $module, string $device)
    {
        $this->guard($module, $device);

        // Writing a page that does not exist yet is the same job as editing one
        // that does, so an empty draft stands in until it is saved.
        $page = AsTutorialPage::active()
            ->where('moduleKey', $module)->where('device', $device)->first()
            ?: new AsTutorialPage([
                'moduleKey' => $module,
                'device' => $device,
                'title' => 'How to use ' . AsTutorialPage::moduleLabel($module),
                'blocks' => [],
            ]);

        // What the other two devices say, to copy from rather than retype.
        $siblings = AsTutorialPage::active()
            ->where('moduleKey', $module)->where('device', '!=', $device)
            ->get();

        return view('aniSensoAdmin.help-guides.edit', compact('page', 'module', 'device', 'siblings'));
    }

    public function update(Request $request, string $module, string $device)
    {
        $this->guard($module, $device);

        $data = $request->validate([
            'title' => 'required|string|max:191',
            'summary' => 'nullable|string|max:400',
            'blocks' => 'nullable|string',          // JSON from the builder
        ]);

        AsTutorialPage::updateOrCreate(
            ['moduleKey' => $module, 'device' => $device],
            [
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'blocks' => $this->cleanBlocks($data['blocks'] ?? '[]'),
                'updatedByUserId' => Auth::id(),
                'deleteStatus' => 1,
            ]
        );

        return redirect()
            ->route('anisenso-help-guides.edit', ['module' => $module, 'device' => $device])
            ->with('success', 'Guide saved. It is live in AniSystem now.');
    }

    /** Copy another device's blocks over this one, as a starting point. */
    public function copyFrom(Request $request, string $module, string $device)
    {
        $this->guard($module, $device);
        $from = (string) $request->input('from');
        abort_unless(in_array($from, AsTutorialPage::DEVICES, true), 404);

        $source = AsTutorialPage::active()
            ->where('moduleKey', $module)->where('device', $from)->first();
        if (! $source) {
            return back()->with('error', 'That device has no guide to copy yet.');
        }

        AsTutorialPage::updateOrCreate(
            ['moduleKey' => $module, 'device' => $device],
            [
                'title' => $source->title,
                'summary' => $source->summary,
                'blocks' => $source->blocks,
                'updatedByUserId' => Auth::id(),
                'deleteStatus' => 1,
            ]
        );

        return back()->with('success', 'Copied from the ' . AsTutorialPage::DEVICE_LABELS[$from] . ' guide — edit away.');
    }

    public function destroy(string $module, string $device)
    {
        $this->guard($module, $device);
        AsTutorialPage::where('moduleKey', $module)->where('device', $device)
            ->update(['deleteStatus' => 0]);

        return redirect()->route('anisenso-help-guides.index')
            ->with('success', 'Guide removed. AniSystem falls back to another device\'s page.');
    }

    private function guard(string $module, string $device): void
    {
        abort_unless(array_key_exists($module, AsTutorialPage::MODULES), 404);
        abort_unless(in_array($device, AsTutorialPage::DEVICES, true), 404);
    }

    /**
     * Keep only blocks the app knows how to draw, with only the fields that
     * kind uses. The builder is trusted to be well-meaning, not to be correct —
     * and a block with junk in it would be junk on someone's help page.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cleanBlocks(string $json): array
    {
        $raw = json_decode($json, true);
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach (array_slice($raw, 0, 80) as $b) {
            $kind = is_array($b) ? (string) ($b['kind'] ?? '') : '';
            if (! array_key_exists($kind, AsTutorialPage::BLOCK_KINDS)) {
                continue;
            }

            $keep = ['kind' => $kind];
            if (in_array($kind, ['steps', 'tips'], true)) {
                $items = array_values(array_filter(
                    array_map(fn ($i) => trim(mb_substr((string) $i, 0, 300)), (array) ($b['items'] ?? [])),
                    fn ($i) => $i !== ''
                ));
                if (! $items) {
                    continue;
                }
                $keep['items'] = array_slice($items, 0, 30);
            } elseif ($kind === 'image' || $kind === 'video') {
                $url = trim((string) ($b['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $keep['url'] = mb_substr($url, 0, 600);
                if ($kind === 'image') {
                    $keep['caption'] = mb_substr(trim((string) ($b['caption'] ?? '')), 0, 200);
                }
            } elseif ($kind === 'callout') {
                $keep['tone'] = in_array($b['tone'] ?? '', ['warn', 'good'], true) ? $b['tone'] : 'note';
                $keep['title'] = mb_substr(trim((string) ($b['title'] ?? '')), 0, 120);
                $keep['text'] = mb_substr(trim((string) ($b['text'] ?? '')), 0, 2000);
                if ($keep['text'] === '') {
                    continue;
                }
            } elseif ($kind !== 'divider') {
                $text = trim((string) ($b['text'] ?? ''));
                if ($text === '') {
                    continue;
                }
                $keep['text'] = mb_substr($text, 0, 4000);
            }

            $out[] = $keep;
        }

        return $out;
    }
}
