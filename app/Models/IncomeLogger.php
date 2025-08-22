<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeLogger extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'income_logger';

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
        'taskCoin',
        'taskType',
        'transactionDateTime',
        'originalPhpValue',
        'newPhpValue',
        'delete_status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'difference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'transactionDateTime' => 'datetime',
        'originalPhpValue' => 'decimal:2',
        'newPhpValue' => 'decimal:2',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the user that owns the income logger entry.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Scope to get active entries
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope to filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope to filter by task type
     */
    public function scopeByTaskType($query, $taskType)
    {
        return $query->where('taskType', $taskType);
    }

    /**
     * Scope to filter by coin type
     */
    public function scopeByCoinType($query, $coinType)
    {
        return $query->where('taskCoin', $coinType);
    }



    /**
     * Get the difference between original and new PHP values
     */
    public function getDifferenceAttribute()
    {
        return $this->newPhpValue - $this->originalPhpValue;
    }
}
