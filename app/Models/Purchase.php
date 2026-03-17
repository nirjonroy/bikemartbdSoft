<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'vehicle_id',
        'name',
        'father_name',
        'address',
        'mobile_number',
        'quantity',
        'buying_price_from_owner',
        'purchasing_date',
        'extra_additional_note',
    ];

    protected $casts = [
        'vehicle_id' => 'integer',
        'quantity' => 'integer',
        'buying_price_from_owner' => 'decimal:2',
        'purchasing_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PurchaseDocument::class);
    }

    public function pictureDocuments(): HasMany
    {
        return $this->hasMany(PurchaseDocument::class)->where('type', PurchaseDocument::TYPE_PICTURE);
    }

    public function modifyingCosts(): HasMany
    {
        return $this->hasMany(PurchaseModifyingCost::class);
    }

    public function documentFor(string $type): ?PurchaseDocument
    {
        return $this->documents->firstWhere('type', $type);
    }

    public function getTotalModifyingCostAttribute(): float
    {
        return (float) $this->modifyingCosts->sum('cost');
    }

    public function getGrandTotalAttribute(): float
    {
        return (float) $this->buying_price_from_owner + $this->total_modifying_cost;
    }
}
