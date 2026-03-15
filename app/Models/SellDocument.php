<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SellDocument extends Model
{
    public const TYPE_PICTURE = 'picture';

    public const FILE_TYPES = [
        self::TYPE_PICTURE => 'Picture',
        'registration_copy' => 'Registration Copy',
        'smart_card' => 'Smart Card',
        'nid_copy' => 'NID Copy',
        'tax_token' => 'Tax Token',
        'fitness_paper' => 'Fitness Paper',
        'insurance' => 'Insurance',
    ];

    protected $fillable = [
        'sell_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function sell(): BelongsTo
    {
        return $this->belongsTo(Sell::class);
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
