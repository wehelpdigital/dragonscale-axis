<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'dob',
        'avatar',
        'delete_status',
        'api_key',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_key',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the created_at attribute with Philippines timezone.
     */
    public function getCreatedAtAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parse($value)->timezone('Asia/Manila');
        }
        return $value;
    }

    /**
     * Get the updated_at attribute with Philippines timezone.
     */
    public function getUpdatedAtAttribute($value)
    {
        if ($value) {
            return \Carbon\Carbon::parse($value)->timezone('Asia/Manila');
        }
        return $value;
    }

    /**
     * Get a fresh timestamp for the model.
     *
     * @return \Carbon\Carbon
     */
    public function freshTimestamp()
    {
        return \Carbon\Carbon::now('Asia/Manila');
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return \Carbon\Carbon::parse($date)->timezone('Asia/Manila')->format('Y-m-d H:i:s');
    }
}
