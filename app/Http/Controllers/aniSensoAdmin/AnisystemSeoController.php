<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsSiteSetting;
use Illuminate\Http\Request;

/**
 * Whether search engines may index anee.io's public site, managed here.
 *
 * One switch. Off (the default), and the whole domain answers noindex --
 * robots.txt, an X-Robots-Tag header on every response, the robots meta
 * in every layout. On, and the public pages (home, features, pricing,
 * about, contact, the legal pages) open to crawlers while everything
 * behind the login stays closed; that part is not a setting.
 */
class AnisystemSeoController extends Controller
{
    public const KEY = 'seo.publicIndexable';

    public function index()
    {
        $publicIndexable = AsSiteSetting::yes(self::KEY, false);
        $tableReady = true;
        try {
            AsSiteSetting::query()->count();
        } catch (\Throwable $e) {
            $tableReady = false;
        }

        return view('aniSensoAdmin.seo.index', compact('publicIndexable', 'tableReady'));
    }

    public function save(Request $request)
    {
        try {
            AsSiteSetting::put(self::KEY, $request->boolean('publicIndexable'));
        } catch (\Throwable $e) {
            return redirect()->route('anisenso-seo.index')->with('error', 'The settings shelf is not there yet — anee.io has to deploy its migration first.');
        }

        return redirect()->route('anisenso-seo.index')->with('success',
            $request->boolean('publicIndexable')
                ? 'Search engines may now index the public site. The app behind the login stays closed to them.'
                : 'Search engines are told to keep the whole site out of their index.');
    }
}
