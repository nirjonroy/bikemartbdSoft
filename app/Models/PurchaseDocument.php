<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseDocument extends Model
{
    public const TYPE_PICTURE = 'picture';

    public const SINGLE_TYPES = [
        'nid_copy' => 'NID Copy',
        'registration_copy' => 'Registration Copy',
        'smart_card' => 'Smart Card',
        'tax_token' => 'Tax Token',
        'fitness_paper' => 'Fitness Paper',
        'insurance' => 'Insurance',
    ];

    public const ALL_TYPES = [
        self::TYPE_PICTURE => 'Vehicle Pictures',
        'nid_copy' => 'NID Copy',
        'registration_copy' => 'Registration Copy',
        'smart_card' => 'Smart Card',
        'tax_token' => 'Tax Token',
        'fitness_paper' => 'Fitness Paper',
        'insurance' => 'Insurance',
    ];

    protected $fillable = [
        'purchase_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
}
