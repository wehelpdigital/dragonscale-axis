<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsAdSetting;
use App\Models\AsAdUnit;
use Illuminate\Http\Request;

/**
 * The advertising anee.io's free plan carries, managed from here.
 *
 * One page holds the switch and the settings (the AdSense publisher id,
 * the words a slot wears, how often the community feed shows one) and the
 * units under them: a Google AdSense slot, a picture that links somewhere,
 * or a script an ad network handed over -- each with the surfaces it may
 * appear on and a weight for the draw. anee.io reads both tables and
 * shows a slot only to a Libre account on its own farm (and to anybody
 * not paying on the public pricing page); paid plans never see one.
 */
class AnisystemAdsController extends Controller
{
    public function index()
    {
        $settings = AsAdSetting::current();
        $units = AsAdUnit::active()->orderBy('sortOrder')->orderByDesc('id')->get();

        return view('aniSensoAdmin.ads.index', compact('settings', 'units'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'adsenseClient' => 'nullable|string|max:64',
            'label' => 'required|string|max:60',
            'upsell' => 'required|string|max:191',
            'feedEvery' => 'required|integer|min:3|max:30',
        ]);
        $settings = AsAdSetting::current();
        $settings->fill([
            'isEnabled' => $request->boolean('isEnabled'),
            'autoAds' => $request->boolean('autoAds'),
            'adsenseClient' => trim((string) ($data['adsenseClient'] ?? '')) ?: null,
            'label' => $data['label'],
            'upsell' => $data['upsell'],
            'feedEvery' => (int) $data['feedEvery'],
            'deleteStatus' => 1,
        ])->save();

        return redirect()->route('anisenso-ads.index')->with('success', 'Ad settings saved.');
    }

    public function create()
    {
        return view('aniSensoAdmin.ads.form', ['unit' => new AsAdUnit(['kind' => 'image', 'weight' => 1, 'isActive' => true]), 'mode' => 'create']);
    }

    public function edit(Request $request)
    {
        $unit = AsAdUnit::active()->where('id', (int) $request->query('id'))->firstOrFail();

        return view('aniSensoAdmin.ads.form', ['unit' => $unit, 'mode' => 'edit']);
    }

    public function store(Request $request)
    {
        $unit = new AsAdUnit();
        $this->fill($unit, $request);
        $unit->save();

        return redirect()->route('anisenso-ads.index')->with('success', 'Ad unit saved.');
    }

    public function update(Request $request)
    {
        $unit = AsAdUnit::active()->where('id', (int) $request->query('id'))->firstOrFail();
        $this->fill($unit, $request);
        $unit->save();

        return redirect()->route('anisenso-ads.index')->with('success', 'Ad unit updated.');
    }

    public function destroy(Request $request)
    {
        $unit = AsAdUnit::active()->where('id', (int) $request->query('id'))->firstOrFail();
        $unit->update(['deleteStatus' => 0, 'isActive' => false]);

        return response()->json(['success' => true, 'message' => 'Ad unit removed.']);
    }

    private function fill(AsAdUnit $unit, Request $request): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'kind' => 'required|in:adsense,image,script',
            'placements' => 'nullable|array',
            'placements.*' => 'string|in:' . implode(',', array_keys(AsAdUnit::PLACEMENTS)),
            'weight' => 'nullable|integer|min:1|max:100',
            'sortOrder' => 'nullable|integer',
            'startsAt' => 'nullable|date',
            'endsAt' => 'nullable|date|after_or_equal:startsAt',
            'adClient' => 'nullable|string|max:64',
            'adSlot' => 'nullable|string|max:64',
            'adFormat' => 'nullable|string|max:24',
            'image' => 'nullable|image|max:4096',
            'imageUrl' => 'nullable|url|max:500',
            'imageAlt' => 'nullable|string|max:191',
            'linkUrl' => 'nullable|url|max:500',
            'scriptHtml' => 'nullable|string|max:20000',
        ]);

        $unit->name = $data['name'];
        $unit->kind = $data['kind'];
        $unit->placements = array_values($data['placements'] ?? []);
        $unit->weight = (int) ($data['weight'] ?? 1);
        $unit->sortOrder = (int) ($data['sortOrder'] ?? 0);
        $unit->isActive = $request->boolean('isActive');
        $unit->startsAt = $data['startsAt'] ?? null;
        $unit->endsAt = $data['endsAt'] ?? null;
        $unit->adClient = trim((string) ($data['adClient'] ?? '')) ?: null;
        $unit->adSlot = trim((string) ($data['adSlot'] ?? '')) ?: null;
        $unit->adFormat = trim((string) ($data['adFormat'] ?? '')) ?: null;
        $unit->imageAlt = $data['imageAlt'] ?? null;
        $unit->linkUrl = trim((string) ($data['linkUrl'] ?? '')) ?: null;
        $unit->scriptHtml = $data['scriptHtml'] ?? null;
        $unit->deleteStatus = 1;

        // The picture: an upload lands in the bucket beside the community's
        // other pictures; a pasted address is kept as it is.
        if ($request->hasFile('image')) {
            $unit->imagePath = $request->file('image')->store('community/ads', 'public');
        } elseif (filled($data['imageUrl'] ?? null)) {
            $unit->imagePath = $data['imageUrl'];
        }
    }
}
