<?php

namespace App\Models;

class AsGenerationOffDate extends BaseModel
{
    protected $table = 'as_generation_off_dates';

    protected $fillable = [
        'generationId',
        'offDate',
        'reason',
    ];

    protected $casts = [
        'offDate' => 'date:Y-m-d',
    ];

    public function generation()
    {
        return $this->belongsTo(AsScheduleGeneration::class, 'generationId');
    }
}
