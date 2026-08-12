<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsEmailTemplate;
use App\Support\EmailBlocks;
use Illuminate\Http\Request;

/**
 * The drag-and-drop editor for AniSystem's email layouts.
 *
 * The Mail Settings screen next door edits `bodyHtml` by hand, which is fine
 * for someone who writes HTML and hopeless for everyone else. This edits the
 * same templates as blocks, renders them to that same `bodyHtml`, and keeps
 * the blocks alongside so the layout can be reopened and rearranged rather
 * than becoming hand-edited HTML forever.
 */
class AniSensoEmailBuilderController extends Controller
{
    public function index()
    {
        $templates = AsEmailTemplate::active()
            ->where('groupKey', 'AniSystem')
            ->orderBy('templateName')
            ->get();

        return view('aniSensoAdmin.email-builder.index', compact('templates'));
    }

    public function edit($id)
    {
        $template = AsEmailTemplate::active()->findOrFail($id);

        return view('aniSensoAdmin.email-builder.edit', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = AsEmailTemplate::active()->findOrFail($id);

        $data = $request->validate([
            'templateName' => 'required|string|max:150',
            'subject' => 'required|string|max:255',
            'blocks' => 'nullable|string',
        ]);

        $blocks = $this->cleanBlocks($data['blocks'] ?? '[]');

        $template->templateName = $data['templateName'];
        $template->subject = $data['subject'];
        $template->blocks = $blocks;
        // What actually gets sent. Rendered here, once, so the sending app
        // never has to know what a block is.
        $template->bodyHtml = EmailBlocks::wrap(EmailBlocks::render($blocks), '{{app_name}}');
        $template->save();

        return redirect()
            ->route('anisenso-email-builder.edit', $template->id)
            ->with('success', 'Layout saved. The next email uses it.');
    }

    /**
     * Keep only what the renderer draws, with only the fields each kind uses.
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
        foreach (array_slice($raw, 0, 60) as $b) {
            $kind = is_array($b) ? (string) ($b['kind'] ?? '') : '';
            if (! array_key_exists($kind, EmailBlocks::KINDS)) {
                continue;
            }

            $keep = ['kind' => $kind];
            if ($kind === 'tips') {
                $items = array_values(array_filter(
                    array_map(fn ($i) => trim(mb_substr((string) $i, 0, 300)), (array) ($b['items'] ?? [])),
                    fn ($i) => $i !== ''
                ));
                if (! $items) {
                    continue;
                }
                $keep['items'] = array_slice($items, 0, 20);
            } elseif ($kind === 'button') {
                $url = trim((string) ($b['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $keep['url'] = mb_substr($url, 0, 600);
                $keep['text'] = mb_substr(trim((string) ($b['text'] ?? 'Open')), 0, 60);
            } elseif ($kind === 'callout') {
                $keep['title'] = mb_substr(trim((string) ($b['title'] ?? '')), 0, 120);
                $keep['text'] = mb_substr(trim((string) ($b['text'] ?? '')), 0, 2000);
                if ($keep['text'] === '') {
                    continue;
                }
            } elseif (! in_array($kind, ['divider', 'spacer', 'activities'], true)) {
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
