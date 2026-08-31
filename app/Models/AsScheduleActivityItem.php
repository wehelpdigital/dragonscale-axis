<?php

namespace App\Models;

class AsScheduleActivityItem extends BaseModel
{
    protected $table = 'as_schedule_activity_items';

    protected $fillable = [
        'activityId',
        'itemType',
        'materialId',
        'serviceId',
        // A free-form line: what it is called, what it cost, and which thing
        // on the inventory shelf it spends, if any. The farmer app writes all
        // three; this app could only ever read past them.
        'itemName',
        'unitPrice',
        'inventoryItemId',
        'quantity',
        'unitOfMeasure',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unitPrice' => 'decimal:2',
        'inventoryItemId' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function activity()
    {
        return $this->belongsTo(AsScheduleActivity::class, 'activityId');
    }

    public function material()
    {
        return $this->belongsTo(AsScheduleMaterial::class, 'materialId');
    }

    public function service()
    {
        return $this->belongsTo(AsScheduleService::class, 'serviceId');
    }
}
