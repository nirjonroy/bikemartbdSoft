<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'address',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function sells(): HasMany
    {
        return $this->hasMany(Sell::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function defaultUsers(): HasMany
    {
        return $this->hasMany(User::class, 'default_location_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code
            ? "{$this->name} ({$this->code})"
            : $this->name;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active ? 'text-bg-success' : 'text-bg-secondary';
    }
}
