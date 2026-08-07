<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsTutorial;
use Illuminate\Http\Request;

/**
 * Tutorial video library management. The team curates YouTube guides here; they
 * appear on the AniSystem Tutorials page.
 */
class AniSensoTutorialController extends Controller
{
    public function index()
    {
        $tutorials = AsTutorial::active()
            ->orderBy('sortOrder')
            ->orderByDesc('id')
            ->paginate(30);

        return view('aniSensoAdmin.tutorials.index', compact('tutorials'));
    }

    public function create()
    {
        return view('aniSensoAdmin.tutorials.form', ['tutorial' => new AsTutorial(), 'mode' => 'create']);
    }

    public function edit($id)
    {
        $tutorial = AsTutorial::active()->where('id', $id)->firstOrFail();

        return view('aniSensoAdmin.tutorials.form', ['tutorial' => $tutorial, 'mode' => 'edit']);
    }

    public function store(Request $request)
    {
        $tutorial = new AsTutorial();
        $this->fill($tutorial, $request);
        $tutorial->save();

        return redirect()->route('anisenso-tutorials.index')->with('success', 'Tutorial saved.');
    }

    public function update(Request $request, $id)
    {
        $tutorial = AsTutorial::active()->where('id', $id)->firstOrFail();
        $this->fill($tutorial, $request);
        $tutorial->save();

        return redirect()->route('anisenso-tutorials.index')->with('success', 'Tutorial updated.');
    }

    public function destroy($id)
    {
        $tutorial = AsTutorial::active()->where('id', $id)->firstOrFail();
        $tutorial->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Tutorial removed.']);
    }

    private function fill(AsTutorial $tutorial, Request $request): void
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'category' => 'nullable|string|max:80',
            'videoUrl' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'sortOrder' => 'nullable|integer',
            'cover' => 'nullable|image|max:8192',
            'isPublished' => 'nullable|boolean',
        ]);

        $tutorial->title = $data['title'];
        $tutorial->category = $data['category'] ?? null;
        $tutorial->youtubeId = $this->youtubeId($data['videoUrl'] ?? '');
        $tutorial->description = $data['description'] ?? null;
        $tutorial->sortOrder = (int) ($data['sortOrder'] ?? 0);
        $tutorial->isPublished = $request->boolean('isPublished') ? 1 : 0;
        $tutorial->deleteStatus = 1;

        if ($request->hasFile('cover')) {
            $tutorial->coverImagePath = $request->file('cover')->store('community/tutorials', 'public');
        }
    }

    /** Pull the 11-char id out of any YouTube URL shape (or a bare id). */
    private function youtubeId(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:[^ ]*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $raw, $m)) {
            return $m[1];
        }
        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $raw)) {
            return $raw;
        }

        return null;
    }
}
