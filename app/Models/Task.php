<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'currentCoinValue',
        'taskCoin',
        'taskType',
        'startingPhpValue',
        'minThreshold',
        'usersId',
        'intervalThreshold',
        'toBuyCurrentCashValue',
        'toBuyStartingCoinValue',
        'toBuyMinThreshold',
        'toBuyIntervalThreshold',
        // Add other columns as needed
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

    /**
     * Scope to get tasks with current status
     */
    public function scopeCurrent($query)
    {
        return $query->where('status', 'current');
    }

    /**
     * Get the user that owns the task.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the notification histories for this task.
     */
    public function notificationHistories()
    {
        return $this->hasMany(NotificationHistory::class, 'taskId', 'id');
    }
}
