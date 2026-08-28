<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A "How to use" page in AniSystem: one module, one device, a list of blocks.
 *
 * Shared table — AniSystem reads these, this app writes them. The block shapes
 * are the contract between the two; keep them in step with
 * App\Support\TutorialBlocks over there.
 */
class AsTutorialPage extends Model
{
    protected $table = 'as_tutorial_pages';

    /** Widest first, which is also the order the tabs read. */
    public const DEVICES = ['desktop', 'tablet', 'mobile'];

    public const DEVICE_LABELS = [
        'desktop' => 'Desktop',
        'tablet' => 'Tablet',
        'mobile' => 'Phone',
    ];

    /** Every module a page can be written for. Matches AniSystem's list. */
    public const MODULES = [
        'activities' => 'Activities',
        'notes' => 'Notes',
        'maps' => 'Maps',
        'draw' => 'Draw',
        // Added after this list was first written: the builder can only edit a
        // guide for a module it knows about, so a module missing here has a
        // page in AniSystem that nobody can correct.
        'growth' => 'Growth Stages',
        'gallery' => 'Gallery',
        'weather' => 'Weather',
        'lots' => 'Lots',
        'workers' => 'Workers',
        'documentation' => 'Documentation',
        'post-harvest' => 'Post-harvest',
        'reports' => 'Reports',
        'settings' => 'Settings',
        'collab' => 'Collab Room',
        'ai' => 'AI Technician',
        'hub' => 'Schedule Hub',
        'schedules' => 'Schedules',

        /* Outside the cropping schedule.
         *
         * The question mark started life in the schedule modules and stayed
         * there, which left the three screens people actually live on — the
         * home board, Community and Account — with nothing behind it and no
         * way to write anything. These are those, plus the two schedule
         * modules that shipped a help key and were never added to this list:
         * their pages 404 rather than opening, which is the same as having
         * no help at all except that it looks broken.
         *
         * Community is not one screen and does not get one page. Discussions,
         * Members, Co-farmers and the rest are separate places with separate
         * rules, and a single "how to use Community" would have to be so
         * general as to say nothing. */
        'inventory' => 'Inventory',
        'media' => 'Media',
        'home' => 'Home',
        'account' => 'Account',
        'community' => 'Community — the feed',
        'community-discussions' => 'Community — Discussions',
        'community-members' => 'Community — Members',
        'community-cofarmers' => 'Community — Co-farmers',
        'community-blog' => 'Community — Blog',
        'community-ranking' => 'Community — Rankings',
        'community-saved' => 'Community — Saved',
        'community-messages' => 'Community — Messages',
        'community-profile' => 'Community — Your profile',
    ];

    /** What the builder can drag onto a page. */
    public const BLOCK_KINDS = [
        'heading' => 'Heading',
        'text' => 'Paragraph',
        'steps' => 'Numbered steps',
        'tips' => 'Bullet list',
        'callout' => 'Callout',
        'image' => 'Image',
        'video' => 'Video (YouTube)',
        'divider' => 'Divider',
    ];

    protected $fillable = [
        'moduleKey',
        'device',
        'title',
        'summary',
        'blocks',
        'updatedByUserId',
        'deleteStatus',
    ];

    protected $casts = [
        'blocks' => 'array',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public static function moduleLabel(string $key): string
    {
        return self::MODULES[$key] ?? ucfirst(str_replace('-', ' ', $key));
    }
}
