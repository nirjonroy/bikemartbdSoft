<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    public const PAYMENT_STATUSES = [
        'paid' => 'Paid',
        'partial' => 'Partial',
        'unpaid' => 'Unpaid',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank Transfer',
        'mobile_banking' => 'Mobile Banking',
        'card' => 'Card',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ];

    protected $fillable = [
        'location_id',
        'vehicle_id',
        'name',
        'father_name',
        'address',
        'mobile_number',
        'quantity',
        'buying_price_from_owner',
        'payment_status',
        'payment_method',
        'payment_information',
        'purchasing_date',
        'extra_additional_note',
    ];

    protected $casts = [
        'location_id' => 'integer',
        'vehicle_id' => 'integer',
        'quantity' => 'integer',
        'buying_price_from_owner' => 'decimal:2',
        'purchasing_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Purchase $purchase) {
            if (! $purchase->location_id) {
                $purchase->location_id = Location::query()->value('id');
            }
        });
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]
            ?? 'Not Set';
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method]
            ?? 'Not Set';
    }

    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'text-bg-success',
            'partial' => 'text-bg-warning',
            default => 'text-bg-danger',
        };
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
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
