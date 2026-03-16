<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function getBusinessSetting(): BusinessSetting
    {
        return BusinessSetting::first() ?? BusinessSetting::create([
            'business_name' => config('app.name', 'BikeMart POS'),
            'email' => 'admin@bikemartbd.com',
            'currency_code' => 'BDT',
            'timezone' => config('app.timezone', 'UTC'),
        ]);
    }
}
