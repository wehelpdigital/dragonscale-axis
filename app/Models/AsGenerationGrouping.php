<?php

namespace App\Models;

class AsGenerationGrouping extends BaseModel
{
    protected $table = 'as_generation_groupings';

    protected $fillable = [
        'generationId',
        'groupName',
        'staggerDays',
        'startDate',
        'groupOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'staggerDays' => 'integer',
        'startDate' => 'date:Y-m-d',
        'groupOrder' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function generation()
    {
        return $this->belongsTo(AsScheduleGeneration::class, 'generationId');
    }

    public function lots()
    {
        return $this->belongsToMany(
            AsScheduleLot::class,
            'as_generation_grouping_lots',
            'groupingId',
            'lotId'
        );
    }

    public function groupingLots()
    {
        return $this->hasMany(AsGenerationGroupingLot::class, 'groupingId');
    }
}
