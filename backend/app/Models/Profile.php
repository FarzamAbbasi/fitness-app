<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'goal',
        'experience_level',
        'days_per_week',
        'session_duration_minutes',
        'equipment',
        'niche',
    ];

    protected function casts(): array
    {
        return [
            'equipment' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
