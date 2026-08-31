<?php

namespace App\Models;

class AsScheduleLot extends BaseModel
{
    protected $table = 'as_schedule_lots';

    protected $fillable = [
        'croppingScheduleId',
        'lotName',
        'lotSize',
        'lotSizeUnit',
        'variety',
        // What is planted here, where it is, and which counter its days are
        // read in — all four are written by the farmer app and were readable
        // here but not settable. The growth stages and the forecast are both
        // answers to these columns.
        'crop',
        'locBarangay',
        'locZone',
        'locTown',
        'locProvince',
        'dayType',
        'dayZeroDate',
        'transplantDate',
        // How long this crop takes here, when the tree went in, and where the
        // field actually IS — all written by the farmer app, all settable
        // from the Lots tab now.
        'daysToMaturity',
        'treePlantedAt',
        'pinLat',
        'pinLng',
        'pinLabel',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'lotSize' => 'decimal:4',
        'dayZeroDate' => 'date:Y-m-d',
        'transplantDate' => 'date:Y-m-d',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    /** Human-readable full address, e.g. "Brgy. San Jose, Zone 3, Talavera, Nueva Ecija". */
    public function getFullAddressAttribute(): string
    {
        return collect([
            filled($this->locBarangay) ? 'Brgy. ' . trim($this->locBarangay) : null,
            filled($this->locZone) ? 'Zone ' . trim($this->locZone) : null,
            filled($this->locTown) ? trim($this->locTown) : null,
            filled($this->locProvince) ? trim($this->locProvince) : null,
        ])->filter()->implode(', ');
    }

    /** The geocodable part of the address ("Town, Province"), or null if unusable. */
    public function getGeocodeQueryAttribute(): ?string
    {
        $q = collect([$this->locTown, $this->locProvince])
            ->filter(fn ($p) => filled($p))
            ->map(fn ($p) => trim($p))
            ->implode(', ');

        return $q !== '' ? $q : null;
    }

    /** Stable key for de-duplicating identical locations across lots. */
    public function getLocationKeyAttribute(): ?string
    {
        $q = $this->geocode_query;

        return $q ? substr(md5(mb_strtolower($q)), 0, 12) : null;
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function activities()
    {
        return $this->belongsToMany(
            AsScheduleActivity::class,
            'as_schedule_activity_lots',
            'lotId',
            'activityId'
        );
    }
}
