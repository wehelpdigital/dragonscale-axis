<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DifferenceHistory extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'difference_history';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usersId',
        'taskType',
        'toSellCurrentCoinValue',
        'toSellStartingPhpValue',
        'toBuyCurrentCashValue',
        'toBuyStartingCoinValue',
        'cashDifference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'toSellCurrentCoinValue' => 'decimal:8',
        'toSellStartingPhpValue' => 'decimal:2',
        'toBuyCurrentCashValue' => 'decimal:2',
        'toBuyStartingCoinValue' => 'decimal:8',
        'cashDifference' => 'decimal:2',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the user that owns the difference history.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }
}
