<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PodCheckin extends Model
{
    protected $fillable = [
        'pod_id',
        'user_id',
        'workout_log_id',
        'message',
    ];

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Pod::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }
}
