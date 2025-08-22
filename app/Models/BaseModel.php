<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BaseModel extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the created_at attribute with Philippines timezone.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    public function getCreatedAtAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->timezone('Asia/Manila');
        }
        return $value;
    }

    /**
     * Get the updated_at attribute with Philippines timezone.
     *
     * @param  mixed  $value
     * @return \Carbon\Carbon
     */
    public function getUpdatedAtAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->timezone('Asia/Manila');
        }
        return $value;
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new \Illuminate\Database\Eloquent\Builder($query);
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp()
    {
        return Carbon::now('Asia/Manila');
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return Carbon::parse($date)->timezone('Asia/Manila')->format('Y-m-d H:i:s');
    }
}
