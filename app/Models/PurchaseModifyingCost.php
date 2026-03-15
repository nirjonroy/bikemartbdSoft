<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseModifyingCost extends Model
{
    protected $fillable = [
        'purchase_id',
        'reason',
        'cost',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
