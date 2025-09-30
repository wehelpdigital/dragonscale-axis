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
        // Add other fields as needed
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
     * Scope to get all clients (no filtering since no isActive/deleteStatus columns)
     */
    public function scopeActive($query)
    {
        return $query; // Return all clients since there are no status columns
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
