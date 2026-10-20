<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    protected $fillable = [
        'user_id',
        'workout_plan_id',
        'plan_day_id',
        'logged_date',
        'duration_minutes',
        'perceived_effort',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'logged_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function planDay(): BelongsTo
    {
        return $this->belongsTo(PlanDay::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(WorkoutLogEntry::class);
    }
}
