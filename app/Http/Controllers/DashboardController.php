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
        $selectedLocationIds = $this->getSelectedLocationIds();

        if ($selectedLocationIds->isEmpty()) {
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
                ->where(function ($query) use ($selectedLocationIds) {
                    $query
                        ->whereHas('locations', fn ($locationQuery) => $locationQuery->whereIn('locations.id', $selectedLocationIds->all()))
                        ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'super-admin'));
                })
                ->count(),
            'profileCompletion' => $profileCompletion,
            'brandCount' => Brand::count(),
            'categoryCount' => Category::count(),
            'vehicleCount' => Vehicle::count(),
            'purchaseCount' => Purchase::query()->whereIn('location_id', $selectedLocationIds->all())->count(),
            'saleCount' => Sell::query()->whereIn('location_id', $selectedLocationIds->all())->count(),
        ]);
    }
}
