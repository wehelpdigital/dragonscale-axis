<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientAllDatabase extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clients_all_database';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'clientFirstName',
        'clientMiddleName',
        'clientLastName',
        'clientPhoneNumber',
        'clientEmailAddress',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get active (non-deleted) clients
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get the full name of the client
     */
    public function getFullNameAttribute()
    {
        $name = $this->clientFirstName;
        if ($this->clientMiddleName) {
            $name .= ' ' . $this->clientMiddleName;
        }
        if ($this->clientLastName) {
            $name .= ' ' . $this->clientLastName;
        }
        return $name;
    }
}
