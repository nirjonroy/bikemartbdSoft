<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'email',
        'phone',
        'address',
        'website',
        'currency_code',
        'timezone',
        'invoice_footer',
        'logo_path',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->business_name ?: config('app.name', 'BikeMart POS');
    }

    public function getInitialsAttribute(): string
    {
        return collect(preg_split('/\s+/', $this->display_name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->join('');
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return Storage::url($this->logo_path);
        }

        return asset('adminlte/assets/img/AdminLTELogo.png');
    }
}
