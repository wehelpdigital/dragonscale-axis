<?php

namespace App\Http\Controllers\Games;

use App\Http\Controllers\Controller;
use App\Models\GameSiteGame;
use App\Services\Games\GamesSiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Games > Website > Games & Site: what the studio website (wehelpdigital/
 * game-website) shows. Its name and tagline, and every game it features:
 * the words, the pictures, the platforms and where to play. The website reads
 * all of it from /api/games-site/v1/site (cached for a few minutes there).
 */
class GamesSiteController extends Controller
{
    public function index()
    {
        return view('games.site.index', [
            'games' => GameSiteGame::active()->orderBy('sortOrder')->orderBy('id')->get(),
            'site' => GamesSiteSettings::site(),
            'siteUrl' => (string) config('games.site.url'),
            'tokenSet' => trim((string) config('games.site.token')) !== '',
        ]);
    }

    public function edit(Request $request)
    {
        $game = $request->filled('id') ? GameSiteGame::active()->findOrFail((int) $request->input('id')) : new GameSiteGame([
            'status' => 'coming_soon',
            'platforms' => [['key' => 'web', 'status' => 'soon', 'url' => '']],
            'features' => [],
            'screenshots' => [],
            'sortOrder' => (int) GameSiteGame::active()->max('sortOrder') + 1,
        ]);

        return view('games.site.edit', ['game' => $game]);
    }

    public function save(Request $request)
    {
        $game = $request->filled('id') ? GameSiteGame::active()->findOrFail((int) $request->input('id')) : new GameSiteGame();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'gameKey' => ['required', 'regex:/^[a-z0-9_]{2,40}$/', Rule::unique('games_site_games', 'gameKey')->ignore($game->id)],
            'slug' => ['required', 'regex:/^[a-z0-9-]{2,80}$/', Rule::unique('games_site_games', 'slug')->ignore($game->id)],
            'tagline' => ['nullable', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'genre' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(array_keys(GameSiteGame::STATUSES))],
            'playUrl' => ['nullable', 'url', 'max:255'],
            'accent' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sortOrder' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'platforms' => ['nullable', 'array'],
            'platforms.*.key' => ['required', Rule::in(array_keys(GameSiteGame::PLATFORMS))],
            'platforms.*.status' => ['required', Rule::in(['live', 'soon'])],
            'platforms.*.url' => ['nullable', 'url', 'max:255'],
            'features' => ['nullable', 'array', 'max:12'],
            'features.*.title' => ['nullable', 'string', 'max:80'],
            'features.*.text' => ['nullable', 'string', 'max:400'],
            'screenshots' => ['nullable', 'array', 'max:24'],
            'screenshots.*.url' => ['nullable', 'string', 'max:255'],
            'screenshots.*.caption' => ['nullable', 'string', 'max:160'],
            'newScreenshots' => ['nullable', 'array', 'max:12'],
            'newScreenshots.*' => ['image', 'max:6144'],
            'logoFile' => ['nullable', 'image', 'max:4096'],
            'heroFile' => ['nullable', 'image', 'max:6144'],
        ], [
            'gameKey.regex' => 'The key is lowercase letters, digits and _ (the game server\'s key, e.g. salpakan).',
            'slug.regex' => 'The address is lowercase letters, digits and - (e.g. salpakan-tactics).',
            'accent.regex' => 'The colour looks like #ff5a8a.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $platforms = collect($request->input('platforms', []))
            ->filter(fn ($p) => is_array($p) && ! empty($p['key']))
            ->map(fn ($p) => ['key' => $p['key'], 'status' => $p['status'], 'url' => trim((string) ($p['url'] ?? ''))])
            ->unique('key')->values()->all();
        $features = collect($request->input('features', []))
            ->filter(fn ($f) => is_array($f) && trim((string) ($f['title'] ?? '')) !== '')
            ->map(fn ($f) => ['title' => trim((string) $f['title']), 'text' => trim((string) ($f['text'] ?? ''))])
            ->values()->all();
        $screenshots = collect($request->input('screenshots', []))
            ->filter(fn ($s) => is_array($s) && trim((string) ($s['url'] ?? '')) !== '' && empty($s['remove']))
            ->map(fn ($s) => ['url' => trim((string) $s['url']), 'caption' => trim((string) ($s['caption'] ?? ''))])
            ->values()->all();
        foreach ((array) $request->file('newScreenshots', []) as $file) {
            $screenshots[] = ['url' => $file->store('games/site/' . $request->input('gameKey'), 'public'), 'caption' => ''];
        }

        $values = [
            'name' => trim((string) $request->input('name')),
            'gameKey' => $request->input('gameKey'),
            'slug' => $request->input('slug'),
            'tagline' => $request->input('tagline'),
            'summary' => $request->input('summary'),
            'description' => $request->input('description'),
            'genre' => $request->input('genre'),
            'status' => $request->input('status'),
            'playUrl' => $request->input('playUrl'),
            'accent' => $request->input('accent'),
            'sortOrder' => (int) $request->input('sortOrder', 0),
            'platforms' => $platforms,
            'features' => $features,
            'screenshots' => $screenshots,
        ];
        if ($request->hasFile('logoFile')) {
            $values['logoImage'] = $request->file('logoFile')->store('games/site/' . $request->input('gameKey'), 'public');
        }
        if ($request->hasFile('heroFile')) {
            $values['heroImage'] = $request->file('heroFile')->store('games/site/' . $request->input('gameKey'), 'public');
        }
        if ($request->boolean('logoRemove')) {
            $values['logoImage'] = null;
        }
        if ($request->boolean('heroRemove')) {
            $values['heroImage'] = null;
        }

        $game->fill($values)->save();

        return redirect()->route('games-site.index')->with('success', $game->name . ' is saved. The website shows it within a few minutes.');
    }

    public function destroy(Request $request)
    {
        $game = GameSiteGame::active()->findOrFail((int) $request->input('id'));
        $game->update(['delete_status' => 'deleted', 'gameKey' => $game->gameKey . '-deleted-' . $game->id, 'slug' => $game->slug . '-deleted-' . $game->id]);

        return response()->json(['success' => true, 'message' => $game->name . ' is off the website.']);
    }

    public function saveSite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'siteName' => ['required', 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'announcement' => ['nullable', 'string', 'max:300'],
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        GamesSiteSettings::saveSite([
            'siteName' => trim((string) $request->input('siteName')),
            'tagline' => trim((string) $request->input('tagline')),
            'contactEmail' => trim((string) $request->input('contactEmail')),
            'announcement' => trim((string) $request->input('announcement')),
        ]);

        return redirect()->route('games-site.index')->with('success', 'The website\'s settings are saved.');
    }

    /** A screenshot or logo path on the public disk, as an address for the form's preview. */
    public static function imageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, '/')) {
            return rtrim((string) config('games.site.url'), '/') . $path;  // One of the website's own pictures.
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}
