<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sell extends Model
{
    protected $fillable = [
        'vehicle_id',
        'name',
        'father_name',
        'address',
        'mobile_number',
        'quantity',
        'selling_price_to_customer',
        'selling_date',
        'extra_additional_note',
    ];

    protected $casts = [
        'vehicle_id' => 'integer',
        'quantity' => 'integer',
        'selling_price_to_customer' => 'decimal:2',
        'selling_date' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SellDocument::class);
    }

    public function pictureDocuments(): HasMany
    {
        return $this->hasMany(SellDocument::class)->where('type', SellDocument::TYPE_PICTURE);
    }

    public function documentFor(string $type): ?SellDocument
    {
        return $this->documents->firstWhere('type', $type);
    }
}
