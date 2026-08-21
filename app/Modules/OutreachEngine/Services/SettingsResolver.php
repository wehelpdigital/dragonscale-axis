<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachSetting;
use App\Modules\OutreachEngine\Support\OutreachException;

/**
 * Resolves "which credentials do I run as?" for one admin user.
 *
 * Every other service in this module takes an OutreachSetting in its constructor;
 * this is the one place that goes to the database for it, so controllers, console
 * commands and jobs all agree on what a missing configuration means.
 *
 * Deliberately NOT memoised. The settings screen saves and then immediately runs a
 * connection test in the same request - a per-request cache would hand that test the
 * pre-save credentials and report a false failure. Reads are a single indexed row.
 *
 * Nothing here writes. outreach_settings carries unique(usersId, delete_status), so a
 * user may hold one active plus one deleted row: the save path must UPDATE the active
 * row in place, never delete-and-recreate, or the second save collides on the index.
 */
class SettingsResolver
{
    /**
     * The active settings row for a user, or an unsaved instance carrying the column
     * defaults. Safe for read-only paths (settings form, dashboard badges) that must
     * render something even before the user has ever saved.
     */
    public function forUser(int $userId): OutreachSetting
    {
        return OutreachSetting::forUserOrNew($userId);
    }

    /**
     * The saved settings row for a user, or a hard stop.
     *
     * Used by anything that is about to spend money or touch the network: an unsaved
     * instance has empty credentials, and letting it through only produces a confusing
     * downstream error ("REQUEST_DENIED", "SMTP connect failed") instead of the real one.
     *
     * @throws OutreachException when the user has never saved their settings.
     */
    public function requireForUser(int $userId): OutreachSetting
    {
        $settings = $this->forUser($userId);

        if (! $settings->exists) {
            throw new OutreachException(
                'Outreach settings have not been saved for this account yet. Open Lead Finder > Settings and save your API keys first.'
            );
        }

        return $settings;
    }

    /**
     * Has this user saved their settings at all? Cheap guard for UI badges and for
     * console commands that would otherwise throw on an unconfigured account.
     */
    public function isConfigured(int $userId): bool
    {
        return OutreachSetting::query()->active()->forUser($userId)->exists();
    }

    /**
     * Every usersId holding an active settings row, ascending.
     *
     * This is the list the console commands walk when --user is omitted. The unique
     * index guarantees at most one active row per user, so the ids are already distinct.
     *
     * @return int[]
     */
    public function configuredUserIds(): array
    {
        return OutreachSetting::query()
            ->active()
            ->orderBy('usersId')
            ->pluck('usersId')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
    }
}
