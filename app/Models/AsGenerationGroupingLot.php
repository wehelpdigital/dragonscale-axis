<?php

namespace App\Models;

class AsGenerationGroupingLot extends BaseModel
{
    protected $table = 'as_generation_grouping_lots';

    protected $fillable = [
        'groupingId',
        'lotId',
    ];

    public function grouping()
    {
        return $this->belongsTo(AsGenerationGrouping::class, 'groupingId');
    }

    public function lot()
    {
        return $this->belongsTo(AsScheduleLot::class, 'lotId');
    }
}
