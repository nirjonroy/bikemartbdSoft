<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'show_stock_information',
        'show_quantity_fields',
        'show_stock_management_module',
    ];

    protected $attributes = [
        'show_stock_information' => true,
        'show_quantity_fields' => true,
        'show_stock_management_module' => true,
    ];

    protected $casts = [
        'show_stock_information' => 'boolean',
        'show_quantity_fields' => 'boolean',
        'show_stock_management_module' => 'boolean',
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
            return asset('storage/'.ltrim($this->logo_path, '/'));
        }

        return asset('adminlte/assets/img/AdminLTELogo.png');
    }
}
