<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThresholdTask extends BaseModel
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'threshold_task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'usersId',
        'taskId',
        'thresholdQuotient',
        'date',
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
     * Get the user that owns the threshold task.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the task that owns the threshold task.
     */
    public function task()
    {
        return $this->belongsTo(Task::class, 'taskId');
    }
}
