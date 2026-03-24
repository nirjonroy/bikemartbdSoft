<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sell extends Model
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
        'selling_price_to_customer',
        'payment_status',
        'payment_method',
        'payment_information',
        'selling_date',
        'extra_additional_note',
    ];

    protected $casts = [
        'location_id' => 'integer',
        'vehicle_id' => 'integer',
        'quantity' => 'integer',
        'selling_price_to_customer' => 'decimal:2',
        'selling_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Sell $sell) {
            if (! $sell->location_id) {
                $sell->location_id = Location::query()->value('id');
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

    public function getInvoiceNumberAttribute(): string
    {
        $locationCode = $this->relationLoaded('location')
            ? $this->location?->code
            : $this->location()->value('code');

        return sprintf(
            'INV-%s-%05d',
            strtoupper($locationCode ?: 'MAIN'),
            $this->id
        );
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
