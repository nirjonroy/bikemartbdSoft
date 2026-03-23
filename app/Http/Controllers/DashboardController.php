<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\User;
use App\Models\Vehicle;

class DashboardController extends Controller
{
    public function index()
    {
        $activeLocation = $this->getActiveLocation();

        if (! $activeLocation) {
            return $this->missingLocationResponse();
        }

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
            'activeLocation' => $activeLocation,
            'staffCount' => User::query()
                ->where(function ($query) use ($activeLocation) {
                    $query
                        ->whereHas('locations', fn ($locationQuery) => $locationQuery->where('locations.id', $activeLocation->id))
                        ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'super-admin'));
                })
                ->count(),
            'profileCompletion' => $profileCompletion,
            'brandCount' => Brand::count(),
            'categoryCount' => Category::count(),
            'vehicleCount' => Vehicle::count(),
            'purchaseCount' => Purchase::query()->where('location_id', $activeLocation->id)->count(),
            'saleCount' => Sell::query()->where('location_id', $activeLocation->id)->count(),
        ]);
    }
}
