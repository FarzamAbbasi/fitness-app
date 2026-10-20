<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutPlan extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'status',
        'source',
        'week_number',
        'ai_rationale',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_rationale' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(PlanDay::class)->orderBy('day_number');
    }
}
