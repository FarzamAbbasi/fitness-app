<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLogEntry extends Model
{
    protected $fillable = [
        'workout_log_id',
        'exercise_name',
        'set_number',
        'reps',
        'weight',
        'duration_seconds',
    ];

    public function workoutLog(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class);
    }
}
