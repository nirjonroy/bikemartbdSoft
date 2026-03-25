<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Purchase;
use App\Models\Sell;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private const DAILY_PROFIT_DAYS = 14;
    private const WEEKLY_PROFIT_WEEKS = 8;
    private const TRENDING_WINDOW_DAYS = 30;
    private const LOW_STOCK_THRESHOLD = 3;

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
        $averageCostByVehicle = $this->averageUnitCostByVehicle($selectedLocationIds->all());
        $dailyProfitLossChart = $this->buildDailyProfitLossChart($selectedLocationIds->all(), $averageCostByVehicle);
        $weeklyProfitLossChart = $this->buildWeeklyProfitLossChart($selectedLocationIds->all(), $averageCostByVehicle);
        $trendingItemsChart = $this->buildTrendingItemsChart($selectedLocationIds->all());
        $stockAlertChart = $this->showStockInformation() && $this->showStockManagementModule()
            ? $this->buildStockAlertChart($selectedLocationIds->all())
            : null;

        return view('dashboard', [
            'businessSetting' => $businessSetting,
            'activeLocation' => $activeLocation,
            'locationScopeLabel' => $this->getLocationScopeLabel(),
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
            'dailyProfitLossChart' => $dailyProfitLossChart,
            'weeklyProfitLossChart' => $weeklyProfitLossChart,
            'trendingItemsChart' => $trendingItemsChart,
            'stockAlertChart' => $stockAlertChart,
            'todayProfitLoss' => $dailyProfitLossChart['today_profit'],
            'currentWeekProfitLoss' => $weeklyProfitLossChart['current_week_profit'],
            'openStockAlerts' => $stockAlertChart['open_alerts'] ?? 0,
            'topTrendingItem' => $trendingItemsChart['top_item'] ?? 'No sales yet',
        ]);
    }

    private function averageUnitCostByVehicle(array $locationIds): Collection
    {
        return Purchase::query()
            ->whereIn('location_id', $locationIds)
            ->with('modifyingCosts')
            ->get()
            ->groupBy('vehicle_id')
            ->map(function (Collection $purchases) {
                $totalQuantity = max((int) $purchases->sum('quantity'), 1);
                $totalCost = (float) $purchases->sum(fn (Purchase $purchase) => $purchase->grand_total);

                return $totalCost / $totalQuantity;
            });
    }

    private function buildDailyProfitLossChart(array $locationIds, Collection $averageCostByVehicle): array
    {
        $endDate = now()->startOfDay();
        $startDate = $endDate->copy()->subDays(self::DAILY_PROFIT_DAYS - 1);

        $sells = Sell::query()
            ->whereIn('location_id', $locationIds)
            ->whereDate('selling_date', '>=', $startDate->toDateString())
            ->whereDate('selling_date', '<=', $endDate->toDateString())
            ->get();

        $profitByDate = $sells
            ->groupBy(fn (Sell $sell) => optional($sell->selling_date)->toDateString())
            ->map(fn (Collection $daySells) => round($daySells->sum(fn (Sell $sell) => $this->estimatedProfitForSale($sell, $averageCostByVehicle)), 2));

        $dates = collect(range(0, self::DAILY_PROFIT_DAYS - 1))
            ->map(fn (int $offset) => $startDate->copy()->addDays($offset));

        $labels = $dates->map(fn (Carbon $date) => $date->format('d M'))->all();
        $values = $dates
            ->map(fn (Carbon $date) => (float) ($profitByDate->get($date->toDateString(), 0)))
            ->all();

        return [
            'labels' => $labels,
            'values' => $values,
            'today_profit' => (float) end($values),
            'net_profit' => (float) array_sum($values),
            'best_day_profit' => empty($values) ? 0.0 : (float) max($values),
            'worst_day_profit' => empty($values) ? 0.0 : (float) min($values),
            'positive_days' => count(array_filter($values, fn (float $value) => $value > 0)),
            'negative_days' => count(array_filter($values, fn (float $value) => $value < 0)),
        ];
    }

    private function buildWeeklyProfitLossChart(array $locationIds, Collection $averageCostByVehicle): array
    {
        $currentWeekStart = now()->startOfWeek(Carbon::MONDAY);
        $firstWeekStart = $currentWeekStart->copy()->subWeeks(self::WEEKLY_PROFIT_WEEKS - 1);

        $sells = Sell::query()
            ->whereIn('location_id', $locationIds)
            ->whereDate('selling_date', '>=', $firstWeekStart->toDateString())
            ->whereDate('selling_date', '<=', $currentWeekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString())
            ->get();

        $profitByWeek = $sells
            ->groupBy(fn (Sell $sell) => optional($sell->selling_date)?->copy()->startOfWeek(Carbon::MONDAY)->toDateString())
            ->map(fn (Collection $weekSells) => round($weekSells->sum(fn (Sell $sell) => $this->estimatedProfitForSale($sell, $averageCostByVehicle)), 2));

        $weeks = collect(range(0, self::WEEKLY_PROFIT_WEEKS - 1))
            ->map(fn (int $offset) => $firstWeekStart->copy()->addWeeks($offset));

        $labels = $weeks->map(fn (Carbon $weekStart) => $weekStart->format('d M'))->all();
        $values = $weeks
            ->map(fn (Carbon $weekStart) => (float) ($profitByWeek->get($weekStart->toDateString(), 0)))
            ->all();

        return [
            'labels' => $labels,
            'values' => $values,
            'current_week_profit' => (float) end($values),
            'net_profit' => (float) array_sum($values),
            'strongest_week_profit' => empty($values) ? 0.0 : (float) max($values),
            'weakest_week_profit' => empty($values) ? 0.0 : (float) min($values),
        ];
    }

    private function buildTrendingItemsChart(array $locationIds): array
    {
        $startDate = now()->subDays(self::TRENDING_WINDOW_DAYS - 1)->startOfDay();

        $rows = Sell::query()
            ->whereIn('location_id', $locationIds)
            ->whereDate('selling_date', '>=', $startDate->toDateString())
            ->with(['vehicle.brand', 'vehicle.category'])
            ->get()
            ->groupBy('vehicle_id')
            ->map(function (Collection $vehicleSells) {
                /** @var \App\Models\Sell $first */
                $first = $vehicleSells->first();

                return [
                    'label' => $first->vehicle?->display_name ?: 'Unknown vehicle',
                    'subtitle' => ($first->vehicle?->brand?->name ?: 'No brand') . ' / ' . ($first->vehicle?->category?->name ?: 'No category'),
                    'sold_quantity' => (int) $vehicleSells->sum('quantity'),
                    'revenue' => (float) $vehicleSells->sum('selling_price_to_customer'),
                ];
            })
            ->sortByDesc('sold_quantity')
            ->take(6)
            ->values();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('sold_quantity')->all(),
            'items' => $rows->all(),
            'top_item' => $rows->first()['label'] ?? 'No sales yet',
            'total_units' => (int) $rows->sum('sold_quantity'),
        ];
    }

    private function buildStockAlertChart(array $locationIds): array
    {
        $vehicles = Vehicle::query()
            ->with(['brand', 'category'])
            ->withStockForLocation($locationIds)
            ->get();

        $outOfStock = $vehicles->filter(fn (Vehicle $vehicle) => $vehicle->purchased_quantity > 0 && $vehicle->available_stock_quantity === 0)->count();
        $critical = $vehicles->filter(fn (Vehicle $vehicle) => $vehicle->available_stock_quantity === 1)->count();
        $low = $vehicles->filter(fn (Vehicle $vehicle) => $vehicle->available_stock_quantity >= 2 && $vehicle->available_stock_quantity <= self::LOW_STOCK_THRESHOLD)->count();
        $healthy = $vehicles->filter(fn (Vehicle $vehicle) => $vehicle->available_stock_quantity > self::LOW_STOCK_THRESHOLD)->count();

        $alertItems = $vehicles
            ->filter(fn (Vehicle $vehicle) => $vehicle->purchased_quantity > 0 && $vehicle->available_stock_quantity <= self::LOW_STOCK_THRESHOLD)
            ->sortBy('available_stock_quantity')
            ->take(6)
            ->map(function (Vehicle $vehicle) {
                return [
                    'label' => $vehicle->display_name,
                    'subtitle' => ($vehicle->brand?->name ?: 'No brand') . ' / ' . ($vehicle->category?->name ?: 'No category'),
                    'available_stock' => $vehicle->available_stock_quantity,
                    'status' => $vehicle->available_stock_quantity === 0
                        ? 'Out of Stock'
                        : ($vehicle->available_stock_quantity === 1 ? 'Critical' : 'Low'),
                ];
            })
            ->values();

        return [
            'labels' => ['Out of Stock', 'Critical', 'Low', 'Healthy'],
            'values' => [$outOfStock, $critical, $low, $healthy],
            'items' => $alertItems->all(),
            'open_alerts' => $outOfStock + $critical + $low,
            'healthy_items' => $healthy,
        ];
    }

    private function estimatedProfitForSale(Sell $sell, Collection $averageCostByVehicle): float
    {
        $averageUnitCost = (float) ($averageCostByVehicle->get($sell->vehicle_id) ?? 0);

        return (float) $sell->selling_price_to_customer - ($averageUnitCost * (int) $sell->quantity);
    }
}
