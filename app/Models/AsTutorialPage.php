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
