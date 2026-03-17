<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class StockManagementController extends Controller
{
    private const VALID_STATUSES = [
        'In Stock',
        'Out of Stock',
        'Not Purchased',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(self::VALID_STATUSES)],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;

        $summaryVehicles = Vehicle::query()
            ->with(['latestPurchase.modifyingCosts', 'latestSell'])
            ->withSum('purchases as purchased_quantity_total', 'quantity')
            ->withSum('sells as sold_quantity_total', 'quantity')
            ->get();

        $filteredVehicles = Vehicle::query()
            ->with(['brand', 'category', 'latestPurchase.modifyingCosts', 'latestSell'])
            ->withSum('purchases as purchased_quantity_total', 'quantity')
            ->withSum('sells as sold_quantity_total', 'quantity')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($vehicleQuery) use ($search) {
                    $vehicleQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%")
                        ->orWhere('engine_number', 'like', "%{$search}%")
                        ->orWhere('chassis_number', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->get()
            ->when($status, fn ($vehicles) => $vehicles->where('stock_status', $status))
            ->values();

        $perPage = 12;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $vehicles = new LengthAwarePaginator(
            $filteredVehicles->forPage($currentPage, $perPage)->values(),
            $filteredVehicles->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('stock.index', [
            'businessSetting' => $this->getBusinessSetting(),
            'vehicles' => $vehicles,
            'search' => $search,
            'status' => $status,
            'statusOptions' => self::VALID_STATUSES,
            'filteredVehicleCount' => $filteredVehicles->count(),
            'totalVehicles' => $summaryVehicles->count(),
            'totalPurchasedUnits' => $summaryVehicles->sum('purchased_quantity'),
            'totalSoldUnits' => $summaryVehicles->sum('sold_quantity'),
            'availableStockUnits' => $summaryVehicles->sum('available_stock_quantity'),
            'inStockVehicleCount' => $summaryVehicles->where('stock_status', 'In Stock')->count(),
            'outOfStockVehicleCount' => $summaryVehicles->where('stock_status', 'Out of Stock')->count(),
            'notPurchasedVehicleCount' => $summaryVehicles->where('stock_status', 'Not Purchased')->count(),
        ]);
    }
}
