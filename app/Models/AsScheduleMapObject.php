<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One shape on a season's map.
 *
 * Points are [[lat,lng],...] and `kind` decides how they are drawn and
 * measured. The same rows the farmer app draws on: this console is not
 * keeping a copy of somebody's ground, it is drawing on theirs.
 *
 * A twin of the farmer app's App\Models\ScheduleMapObject — same table, same
 * columns, same shaped() — because the two apps share a database and not a
 * codebase.
 */
class AsScheduleMapObject extends Model
{
    protected $table = 'as_schedule_map_objects';

    protected $fillable = [
        'scheduleId', 'userId', 'kind', 'color', 'width', 'font', 'points', 'label', 'deleteStatus',
    ];

    protected $casts = [
        'scheduleId' => 'integer',
        'userId' => 'integer',
        'width' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function shaped(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'color' => $this->color,
            'width' => (int) $this->width,
            // Stroke thickness for every kind but text, where it is the type
            // size — and `font` is what says which of the two it is.
            'font' => $this->font,
            'points' => json_decode((string) $this->points, true) ?: [],
            'label' => $this->label,
            'userId' => (int) $this->userId,
        ];
    }
}
