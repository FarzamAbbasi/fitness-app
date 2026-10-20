<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanExercise extends Model
{
    protected $fillable = [
        'plan_day_id',
        'order',
        'exercise_name',
        'sets',
        'reps',
        'duration_seconds',
        'rest_seconds',
        'notes',
    ];

    public function planDay(): BelongsTo
    {
        return $this->belongsTo(PlanDay::class);
    }
}
