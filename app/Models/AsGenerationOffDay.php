<?php

namespace App\Models;

class AsGenerationOffDay extends BaseModel
{
    protected $table = 'as_generation_off_days';

    protected $fillable = [
        'generationId',
        'dayOfWeek',
    ];

    protected $casts = [
        'dayOfWeek' => 'integer',
    ];

    public function generation()
    {
        return $this->belongsTo(AsScheduleGeneration::class, 'generationId');
    }
}
