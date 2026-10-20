<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'niche',
        'created_by',
        'capacity',
        'status',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(PodMember::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(PodCheckin::class)->latest();
    }
}
