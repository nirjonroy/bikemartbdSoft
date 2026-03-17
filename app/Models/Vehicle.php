<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'code',
        'model',
        'registration_number',
        'engine_number',
        'chassis_number',
        'color',
        'year',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function latestPurchase(): HasOne
    {
        return $this->hasOne(Purchase::class)->latestOfMany('purchasing_date');
    }

    public function sells(): HasMany
    {
        return $this->hasMany(Sell::class);
    }

    public function latestSell(): HasOne
    {
        return $this->hasOne(Sell::class)->latestOfMany('selling_date');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->code
            ? "{$this->name} ({$this->code})"
            : $this->name;
    }

    public function isAvailableForPurchase(): bool
    {
        return true;
    }

    public function isAvailableForSale(): bool
    {
        return $this->available_stock_quantity > 0;
    }

    public function getPurchasedQuantityAttribute(): int
    {
        if (array_key_exists('purchased_quantity_total', $this->attributes)) {
            return (int) round((float) $this->attributes['purchased_quantity_total']);
        }

        if ($this->relationLoaded('purchases')) {
            return (int) $this->purchases->sum('quantity');
        }

        return (int) $this->purchases()->sum('quantity');
    }

    public function getSoldQuantityAttribute(): int
    {
        if (array_key_exists('sold_quantity_total', $this->attributes)) {
            return (int) round((float) $this->attributes['sold_quantity_total']);
        }

        if ($this->relationLoaded('sells')) {
            return (int) $this->sells->sum('quantity');
        }

        return (int) $this->sells()->sum('quantity');
    }

    public function getAvailableStockQuantityAttribute(): int
    {
        return max($this->purchased_quantity - $this->sold_quantity, 0);
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->purchased_quantity === 0) {
            return 'Not Purchased';
        }

        return $this->available_stock_quantity > 0
            ? 'In Stock'
            : 'Out of Stock';
    }

    public function getStockBadgeClassAttribute(): string
    {
        return match ($this->stock_status) {
            'In Stock' => 'text-bg-success',
            'Out of Stock' => 'text-bg-danger',
            default => 'text-bg-secondary',
        };
    }

    public function getCurrentStockValueAttribute(): float
    {
        if ($this->available_stock_quantity <= 0) {
            return 0.0;
        }

        $latestPurchase = $this->relationLoaded('latestPurchase')
            ? $this->latestPurchase
            : $this->latestPurchase()->first();

        if (! $latestPurchase) {
            return 0.0;
        }

        $unitCost = (float) $latestPurchase->grand_total / max((int) $latestPurchase->quantity, 1);

        return $unitCost * $this->available_stock_quantity;
    }

    public function getEstimatedMarginAttribute(): ?float
    {
        $latestPurchase = $this->relationLoaded('latestPurchase')
            ? $this->latestPurchase
            : $this->latestPurchase()->first();

        $latestSell = $this->relationLoaded('latestSell')
            ? $this->latestSell
            : $this->latestSell()->first();

        if (! $latestPurchase || ! $latestSell) {
            return null;
        }

        return (float) $latestSell->selling_price_to_customer - (float) $latestPurchase->grand_total;
    }
}
