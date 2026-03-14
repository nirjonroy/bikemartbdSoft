<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $businessSetting = $this->getBusinessSetting();

        $trackedFields = [
            $businessSetting->business_name,
            $businessSetting->email,
            $businessSetting->phone,
            $businessSetting->address,
            $businessSetting->website,
            $businessSetting->currency_code,
            $businessSetting->timezone,
            $businessSetting->invoice_footer,
            $businessSetting->logo_path,
        ];

        $completedFields = collect($trackedFields)
            ->filter(fn ($value) => filled($value))
            ->count();

        $profileCompletion = (int) round(($completedFields / count($trackedFields)) * 100);

        return view('dashboard', [
            'businessSetting' => $businessSetting,
            'staffCount' => User::count(),
            'profileCompletion' => $profileCompletion,
        ]);
    }

    private function getBusinessSetting(): BusinessSetting
    {
        return BusinessSetting::first() ?? BusinessSetting::create([
            'business_name' => config('app.name', 'BikeMart POS'),
            'email' => 'admin@bikemartbd.com',
            'currency_code' => 'BDT',
            'timezone' => config('app.timezone', 'UTC'),
        ]);
    }
}
