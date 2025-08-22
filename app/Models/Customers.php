<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customers extends BaseModel
{
    use HasFactory;

    protected $table = "customers";

    protected $fillable = [
        "username",
        "email",
        "phone",
        "address",
        "rating",
        "balance",
        "joining_date"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
